<?php

function fetch($url, $headers = [], $params = [])
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

        CURLOPT_HTTPHEADER => $headers,
    ));

    $response = curl_exec($curl);
     
    curl_close($curl);

    return json_decode($response, true);
}

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

function saveContactToActiveComp($data)  //store contact in activecompaign
{
    $postData = array(
        'email' => $data['contact']['email'],
        'firstName' => $data['contact']['first_name'] ,
        'lastName' => $data['contact']['last_name'],
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

function saveContact($contact)
{
    var_dump($contact);
    $response = fetch('https://api.swoogo.com/api/v1/contacts/'.$contact['id'].'.json', [
        'fields' => '',
        'expand' => 'registrants'
    ]);

    $data = [
        'contact' => [
            'first_name' => $contact['first_name'],
            'last_name' => $contact['last_name'],
            'email' => $contact['email'],
        ]
        ];
    
    $details = isset($response['registrants'][0]['id']) ? getRegistrantDetails($response['registrants'][0]['id']) : '';
    
    $tag = $details['reg_type_id']['value'] ?? '';
    
    $data['tag'] = $tag;
    
    saveContactToActiveComp($data);
}

ini_set('display_errors', 1);

$activeCompContacts = fetch('https://colorintech.api-us1.com/api/3/contacts', [
    'Api-Token: 882e59f548141c72a906bda3f63134ac72456db39f9b1234751080d208eb55cdb3c9e889',
], [

]);

$contacts = $activeCompContacts['contacts'];

$existingEmails = [];

foreach($contacts as $contact){
    $existingEmails[] = $contact['email'];
}

$swoogoContacts = fetch('https://api.swoogo.com/api/v1/contacts.json', 
[
    'Authorization: Bearer '.getBearerToken(),
],
[
    'fields' => 'email,first_name,last_name,id',
    'expand' => '',
    'per-page' => 1000,
    'search' => '',
    'page' => 1,
    'sort' => ''
]);


$contactsToSave = [];

foreach($swoogoContacts['items'] as $contact){
    if(!in_array($contact['email'], $existingEmails)){
        $contactsToSave[] = $contact;
    }
}

saveContact($contactsToSave[0]);
// foreach($contactsToSave as $contact){
//     saveContact($contact);
// }

function get_swoogo_contacts()
{
    $swoogoContacts = fetch('https://api.swoogo.com/api/v1/contacts.json', 
    [
        'Authorization: Bearer '.getBearerToken(),
    ],
    [
        'fields' => 'email,first_name,last_name,id',
        'expand' => '',
        'per-page' => 1000,
        'search' => '',
        'page' => 1,
        'sort' => ''
    ]);
}