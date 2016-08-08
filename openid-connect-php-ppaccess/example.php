<?php
/******************************************
* Copyright 2012 PayPal
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
******************************************/

//require the auth class
require_once('auth.php');

//initialize a new PayPal Access instance
$ppaccess = new PayPalAccess();

//if the code parameter is available, the user has gone through the auth process
if (isset($_GET['code'])){
    //make request to exchange code for an access token
    $token = $ppaccess->get_access_token();
    echo "<h1>Token</h1>";
    print_r($token);
    
    //use access token to get user profile
    $profile = $ppaccess->get_profile();
    echo "<h1>User Profile</h1>";
    print_r($profile);
    
    //make request to refresh an expired access token
    $refreshed = $ppaccess->refresh_access_token();
    echo "<h1>Refreshed Token</h1>";
    print_r($refreshed);
    
    //validate the id token and provide back validation object
    $verify = $ppaccess->validate_token();
    echo "<h1>Token Validation</h1>";
    print_r($verify);
    
    //log the user out
    $ppaccess->end_session();
//if the code parameter is not available, the user should be pushed to auth
} else {
    //handle case where there was an error during auth (e.g. the user didn't log in / refused permissions / invalid_scope)
    if (isset($_GET['error_uri'])){
        echo "error";
    //this is the first time the user has come to log in
    } else {
        //get auth url and redirect user browser to PayPal to log in
        $url = $ppaccess->get_auth_url();
        header("Location: $url");
    }
}
?>
