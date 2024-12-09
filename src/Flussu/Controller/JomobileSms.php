<?php
/* --------------------------------------------------------------------*
 * Flussu v4.0.0 - Mille Isole SRL - Released under Apache License 2.0
 * --------------------------------------------------------------------*
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * --------------------------------------------------------------------*
 * CLASS-NAME:      Jo Mobile SMS provider
 * UPDATED DATE:    24.09.2023 - Aldus - Flussu v2.9
 *                  Class to handle sms send using JoMobile API service
 * UPDATED DATE:    10.05:2024 - Aldus - Flussu v3.0
 *                  Standardized response like SMS factor
 * -------------------------------------------------------*/
namespace Flussu\Controller;

use Auth;
use Session;
use Exception;

class JomobileSms 
{
    private $_apiKey="";
    //private $_myHandler=null;
    function __construct($apiKey) {
        $this->_apiKey=$apiKey;
    }
    function sendSms($senderName,$toNumber,$textMessage){


        $opts=[
            "token"=>$this->_apiKey,
            "phone"=>$toNumber,
            "senderID"=>trim($senderName),
            "text"=>trim($textMessage),
            "type"=>"sms",
            "lifetime"=>120,
            "delivery"=>"false"
        ];
        $res0=$this->sendRequest("https://api.jomobile.online/send", "GET", $opts);
        $res=explode(":",str_replace(["{","}"],["",""],$res0));
        $res[0]=str_replace('"','',$res[0]);
        $res[1]=str_replace('"','',$res[1]);
        $ret=["status"=>0,"message"=>"error","ticket"=>"","sent"=>0];
        
        if (count($res)>1){
            if ($res[0]=="error"){
                //errore
                if (isset($res[1]))
                    $ret["message"]=$res0;
            } else {
                if (strpos($res[0],$toNumber)!==false || strpos($toNumber,$res[0])!==false){
                    // ha spedito.
                    //"{"status":"1","message":"OK","ticket":"280570602","cost":"1","credits":"179","total":"1","sent":"1","blacklisted":"0","duplicated":"0","invalid":"0","npai":"0"}"
                    $ret["status"]=1;
                    $ret["message"]="OK";
                    $ret["ticket"]=$res[1];
                    $ret["sent"]=1;
                }

            }
        }
        return json_encode($ret);
    }

/**
     * @param  string  $url
     * @param  string  $method
     * @param  array   $opts
     * @return bool|string
     */
    private function sendRequest(string $url, string $method, array $opts = [])
    {
        $Url = $url . '?' . http_build_query($opts);
        $curl_info = [
            CURLOPT_URL            => $Url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];

        if ($opts == []) {
            unset($curl_info[CURLOPT_POSTFIELDS]);
        }
        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);
        $info     = curl_getinfo($curl);

        curl_close($curl);

        return $response;
    }



    private function sendAsV1ApiPost($senderName,$toNumber,$textMessage)
    {
        $env=[
            "number"=>[$toNumber],
            "senderID"=>trim($senderName),
            "text"=>trim($textMessage),
            "type"=>"sms",
            "lifetime"=>120,
            "delivery"=>"false"
        ];

        //bisogna richiedere il token api V1 / POST

        $jenv=json_encode([$env]);
        $curl_info = [
            CURLOPT_URL            => "https://restapi.jomobile.online/v1/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 6000,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => [""=>$jenv],
            CURLOPT_HTTPHEADER     => ["X-Access-Token: "=>$this->_apiKey]
        ];


        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);

        $info           = curl_getinfo($curl);


        curl_close($curl);

        return $response;
    }

}