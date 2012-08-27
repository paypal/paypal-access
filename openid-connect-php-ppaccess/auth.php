<?php
//PayPal Access OpenID Connect Endpoints
define('AUTHORIZATION_ENDPOINT', 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize');
define('ACCESS_TOKEN_ENDPOINT', 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/tokenservice');
define('PROFILE_ENDPOINT', 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/userinfo');
define('LOGOUT_ENDPOINT', 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/endsession');
define('VALIDATE_ENDPOINT', 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/checkid');

class PayPalAccess{
    private $key = 'YOUR KEY';
    private $secret = 'YOUR SECRET';
    private $scopes = 'YOUR SCOPES';                    //e.g. openid email profile https://uri.paypal.com/services/paypalattributes
    private $state = time() . rand();
    private $callback_url = 'YOUR CALLBACK ENDPOINT';
    
    private $access_token;
    private $refresh_token;
    private $id_token;
    
    /**
    * Get Auth URL
    *
    * Obtain the auth URL on PayPal to which the user should be forwarded
    * to in order to log in and authorize access permissions.
    * 
    */
    public function get_auth_url(){
        $auth_url = sprintf("%s?client_id=%s&response_type=code&scope=%s&redirect_uri=%s&nonce=%s&state=%s",
                            AUTHORIZATION_ENDPOINT,
                            $this->key,
                            $this->scopes,
                            urlencode($this->callback_url),
                            time() . rand(),
                            $this->state);
            
        return $auth_url;
    }
    
    /**
    * Get Access Token
    *
    * After the user is forwarded back to the application callback (defined in
    * the application at devportal.x.com) and the code parameter is available on
    * the query string, exchange the code parameter for an access token.
    * 
    */
    public function get_access_token(){
        $code = $_GET['code'];
        
        $postvals = sprintf("client_id=%s&client_secret=%s&grant_type=authorization_code&code=%s",
                            $this->key,
                            $this->secret,
                            $code,
                            urlencode($this->callback_url));
        
        $token = json_decode($this->run_curl(ACCESS_TOKEN_ENDPOINT, "POST", $postvals));
        
        $this->access_token = $token->access_token;
        $this->refresh_token = $token->refresh_token;
        $this->id_token = $token->id_token;
        
        return $token;
    }
    
    /**
    * Refresh Access Token
    *
    * If the access token has expired, call the access token endpoint with the
    * refresh token to automatically refresh and provide back a new
    * access token.
    * 
    */
    public function refresh_access_token(){
        $postvals = sprintf("client_id=%s&client_secret=%s&grant_type=refresh_token&refresh_token=%s&scope=%s",
                            $this->key,
                            $this->secret,
                            $this->refresh_token,
                            $this->scopes);
        
        $token = json_decode($this->run_curl(ACCESS_TOKEN_ENDPOINT, "POST", $postvals));
        
        return $token;
    }
    
    /**
    * Validate Token
    *
    * Provides a validation response back to the user for id token validation
    * purposes.
    * 
    */
    public function validate_token(){
        $postvals = sprintf("access_token=%s", $this->id_token);
        
        $verification = $this->run_curl(VALIDATE_ENDPOINT, "POST", $postvals);
        
        return $verification;
    }
    
    /**
    * Get Profile
    *
    * Get the full profile of the user using the access token.  This will
    * return all information that the application has requested and the user
    * has accepted from the permissions.
    * 
    */
    public function get_profile(){
        $profile_url = sprintf("%s?schema=openid&access_token=%s",
                               PROFILE_ENDPOINT,
                               $this->access_token);
        
        $profile = json_decode($this->run_curl($profile_url));
        
        return $profile;
    }
    
    /**
    * End Session
    *
    * Call the PayPal logout endpoint to log the user out.  When auth is
    * requested following this call, the user will be prompted to log in
    * again with their PayPal credentials.
    * 
    */
    public function end_session(){
        $logout_url = sprintf("%s?id_token=%s&state=%s&redirect_url=%s",
                               LOGOUT_ENDPOINT,
                               $this->id_token,
                               $this->state,
                               $this->callback_url . "&logout=true");
        
        $this->run_curl($logout_url);
    }
    
    /**
    * cURL
    *
    * Execute a cURL HTTP POST / GET request with optional headers
    */
    private function run_curl($url, $method = 'GET', $postvals = null){
        $ch = curl_init($url);
       
        //GET request: send headers and return data transfer
        if ($method == 'GET'){
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSLVERSION => 3
            );
            curl_setopt_array($ch, $options);
        //POST / PUT request: send post object and return data transfer
        } else {
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_POST => 1,
                CURLOPT_VERBOSE => 1,
                CURLOPT_POSTFIELDS => $postvals,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSLVERSION => 3
            );
            curl_setopt_array($ch, $options);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
       
        return $response;
    }

}
?>
