<?php
/** WEBNALIST CONFIRMATION WINDOW MOCKUP */
if (!isset($_GET['url']) || empty($_GET['url'])) {
    throw new Exception('Url is not defined.');
}
$url = urldecode($_GET['url']);
$query = parse_url($url, PHP_URL_QUERY);
$anchor = '';
if (($pos = strpos($url, "#")) !== FALSE) {
    $anchor = substr($url, $pos);
    $url = substr($url, 0, $pos);
}
$queryPrefix = ($query) ? '&' : '?';
$currentUrl = full_url($_SERVER);
if (isset($_GET['clicked'])) {
    $isPurchased = (boolean)$_GET['clicked'];
    switch ($_GET['clicked']) {
        case
        'yes' :
            $token = 'validToken';
            break;
        case
        'invalid' :
            $token = 'invalidToken';
    }
} else {
    $isPurchased = substr($_GET['url'], -10) === '#purchased';
    if ($isPurchased) {
        $token = 'validToken';
    }
}
$responseUrl = sprintf('%s%swn_purchase_id=%s&wn_token=%s%s', $url, $queryPrefix, 1, $token, $anchor);
if ($isPurchased || $clicked) {
    $response = '<h1>Dostęp przyznany, otwieranie strony z artykułem...</h1>';
    $response .= '<a href="' . $url . '">Przejź jeśli artykuł nie został wczytany &raquo;</a>';
    $response .= '<script> window.opener.location = "' . $responseUrl . '"; window.close();</script>';
    echo $response;
    exit;
}
?>
    <h1>WebnalistPopup Sandbox</h1>
    <h2>Do you want to read article?</h2>
    <center>
        <a href="<?php echo $currentUrl . '&clicked=yes'; ?>">Yes</a>
        &nbsp; &nbsp; &nbsp; &nbsp;
        <a href="javascript:window.close();">No</a>
        &nbsp; &nbsp; &nbsp; &nbsp;
        <a href="<?php echo $currentUrl . '&clicked=invalid' ?>">Invalid</a>
    </center>

<?php
function url_origin($s, $use_forwarded_host = false)
{
    $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true : false;
    $sp = strtolower($s['SERVER_PROTOCOL']);
    $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
    $port = $s['SERVER_PORT'];
    $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
    $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
    $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

function full_url($s, $use_forwarded_host = false)
{
    return url_origin($s, $use_forwarded_host) . $s['REQUEST_URI'];
}