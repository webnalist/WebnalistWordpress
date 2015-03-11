<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title>Webnalist Merchant Demo Store</title>
</head>
<body>

<?php
include_once('conf.php');
include_once('../WebnalistBackend.php');

//demo data
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$receivePurchasedResponse = ($articleId == 1) ? true : false; //first article in sandbox mode is purchased
$currentArticleUrl = PAGE_URL . '/article.php?id=' . $articleId;
$title = '<h1>' . str_repeat($articleId, rand(10, 20)) . '</h1>';
$lead = '<p>Lead for article #' . $articleId . ' Eu leo sem velit, odio nam ipsum, molestie commodo mauris quis iaculis nisl integer . Feugiat egestas orci auctor, viverra nec orci nullam, rutrum bibendum cursus vulputate viverra aliquam dignissim sodales . Convallis nam ligula molestie nam lacinia neque augue cras massa porta porttitor; Scelerisque erat dui aliquam bibendum . Aliquam curabitur quam eu etiam egestas quam pellentesque venenatis tincidunt augue pellentesque feugiat est curabitur augue cras augue .</p>';
$fullText = '<hr><p><strong>Full text of article #' . $articleId . '</strong> senectus et netus et malesuada fames ac turpis egestas. Vestibulum tortor quam, feugiat vitae, ultricies eget, tempor sit amet, ante. Donec eu libero sit amet quam egestas semper. <em>Aenean ultricies mi vitae est.</em> Mauris placerat eleifend leo. Quisque sit amet est et sapien ullamcorper pharetra. Vestibulum erat wisi, condimentum sed, <code>commodo vitae</code>, ornare sit amet, wisi. Aenean fermentum, elit eget tincidunt condimentum, eros ipsum rutrum orci, sagittis tempus lacus enim ac dui. <a href="#">Donec non enim</a> in turpis pulvinar facilisis. Ut felis.</p>
<h2>Header Level 2</h2><ol><li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</li><li>Aliquam tincidunt mauris eu risus.</li></ol>';
$purchasedInfo = '<p><strong>Artykuł kupiony przez <a href="https://webnalist.com" target="_blank">Webnalist.com</a></strong></p>';
$purchaseUrl = sprintf('<p><a href="#" data-wn-url="%s" class="wn-item">Read with Webnalist.com</a></p>', $currentArticleUrl);
$listUrl = '<p><a href="index.php">&laquo; back to articles list</a></p>';


//is purchased, return full article
if ($isPurchased && !$error) {
    $view = $title;
    $view .= $purchasedInfo;
    $view .= $lead;
    $view .= $fullText;
    $view .= $listUrl;
//is not purchased, return lead, purchase link and error if occurred
} else {
    $view = $title;
    $view .= $lead;
    $view .= $purchaseUrl;
    if ($error) {
        $view .= sprintf('<p class="error">%s</p>', $error);
    }
    $view .= $listUrl;
}

//show article
echo $view;

?>
<script>
    var WN = WN || {};
    <?php if (SANDBOX_MODE) : ?>
    WN.options = {
        sandbox: {
            url: '<?php echo PAGE_URL ?>'
        }
    }
    <?php endif; ?>
</script>
<script src="js/webnalist.min.js"></script>

</body>
</html>