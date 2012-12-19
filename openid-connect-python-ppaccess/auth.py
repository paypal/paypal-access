#
# Copyright 2012 eBay Inc
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

import cgi
import random
import time
import urllib
import urllib2
import json

class PayPalAccess:
    #PayPal Access OpenID Connect Endpoints
    authorization_endpoint = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize'
    access_token_endpoint = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/tokenservice'
    profile_endpoint = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/userinfo'
    logout_endpoint = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/endsession'
    validate_endpoint = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/checkid'
    
    key = 'YOUR APPLICATION ID'
    secret = 'YOUR APPLICATION SECRET'
    scopes = 'openid'                       #e.g. openid email profile https://uri.paypal.com/services/paypalattributes
    callback_url = 'YOUR CALLBACK URL'
    nonce = random.random() + time.time()
    
    access_token = ''
    refresh_token = ''
    id_token = ''
    
    """
    " Get Auth URL
    "
    " Obtain the auth URL on PayPal to which the user should be forwarded
    " to in order to log in and authorize access permissions.
    """
    def get_auth_url(self):
        #construct PayPal authorization URI
        self.auth_url = "%s?client_id=%s&response_type=code&scope=%s&redirect_uri=%s&nonce=%s" % (PayPalAccess.authorization_endpoint, PayPalAccess.key, PayPalAccess.scopes, PayPalAccess.callback_url, PayPalAccess.nonce)
        
        #redirect the user to the PayPal authorization URI
        return 'Location: ' + self.auth_url
    
    """
    " Get Access Token
    "
    " After the user is forwarded back to the application callback (defined in
    " the application at devportal.x.com) and the code parameter is available on
    " the query string, exchange the code parameter for an access token.
    """ 
    def get_access_token(self, code):
        self.code = code
        self.postvals = {'client_id': PayPalAccess.key, 'client_secret': PayPalAccess.secret, 'grant_type': 'authorization_code', 'code': self.code}
    
        #make request to capture access token
        self.params = urllib.urlencode(self.postvals)
        self.f = urllib.urlopen(PayPalAccess.access_token_endpoint, self.params)
        self.token = json.read(self.f.read())
        
        PayPalAccess.access_token = self.token['access_token']
        PayPalAccess.refresh_token = self.token['refresh_token']
        PayPalAccess.id_token = self.token['id_token']
        
        return self.token
    
    """
    " Refresh Access Token
    "
    " If the access token has expired, call the access token endpoint with the
    " refresh token to automatically refresh and provide back a new
    " access token.
    """ 
    def refresh_access_token(self):
        self.postvals = {'client_id': PayPalAccess.key, 'client_secret': PayPalAccess.secret, 'grant_type': 'refresh_token', 'refresh_token': PayPalAccess.refresh_token}
    
        #make request to refresh access token
        self.params = urllib.urlencode(self.postvals)
        self.f = urllib.urlopen(PayPalAccess.access_token_endpoint, self.params)
        self.token = json.read(self.f.read())
        
        return self.token
    
    """
    " Validate Token
    "
    " Provides a validation response back to the user for id token validation
    " purposes.
    """ 
    def validate_token(self):
        self.postvals = {'access_token': PayPalAccess.id_token}
        
        #make request to validate id token
        self.params = urllib.urlencode(self.postvals)
        self.f = urllib.urlopen(PayPalAccess.validate_endpoint, self.params)
        self.verification = self.f.read()
        
        return self.verification
    
    """
    " Get Profile
    "
    " Get the full profile of the user using the access token.  This will
    " return all information that the application has requested and the user
    " has accepted from the permissions.
    """ 
    def get_profile(self):
        self.profile_url = "%s?schema=openid&access_token=%s" % (PayPalAccess.profile_endpoint, PayPalAccess.access_token)
        
        self.request = urllib2.Request(self.profile_url)
        self.response = urllib2.urlopen(self.request)
        self.profile = self.response.read()
        
        return self.profile

    """
    " End Session
    ""
    " Call the PayPal logout endpoint to log the user out.  When auth is
    " requested following this call, the user will be prompted to log in
    " again with their PayPal credentials.
    """
    def end_session(self):
        self.profile_url = "%s?id_token=%s&redirect_uri=%s" % (PayPalAccess.logout_endpoint, PayPalAccess.id_token, PayPalAccess.callback_url + "&logout=true")
        
        self.request = urllib2.Request(self.profile_url)
        self.response = urllib2.urlopen(self.request)
