<?php
/** WEBNALIST VALIDATOR MOCKUP */
$response = new stdClass();
$response->articleId = $url;
$response->token = isset($_GET['wn_token']) ? $_GET['wn_token'] : null;
$response->purchaseId = isset($_GET['wn_purchase_id']) ? $_GET['wn_purchase_id'] : null;

if (!$response->token || !$response->purchaseId) {
    $response->code = 'api.response.voter.missing.params';
    $response->title = 'Missing params';
    $response->message = 'Missing url parameters (wn_token, wn_purchase_id)';
    $response->isAllowed = false;
} elseif ($response->token == 'validToken') {
    $response->token = 'validToken';
    $response->code = 'api.response.voter.allowed';
    $response->title = 'Access allowed';
    $response->message = 'Access allowed, token has been removed.';
    $response->isAllowed = true;
} else {
    $response->token = 'invalidToken';
    $response->code = 'api.response.voter.token.invalid';
    $response->title = 'Token invalid';
    $response->message = 'Requested token is invalid or out of date. Ask user to click again on .wn-item.';
    $response->isAllowed = false;
}

echo json_encode($response);
exit;