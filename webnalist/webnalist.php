<?php
/*
Plugin Name: Webnalist
Plugin URI: http://webnalist.com
Description: Webnalist payment integration for Wordpress.
Author: Webnalist sp. z o.o.
Version: 1.0 Alpha
Author URI: http://webnalist.com
*/

function wn_init()
{
    load_plugin_textdomain('webnalist', false, dirname(plugin_basename(__FILE__)) . '/lang');
}

function wn_the_content_filter($content)
{
    $post_id = get_the_ID();
    $url = get_permalink($post_id);
    $read = '<p class="wn-read-with-webnalist"><a class="wn-item" data-wn-url="' . $url . '" href="#">Przeczytaj za <strong><span class="wn-price">...</span> zł</strong> z Webnalist.com &raquo;</a></p>';
    $wnStatus = get_post_meta($post_id, 'wn_status', true);
    if (!is_single()) {
        if ($wnStatus) {
            return $content . $read;
        } else {
            return $content;
        }
    }

    include_once('lib/WebnalistBackend/WebnalistBackend.php');
    $wn_settings = get_option('wn_settings');
    $publicKey = isset($wn_settings['wn_public_key']) ? $wn_settings['wn_public_key'] : '';
    $privateKey = isset($wn_settings['wn_private_key']) ? $wn_settings['wn_private_key'] : '';
    $printDebugMode = isset($wn_settings['wn_debug']) ? $wn_settings['wn_debug'] : 0;
    $webnalist = new WebnalistBackend($publicKey, $privateKey, $printDebugMode);
    $isPurchased = false;
    $error = null;
    try {
        $isPurchased = $webnalist->canRead($url);
    } catch (WebnalistException $we) {
        $error = $we->getMessage();
    }
    if ($isPurchased && !$error) {
        return wn_full($post_id);
    }
    $output = get_the_content();

    if ($error) {
        $output .= '<p class="wn-error" style="color:darkred; padding-top:30px;">' . $error . '</p>';
    }
    if ($wnStatus) {
        $output .= $read;
    }

    return $output;
}

function wn_full($post_id)
{
    $wnPaid = '<div class="wn-paid">Artykuł kupiony przez Webnalist.com. <a href="https://webnalist.com/czytelnik" target="_blank">Twoje konto <strong>Webnalist</strong></Strong>.</a></div>';
    $output = $wnPaid;
    $output .= get_the_content();
    $output .= '<div class="wn-paid-content-container" style="padding-top:20px;">' . get_post_meta($post_id, 'wn_paid_content', true) . '</div>';
    $output .= $wnPaid;

    return $output;
}

function wn_add_post_meta_boxes()
{
    add_meta_box(
        'wn_price',      // Unique ID
        esc_html__('Earn money with Webnalist.com', 'webnalist'),    // Title
        'wn_post_meta_box',   // Callback function
        'post',         // Admin page (or post type)
        'normal',         // Context
        'high'       // Priority
    );
}

/**
 * is_edit_page
 * function to check if the current page is a post edit page
 *
 * @author Ohad Raz <admin@bainternet.info>
 *
 * @param  string $new_edit what page to check for accepts new - new post page ,edit - edit post page, null for either
 * @return boolean
 */
function is_edit_page($new_edit = null)
{
    global $pagenow;
    //make sure we are on the backend
    if (!is_admin()) return false;


    if ($new_edit == "edit")
        return in_array($pagenow, array('post.php',));
    elseif ($new_edit == "new") //check for new post page
        return in_array($pagenow, array('post-new.php'));
    else //check for either new or edit
        return in_array($pagenow, array('post.php', 'post-new.php'));
}

function wn_post_meta_box($object, $box)
{

    if (is_edit_page('new')) {
        $wn_settings = get_option('wn_settings');
        $statusValue = isset($wn_settings['wn_is_paid_default']) ? $wn_settings['wn_is_paid_default'] : 0;
        if (!$statusValue) {
            $price = '';
        } else {
            $price = isset($wn_settings['wn_default_price']) ? $wn_settings['wn_default_price'] : '';
        }
    } else {
        $statusValue = get_post_meta($object->ID, 'wn_status', true);
        $paidContent = get_post_meta($object->ID, 'wn_paid_content', true);
        $price = get_post_meta($object->ID, 'wn_price', true);
    }

    $statuses = array(
        0 => 'Free article',
        1 => 'Paid article',
    );
    ?>
    <p>
        <label for="wn-post-status"><?php _e("Webnalist payment status", 'webnalist'); ?></label>
        <br/>
        <select class="widefat" name="wn-post-status" id="wn-post-status">
            <?php foreach ($statuses as $status => $name) : ?>
                <option <?php echo ($status == $statusValue) ? 'selected="selected"' : ''; ?>
                    value="<?php echo $status; ?>"><?php _e($name, 'webnalist'); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <label
            for="wn-post-price"><?php _e("Post price in PLN/100 (grosz), '150' = 1,50zł. From 1 to 700.", 'webnalist'); ?></label>
        <br/>
        <input maxlength="3" type="number" step="1" min="1" max="700" name="wn-post-price" id="wn-post-price"
               value="<?php echo esc_attr($price); ?>" size="30"/>
    </p>
    <h3><?php _e("Restricted paid content", 'webnalist'); ?></h3>
    <?php
    echo wp_editor(esc_attr($paidContent), 'wn_paid_content');

}

function wn_set_post_meta($post_id, $post, $field, $key)
{
    $post_type = get_post_type_object($post->post_type);
    if (!current_user_can($post_type->cap->edit_post, $post_id))
        return $post_id;
    $new_meta_value = (isset($_POST[$field]) ? $_POST[$field] : '');
    $meta_key = $key;
    $meta_value = get_post_meta($post_id, $meta_key, true);
    if ($new_meta_value && '' == $meta_value) {
        add_post_meta($post_id, $meta_key, $new_meta_value, true);
    } elseif ($new_meta_value && $new_meta_value != $meta_value) {
        update_post_meta($post_id, $meta_key, $new_meta_value);
    } elseif ('' == $new_meta_value && $meta_value) {
        delete_post_meta($post_id, $meta_key, $meta_value);
    }
}

function wn_save_post_meta($post_id, $post)
{
    wn_set_post_meta($post_id, $post, 'wn-post-price', 'wn_price');
    wn_set_post_meta($post_id, $post, 'wn-post-status', 'wn_status');
    wn_set_post_meta($post_id, $post, 'wn_paid_content', 'wn_paid_content');
}


/**
 * Register and enqueue a ja script
 */
function wn_add_scripts()
{
    wp_register_script(
        'webnalist',
        plugins_url() . '/webnalist/lib/WebnalistFrontend/webnalist.min.js',
        array(),
        '1.0',
        true
    );
    wp_enqueue_script('webnalist');
}

function wn_config_script()
{
    $wn_settings = get_option('wn_settings');
    echo '
<script>
    var WN = WN || {};
    WN.options = {
        loadPrices: true,
        popup: ' . $wn_settings['wn_popup'] . '
    };
</script>
';
}

function wn_post_meta_boxes_setup()
{
    add_action('add_meta_boxes', 'wn_add_post_meta_boxes');
    add_action('save_post', 'wn_save_post_meta', 10, 2);
}

function wn_add_admin_menu()
{

    add_menu_page('Webnalist.com', 'Webnalist.com', 'manage_options', 'webnalist', 'wn_options_page');

}

function wn_settings_init()
{

    register_setting('webnalist', 'wn_settings');

    add_settings_section(
        'wn_webnalist_section',
        __('Webnalist paid articles settings', 'ebnalist'),
        'wn_settings_section_callback',
        'webnalist'
    );

    add_settings_field(
        'wn_public_key',
        __('Klucz publiczny API', 'webnalist'),
        'wn_public_key_render',
        'webnalist',
        'wn_webnalist_section'
    );

    add_settings_field(
        'wn_secret_key',
        __('Klucz prywatny API', 'webnalist'),
        'wn_secret_key_render',
        'webnalist',
        'wn_webnalist_section'
    );

    add_settings_field(
        'wn_default_price',
        __('Domyślna cena artylułu (grosze)', 'webnalist'),
        'wn_default_price_render',
        'webnalist',
        'wn_webnalist_section'
    );

    add_settings_field(
        'wn_is_paid_default',
        __('Nowy artykuł domyślnie płatny', 'webnalist'),
        'wn_is_paid_default_render',
        'webnalist',
        'wn_webnalist_section'
    );

    add_settings_field(
        'wn_debug',
        __('Tryb debugowania', 'webnalist'),
        'wn_debug_render',
        'webnalist',
        'wn_webnalist_section'
    );

    add_settings_field(
        'wn_popup',
        __('Okienko popup', 'webnalist'),
        'wn_popup_render',
        'webnalist',
        'wn_webnalist_section'
    );
}


function wn_public_key_render()
{
    $options = get_option('wn_settings');
    ?>
    <input class="regular-text" type='text' name='wn_settings[wn_public_key]'
           value='<?php echo $options['wn_public_key']; ?>'>
    <?php

}


function wn_secret_key_render()
{

    $options = get_option('wn_settings');
    ?>
    <input class="regular-text" type='text' name='wn_settings[wn_secret_key]'
           value='<?php echo $options['wn_secret_key']; ?>'>
    <?php

}

function wn_default_price_render()
{
    $options = get_option('wn_settings');
    ?>
    <input class="small-text" type='number' min="1" max="700" step="1" name='wn_settings[wn_default_price]'
           value='<?php echo $options['wn_default_price']; ?>'>
    <?php

}

function wn_is_paid_default_render()
{

    $options = get_option('wn_settings');
    ?>
    <input type='checkbox' name='wn_settings[wn_is_paid_default]' <?php checked($options['wn_is_paid_default'], 1); ?>
           value='1'>
    <?php

}

function wn_debug_render()
{
    $options = get_option('wn_settings');
    ?>
    <input type='checkbox' name='wn_settings[wn_debug]' <?php checked($options['wn_debug'], 1); ?>
           value='1'>
    <?php
}


function wn_popup_render()
{
    $options = get_option('wn_settings');
    ?>
    <input type='checkbox' name='wn_settings[wn_popup]' <?php checked($options['wn_popup'], 1); ?>
           value='1'>
    <?php
}

function wn_settings_section_callback()
{
    echo __('Requests for the merchant account: admin@webnalist.com', 'webnalist');
}

function wn_options_page()
{
    ?>
    <form action="options.php" method="post">

        <h2>Webnalist.com</h2>

        <?php
        settings_fields('webnalist');
        do_settings_sections('webnalist');
        submit_button();
        ?>

    </form>
    <?php

}

function wn_item_class()
{
    return [
        'wn-item'
    ];
}

if (is_admin()) {
    add_action('load-post.php', 'wn_post_meta_boxes_setup');
    add_action('load-post-new.php', 'wn_post_meta_boxes_setup');
    add_action('admin_menu', 'wn_add_admin_menu');
    add_action('admin_init', 'wn_settings_init');
}

add_action('wp_enqueue_scripts', 'wn_add_scripts');
add_filter('post_class', 'wn_item_class');
add_action('wp_footer', 'wn_config_script');
add_filter('the_content', 'wn_the_content_filter');

add_action('init', 'wn_init');
