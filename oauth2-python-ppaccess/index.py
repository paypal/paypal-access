import cgi
import urllib
import urllib2
import json

#client OAuth keys
key = 'YOUR KEY HERE'
secret = 'YOUR SECRET HERE'

#PayPal URIs and application callbacks
callback_url = "YOUR CALLBACK URL"
authorization_endpoint = "https://identity.x.com/xidentity/resources/authorize"
access_token_endpoint = "https://identity.x.com/xidentity/oauthtokenservice"
profile_endpoint = "https://identity.x.com/xidentity/resources/profile/me"

#get query string parameters
params = cgi.FieldStorage()

"""
" If a code parameter is available in the query string then the user
" has given the client permission to access their protected data.
" If not, the script should forward the user to log in and accept
" the application permissions.
"""
if params.has_key('code'):
    code = params['code'].value
    
    #construct POST object for access token fetch request
    postvals = {'grant_type': 'authorization_code', 'client_id': key, 'client_secret': secret, 'code': code, 'redirect_uri': callback_url, 'redirect_uri': callback_url}
    
    #make request to capture access token
    params = urllib.urlencode(postvals)
    f = urllib.urlopen(access_token_endpoint, params)
    token = json.read(f.read())
    
    #make OAuth signed request for protected user profile
    profile_url = "%s?oauth_token=%s" % (profile_endpoint, token['access_token'])
    request = urllib2.Request(profile_url)
    response = urllib2.urlopen(request)
    profile = response.read()
    
    #print profile data response
    print 'Content-Type: text/plain'
    print ''
    print profile
else:
    #construct PayPal authorization URI
    auth_url = "%s?scope=%s&response_type=code&redirect_uri=%s&client_id=%s" % (authorization_endpoint, profile_endpoint, callback_url, key)
    
    #redirect the user to the PayPal authorization URI
    print "Location: " + auth_url