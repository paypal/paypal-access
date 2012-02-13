<?php
define('KEY', 'YOUR APPLICATION ID');
define('SECRET', 'YOUR APPLICATION SECRET');

define('CALLBACK_URL', 'YOUR CALLBACK PATH - TO COMPLETE.PHP');
define('AUTHORIZATION_ENDPOINT', 'https://identity.x.com/xidentity/resources/authorize');
define('ACCESS_TOKEN_ENDPOINT', 'https://identity.x.com/xidentity/oauthtokenservice');
define('PROFILE_ENDPOINT', 'https://identity.x.com/xidentity/resources/profile/me');

/***************************************************************************
 * Function: Run CURL
 * Description: Executes a CURL request
 * Parameters: url (string) - URL to make request to
 *             method (string) - HTTP transfer method
 *             headers - HTTP transfer headers
 *             postvals - post values
 **************************************************************************/
function run_curl($url, $method = 'GET', $postvals = null){
    $ch = curl_init($url);
    
    //GET request: send headers and return data transfer
    if ($method == 'GET'){
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1
        );
        curl_setopt_array($ch, $options);
    //POST / PUT request: send post object and return data transfer
    } else {
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_VERBOSE => 1,
            CURLOPT_POSTFIELDS => $postvals,
            CURLOPT_RETURNTRANSFER => 1
        );
        curl_setopt_array($ch, $options);
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
?>
