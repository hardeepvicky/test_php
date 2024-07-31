<?php
require 'generator.php';

ini_set('memory_limit', '-1');
set_time_limit(0);

function d($arg, $will_exit = false)
{
    $callBy = debug_backtrace()[0];
    echo "<pre>";
    echo "<b>" . $callBy['file'] . "</b> At Line : " . $callBy['line'];
    echo "<br/>";
    
    if (is_string($arg))
    {
        echo htmlspecialchars($arg);
    }
    else
    {
        print_r($arg);
    }
    
    echo "</pre>";

    if ($will_exit)
    {
        exit;
    }
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
    curl_close($curl);

    $json = json_decode($response , true);
    if (isset($json['contactTag']['contact']))
    {
        $contactID = $json['contactTag']['contact'];
        saveToContactList($contactID);  //saveToContactList will save contact in a list based on pass contact id
    }

    return json_decode($response, true);
}

function fetch_swoogo($url, $params = [])
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

function fetch_active_camp($url, $params = [])
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
            'Api-Token: 882e59f548141c72a906bda3f63134ac72456db39f9b1234751080d208eb55cdb3c9e889',
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    return json_decode($response, true);
}

function getRegistrantDetails($registrantId)
{
    return fetch_swoogo('https://api.swoogo.com/api/v1/registrants/'.$registrantId.'.json');
}

function saveContactToActiveComp($data)  //store contact in activecompaign
{
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

        CURLOPT_POSTFIELDS => json_encode(array('contact' => $data)),

        CURLOPT_HTTPHEADER => array(
            'Api-Token: 882e59f548141c72a906bda3f63134ac72456db39f9b1234751080d208eb55cdb3c9e889',
        ),
    ));

    $response = curl_exec($curl);
    $response = json_decode($response , true);

    if ($response['errors'])
    {
        return false;
    }

    $contactId = $response['contact']['id'];
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

function get_swoogo_contacts($page)
{
    $records = fetch_swoogo("https://api.swoogo.com/api/v1/contacts.json", [
        "fields" => [
            "id", "firstname", "last_name", "email", "mobile_phone"
        ],
        "expand" => 'registrants',
        "per-page" => 100,
        "page" => $page
    ]);

    if ($page > $records['_meta']['totalCount'])
    {
        return $records['items'];
    }

    return array_merge($records['items'], get_swoogo_contacts($page + 1));
}


function get_contact_from_active_camp($email)
{
    $response = fetch_active_camp("https://colorintech.api-us1.com/api/3/contacts", [
        "email" => $email
    ]);

    if ($response)
    {
        return $response['contacts'];
    }

    return false;
}

$total_count = $found_count = $save_count = 0;

$page = 1; //Change here

$swoogo_records = get_swoogo_contacts($page);
//d($swoogo_records, true);

$total_count = count($swoogo_records);

foreach($swoogo_records as $swoogo_record)
{
    if (isset($swoogo_record['registrants'][0]['id']))
    {
        $details = getRegistrantDetails($swoogo_record['registrants'][0]['id']);

        $swoogo_record['tag'] = $details['reg_type_id']['value'] ?? '';
    }
    
    $active_camp_contact = get_contact_from_active_camp($swoogo_record['email']);
    if ($active_camp_contact)
    {
        $found_count++;

        if (isset($swoogo_record['tag']))
        {
            $tagId = getTagNumber($swoogo_record['tag']);  //getTagNumber will match tag value and return id
            $contactTag = [
                'contact' => $swoogo_record['id'],
                'tag' => $tagId
            ];
            saveContactTag($contactTag);
        }
    }
    else
    {
        if (saveContactToActiveComp($swoogo_record))
        {
            $save_count++;
        }
    }
}

d([
    "Total Swoogo Count" => $total_count,
    "Found Swoogo Contact in Active Camp" => $found_count,
    "Save Active camp Count" => $save_count,
]);