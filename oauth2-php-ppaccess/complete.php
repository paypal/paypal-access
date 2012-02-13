<?php
require_once "common.php";

//capture code from auth
$code = $_GET["code"];

//construct POST object for access token fetch request
$postvals = sprintf("client_id=%s&client_secret=%s&grant_type=authorization_code&code=%s&redirect_uri=%s", KEY, SECRET, $code, urlencode(CALLBACK_URL));

//get JSON access token object (with refresh_token parameter)
$token = json_decode(run_curl(ACCESS_TOKEN_ENDPOINT, 'POST', $postvals));

//construct URI to fetch profile information for current user
$profile_url = sprintf("%s?oauth_token=%s", PROFILE_ENDPOINT, $token->access_token);

//fetch profile of current user
$profile = run_curl($profile_url);

var_dump($profile);
?>
