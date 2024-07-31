<?php
require 'generator.php';

function getTagNumber($tag)
{
    $tagMap = [
        'Educator & Guardian Pass ' => 169,
        'Futures Pass' => 163,
        'Build  Pass ' => 165,
        'Ignite  Pass' => 179,
        'All Access Pass' => 181,
        'VIP Pass' => 180,
        'Expo  Pass' => 182
    ];

    return $tagMap[$tag] ?? null;
}

function saveContactTag($data)  //save tag for contact in activecompaign
{
  $json = json_encode(array('contactTag' => $data));
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://colorintech.api-us1.com/api/3/contactTags',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => array(
        'Api-Token: 882e59f548141c72a906bda3f63134ac72456db39f9b1234751080d208eb55cdb3c9e889',
        'Content-Type: application/json',
        'Cookie: PHPSESSID=2e57762737faabb229f48166cf936011; em_acp_globalauth_cookie=328349cb-a1a4-4790-954a-d948c13af065'
        ),
    ));

    $response = curl_exec($curl);
    $json = json_decode($response , true);
    $contactID = $json['contactTag']['contact'];
    saveToContactList($contactID);  //saveToContactList will save contact in a list based on pass contact id
    curl_close($curl);

    return json_decode($response, true);
}

function fetch($url, $params = [])
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url.'?'. http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',

        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer '.getBearerToken(),
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response, true);
}

function getRegistrantDetails($registrantId)
{
    return fetch('https://api.swoogo.com/api/v1/registrants/'.$registrantId.'.json');
}

function saveContactToActiveComp($data)  //store contact in activecompaign
{
    $postData = array(
        'email' => $data['contact']['email'],
        'firstName' => $data['contact']['first_name'] ,
        'lastName' => $data['contact']['last_name'],
        'phone' => $data['contact']['mobile_phone']
    );

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://colorintech.api-us1.com/api/3/contacts',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',

        CURLOPT_POSTFIELDS => json_encode(array('contact' => $postData)),

        CURLOPT_HTTPHEADER => array(
            'Api-Token: 882e59f548141c72a906bda3f63134ac72456db39f9b1234751080d208eb55cdb3c9e889',
        ),
    ));

    $response = curl_exec($curl);
    $contact = json_decode($response , true);
    $contactId = $contact['contact']['id'];
    curl_close($curl);

    $tagId = getTagNumber($data['tag']);  //getTagNumber will match tag value and return id
    $contactTag = [
        'contact' => $contactId,
        'tag' => $tagId
    ];
    saveContactTag($contactTag); //saveContactTag function save tag for contact in activecompaign
    return json_decode($response, true);
}

function saveToContactList($contactId)  //will update list for activecompaign contact
{
    $data = [
        'list' => 5,
        'contact' => $contactId,
        'status' => 1
    ];

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://colorintech.api-us1.com/api/3/contactLists',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',

        CURLOPT_POSTFIELDS => json_encode(array('contactList' => $data)),

        CURLOPT_HTTPHEADER => array(
            'Api-Token: 882e59f548141c72a906bda3f63134ac72456db39f9b1234751080d208eb55cdb3c9e889',
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    echo 'Saved contact to Active Comp Contact Lists';

    return json_decode($response, true);
}

$request = file_get_contents("php://input");

$data = json_decode($request, true);

$contactId = $data['contact']['id'];

$response = fetch('https://api.swoogo.com/api/v1/contacts/'.$contactId.'.json', [
    'fields' => '',
    'expand' => 'registrants'
]);

$details = isset($response['registrants'][0]['id']) ? getRegistrantDetails($response['registrants'][0]['id']) : '';

$tag = $details['reg_type_id']['value'] ?? '';

$data['tag'] = $tag;

saveContactToActiveComp($data); // save contact in activecompaign from webhook response using saveContactToActiveComp function calling

?>
