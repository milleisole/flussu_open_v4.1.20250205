<?php
/**
 * --------------------------------------------------------------------*
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
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 */
/** 
 * --------------------------------------------------------------------*
 * CLASS-NAME:       SMS sender provider for SmsFactor API
 * --------------------------------------------------------------------*/
namespace Flussu\Api;

use Auth;
use Session;
use Exception;

/**
 * Provides functionality to send SMS messages using the SmsFactor API.
 */
class SmsFactor 
{
    /**
     * The API key used for authenticating requests to the SmsFactor service.
     * 
     * @var string
     */
    private $_apiKey="";
    //private $_myHandler=null;
    /**
     * Constructs a new instance of the SmsFactor service handler.
     * 
     * @param string $apiKey The API key for the SmsFactor service.
     */
    function __construct($apiKey) {
        $this->_apiKey=$apiKey;
    }
    /**
     * Sends an SMS message to a specified recipient.
     * 
     * @param string $senderName The name of the sender to display on the recipient's device.
     * @param string $toNumber The phone number of the recipient.
     * @param string $textMessage The message text to send.
     * @return bool|string Returns true on success, or an error message on failure.
     */
    function sendSms($senderName,$toNumber,$textMessage){
        $opts=[
            "token"=>$this->_apiKey,
            "to"=>$toNumber,
            "sender"=>trim($senderName),
            "text"=>trim($textMessage),
        ];
        return $this->sendRequest("https://api.smsfactor.com/send", "GET", $opts);
    }

    /**
     * Sends a request to the SmsFactor API.
     * 
     * This method is used internally to send requests to the SmsFactor API. It handles the
     * construction of the request, including setting the appropriate headers and formatting
     * the request parameters.
     * 
     * @param string $url The URL to send the request to.
     * @param string $method The HTTP method to use for the request (e.g., "GET", "POST").
     * @param array $opts The options to include in the request. This includes the API token, recipient number, sender name, and text message.
     * @return bool|string The response from the SmsFactor API or false on failure.
     */
    private function sendRequest(string $url, string $method, array $opts = [])
    {
        $Url = $url . '?' . http_build_query($opts);
        $curl_info = [
            CURLOPT_URL            => $Url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, $curl_info);
        $response = curl_exec($curl);

        $info= curl_getinfo($curl);
        if ($info['http_code']!=200){
            return false;
        }
        curl_close($curl);

        if (stripos($response,"XML")!==false){
            $xml = simplexml_load_string($response);
            $response = json_encode($xml);
        }

        return $response;
    }

}