<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pushsms extends Model
{
    static public function send_otp($phone, $otp)
    {
        $contant_id = '1707173856462706835';
        $user = 'User';
        $contant = 'Dear ' . $user . ', Your SCRAPMATE application login One Time Password (OTP) is ' . $otp . '. Do not share this OTP with anyone.';
        
        return self::singlePushSMS2($phone, $contant_id, $contant);
    }

    static public function send_sms($phone, $distance, $cust_place)
    {
        $contant_id = '1707173875190649486';
        $user = 'User';
        $contant = 'Dear ' . $user . ', New scrap materials order, located within ' . $distance . ' is ready for collection. Please review the details and coordinate the pickup with the customer at ' . $cust_place . '. SCRAPMATE';

        return self::singlePushSMS2($phone, $contant_id, $contant);
    }

    static public function testSms()
    {
        $contant = 'Dear Abhilash, Your SCRAPMATE application login One Time Password (OTP) is 2222. Do not share this OTP with anyone.';
        echo self::singlePushSMS2('9605056015','1707173856462706835',$contant);
    }

    // Alp SMS provide
    // static private function singlePushSMS($phone, $contant_id, $message)
    // {
    //     $sms_api_key = env('SMS_API_KEY', '$2a$10$DJTlpp3oMypLKkL7o.nuue0VDL65FkaF.Bnj2OVGWUdpSah2dvgEW');
    //     $smsApiUrl = env('SMS_API_URL', 'http://sms.alp-ts.com:8888/alp-sms-api');

    //     $curl = curl_init($smsApiUrl);
    //     curl_setopt($curl, CURLOPT_URL, $smsApiUrl);
    //     curl_setopt($curl, CURLOPT_POST, true);
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($curl, CURLOPT_HTTPHEADER, [
    //         "Content-Type: application/xml",
    //         "Accept: application/xml",
    //     ]);

    //     $data = "<?xml version='1.0' encoding='UTF-8'
    //               <smsRequest>
    //               <head>
    //               <templateid>" . $contant_id . "</templateid>
    //               <apikey>" . $sms_api_key . "</apikey>
    //               <message>" . $message . "</message>
    //               <unicode>0</unicode>
    //               <route>T</route>
    //               </head>
    //               <request>
    //               <number>" . $phone . "</number>
    //               </request>
    //               </smsRequest>";

    //     curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    //     curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    //     $resp = curl_exec($curl);
    //     curl_close($curl);

    //     return $resp;
    // }

    // 4SMS SMS provide (Mahesh)
    static private function singlePushSMS($phone, $contant_id, $message)
    {
        $smsApiUrl = trim(env('4SMS_API_URL'));
        $entityid = trim(env('4SMS_API_ENITYID'));
        
        // Ensure parameters are properly encoded
        $params = http_build_query([
            'uname' => 'scrapmate',
            'pwd' => 'scrapmate@123',
            'senderid' => 'SCRPMT',
            'to' => $phone,
            'msg' => $message,
            'route' => 'T',
            'peid' => $entityid,
            'tempid' => $contant_id,
        ]);

        $fullUrl = $smsApiUrl . '?' . $params;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $fullUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification (if needed)
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        
        $resp = curl_exec($curl);
        
        // Check for cURL errors
        if ($resp === false) {
            echo 'cURL Error: ' . curl_error($curl);
        }
        
        curl_close($curl);
        
        // print_r($resp);
        return $resp;
    }


    static private function singlePushSMS2($phone, $contant_id, $message)
    {
        $expire = strtotime("+1 minute");
        $signature = self::smsSignatureApi4($expire);
        $smsApiUrl = trim(env('4SMS_API_URL_NEW'));
        $entityid = trim(env('4SMS_API_ENITYID'));
        
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $smsApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query([
                'accessToken' => env('4SMS_API_TOKEN'),
                'expire' => $expire,
                'authSignature' => $signature,
                'route' => 'transactional',
                'smsHeader' => env('SMS_HEADER_CENTER_ID'),
                'messageContent' => $message,
                'recipients' => $phone,
                'entityId' => $entityid,
                'templateId' => $contant_id,
            ]),
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;

    }

    static private function smsSignatureApi4($expire) {
        $accessToken        = env('4SMS_API_TOKEN');
        $accessTokenKey     = env('4SMS_API_KEY');

        // Request For may vary eg. send-sms, send-sms-array, send-dynamic-sms, etc..
        $requestFor          = "send-sms";
        
        // MD5 algorithm is hash function producing a 128-bit hash value.
        $timeKey = md5($requestFor."sms@rits-v1.0".$expire);
        $timeAccessTokenKey = md5($accessToken.$timeKey);
        $signature = md5($timeAccessTokenKey.$accessTokenKey);

        return $signature;
    }
}
