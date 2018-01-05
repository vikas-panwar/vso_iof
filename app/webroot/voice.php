<?php

header("Content-type:application/json");

$username = '1dd16f04a70d';
$password = 'd60e0bd98d234ebc';

$data = array(
    'name' => 'Iorder food',
    'fromNumber' => '310218-2048',
    'recipients' => array(
        array('phoneNumber' => '2133995508')

    ),
    'answeringMachineConfig' => 'AM_AND_LIVE',
    'liveSoundText' => 'You have received an online order. Order ID 234123, Name Ekansh  Thank you!',
    'machineSoundText' => 'You have received an online order. Order ID 234123, Name Ekansh  Thank you!',
    'message'=>'You have received an online order. Order ID 234123, Name Ekansh  Thank you!'
);


$data_json = json_encode($data);
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://api.callfire.com/v2/campaigns/voice-broadcasts');
//curl_setopt($ch, CURLOPT_URL, 'https://api.callfire.com/v2/calls/broadcasts?start=true');
//curl_setopt($ch, CURLOPT_URL, 'https://api.callfire.com/v2/calls');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_json))
);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = json_decode(curl_exec($ch));
print_r($result);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.callfire.com/v2/calls/broadcasts?start=true');
curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
$result = json_decode(curl_exec($ch));
print_r($result);

?>
