<?php

function getBearerToken()
{
    // Check if bearer token is present in cookie
    if(isset($_COOKIE['api:token'])){
        return $_COOKIE['api:token'];
    }

    // Swoogo credentials
    $clientId = 'JuwsIB1eZMGrg1Gz0xS0d';

    $clientSecret = 'G_Mjqi82r3-f8eCRIizPJsfu-5RA-sHzgWf-DAszlS';

    // Swoogo api call
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.swoogo.com/api/v1/oauth2/token.json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',

        CURLOPT_POSTFIELDS => http_build_query(array('grant_type' => 'client_credentials')),

        CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.base64_encode($clientId.':'.$clientSecret),
                'Content-Type: application/x-www-form-urlencoded'
            ),
        )
    );

    $response = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response, true);

    $bearerToken = $response['access_token'] ?? '';

    // set cookie for 30 minutes since swoogo api token expires in 30 minutes
    setcookie("api:token", $bearerToken, time() + 30 * 60, "/");

    return $bearerToken;
}

