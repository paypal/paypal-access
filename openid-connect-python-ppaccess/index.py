from auth import *

ppaccess = PayPalAccess()

params = cgi.FieldStorage()
if params.has_key('code'):
    print 'Content-Type: text/plain'
    print ''
    
    #get access token
    token = ppaccess.get_access_token(params['code'].value)
    print token
    
    #get user profile
    profile = ppaccess.get_profile()
    print "<h1>Profile</h1>"
    print profile
    
    #refresh access token
    refreshed = ppaccess.refresh_access_token()
    print "<h1>Refreshed Token</h1>"
    print refreshed
    
    #validate the id token and provide back validation object
    verify = ppaccess.validate_token()
    print "<h1>Validated Token</h1>"
    print verify
    
    #log the user out
    ppaccess.end_session()
else:
    #get auth url and redirect user browser to PayPal to log in
    print ppaccess.get_auth_url()