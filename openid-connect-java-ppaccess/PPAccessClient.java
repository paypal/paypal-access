package com.billmelater.acquisition;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.Map;
import java.util.Random;

import org.codehaus.jackson.map.ObjectMapper;

import sun.misc.BASE64Encoder;

/**
 * Client implementation to get user information using PayPal Access. First step of getting code from PayPal access has
 * to be done manually. You can use <code>authorizeUrl</code> generated to get code. Uses java.net implementation to do
 * REST calls and Jackson API to parse JSON response.
 *
 * @author Abhijith Prabhakar
 *
 */
public class PPAccessClient {

    /**
     * Each method call dissipates a step in OpenId Connect specification.
     *
     * @param args - it do not send anything
     * @throws IOException
     */
    public static void main(String[] args) throws IOException {
        String authorizeUrl = getAuthorizationUrl();
        System.out.println("Paste this url in your browser: " + authorizeUrl);

        // Wait for the authorization code
        System.out.println("Type the code you received here: ");
        BufferedReader in = new BufferedReader(new InputStreamReader(System.in));
        String authorizationCode = in.readLine();

        Map<String, Object> responseMap = getResponseMap(authorizationCode);
        String accessToken = (String) responseMap.get("access_token");
        String idToken = (String) responseMap.get("id_token");
        System.out.println("Access token for this request: " + accessToken);
        System.out.println("Id token for this request: " + idToken);
        Map<String, Object> userInfo = getUserInfo(accessToken);
        System.out.println(" ****** User Info endpoint ***** ");
        for (Map.Entry<String, Object> entry : userInfo.entrySet()) {
            System.out.println(entry.getKey() + " " + entry.getValue());
        }
        System.out.println(" ***** Check Id Endpoint **** ");
        Map<String, Object> checkIdMap = getCheckId(idToken);
        for (Map.Entry<String, Object> entry : checkIdMap.entrySet()) {
            System.out.println(entry.getKey() + " " + entry.getValue());
        }

        System.out.println(" *** End session Endpoint ***");
    endSession(idToken);

        in.close();
    }

    /**
     * Creates an authorization URL so that user can paste this in browser to get authorization code.
     *
     * @return - Authorization URL
     */
    private static String getAuthorizationUrl() {
        StringBuilder authUrl = new StringBuilder(
                "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize");
        // all the below params should be changed according to your needs. Below values are just an example.
        authUrl.append("?client_id=<REPLACE ME>");
        authUrl.append("&response_type=code");
        authUrl.append("&scope=openid profile email address");
        authUrl.append("&nonce=" + createNonce());
        authUrl.append("&redirect_uri=<REPLACE ME>");
        return authUrl.toString();
    }

    /**
     * Gets Access token by going to token service. Code is left blank so that user can fill it up manually.
     *
     * @return - Access token
     */
    private static Map<String, Object> getResponseMap(String authorizationCode) {
        StringBuilder tokenUrl = new StringBuilder(
                "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/tokenservice");
        tokenUrl.append("?grant_type=authorization_code");
        // code should be obtained manually and pasted here.
        tokenUrl.append("&code=" + authorizationCode);
        Map<String, Object> responseMap = getResponse(tokenUrl.toString(), "POST", "Basic " + getAuthorizationHeader());
        return responseMap;
    }

    /**
     * Gets user info based on <code>accesstoken</code> passed.
     *
     * @param accessToken - Access token acquired via token service.
     * @return - User Information map.
     */
    private static Map<String, Object> getUserInfo(String accessToken) {
        StringBuilder userInfoUrl = new StringBuilder(
                "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/userinfo");
        userInfoUrl.append("?schema=openid");
        return getResponse(userInfoUrl.toString(), "GET", ("Bearer " + accessToken));
    }

    /**
     * Java SDK implementation to make rest calls.
     *
     * @param urlStr - URL to be used.
     * @param method - HTTP Method
     * @param authHeader - Authorization header value
     * @return - User Information map.
     */
    private static Map<String, Object> getResponse(String urlStr, String method, String authHeader) {
        try {
            URL url = new URL(urlStr);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod(method);
            if(authHeader != null){
                conn.setRequestProperty("Authorization", authHeader);
            }
            conn.setRequestProperty("Accept", "application/json");

            if (conn.getResponseCode() != 200) {
                throw new RuntimeException("Failed : HTTP error code : " + conn.getResponseCode());
            }

            BufferedReader br = new BufferedReader(new InputStreamReader((conn.getInputStream())));

            String output;
            StringBuilder builder = new StringBuilder();
            while ((output = br.readLine()) != null) {
                builder.append(output);
            }
            // Jackson object mapper to unmarshall json response. More info at:
            // http://wiki.fasterxml.com/JacksonInFiveMinutes
            ObjectMapper mapper = new ObjectMapper();
            Map<String, Object> responseMap = mapper.readValue(builder.toString(), Map.class);
            conn.disconnect();
            return responseMap;
        } catch (MalformedURLException e) {
            e.printStackTrace();
        } catch (IOException e) {
            e.printStackTrace();
        }
        return null;
    }

    /**
     * Authorization header required for token service.
     *
     * @return - Base64 encoded value of client id and secret
     */
    private static String getAuthorizationHeader() {
        // client id and secret has been left empty for user to fill in.
        String clientId = "<REPLACE ME>";
        String clientSecret = "<REPLACE ME>";

        String authString = clientId + ":" + clientSecret;
        BASE64Encoder encoder = new BASE64Encoder();
        return encoder.encode(authString.getBytes());
    }

    /**
     * Generates a unique nonce for every request. Created this way based on recommendation from PayPal Access team.
     * User is free to choose anything you want.
     *
     * @return - generated nonce
     */
    private static String createNonce() {
        Random random = new Random();
        int randomInt = random.nextInt();
        byte[] randomByte = {Integer.valueOf(randomInt).byteValue()};
        String encodedValue;
        String retValue;
        BASE64Encoder encoder = new BASE64Encoder();
        encodedValue = encoder.encode(randomByte);
        retValue = (System.currentTimeMillis() + encodedValue);
        return retValue;

    }

    /**
     * Gives the Url to end session.
     * @param idToken
     */
    private static void endSession(String idToken) {
        StringBuilder endsessionUrl = new StringBuilder(
                "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/endsession");
        endsessionUrl.append("?idToken=" + idToken);
        endsessionUrl.append("&logout=true");
        endsessionUrl.append("&redirect_uri=<REPLACE ME>");
        System.out.println("Put this URL on browser to end session " + endsessionUrl.toString());
    }

    /**
     * Checks if given token is still valid.
     * @param idToken
     * @return Return variables as Map
     */
    private static Map<String, Object> getCheckId(String idToken) {
        StringBuilder checkIdUrl = new StringBuilder(
                "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/checkid");
        checkIdUrl.append("?access_token=" + idToken);
        Map<String, Object> responseMap = getResponse(checkIdUrl.toString(), "GET", null);
        return  responseMap;
    }

}
