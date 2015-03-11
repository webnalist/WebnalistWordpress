<?php
/** WEBNALIST PRICES LIST MOCKUP */
$urls = isset($_POST['url']) ? $_POST['url'] : null;

if (!is_array($urls)) {
    throw new Exception('Expecting urls array in post request.');
}

$response = array();
foreach ($urls as $url) {
    $response[$url] = rand(1, 100);
}

echo json_encode($response);