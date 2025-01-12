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
 * CLASS-NAME:       Flussu API Controller
 * UPDATED DATE:     04.08.2022 - Aldus - Flussu v2.2
 *                   API calls handler
 * -------------------------------------------------------*/
namespace Flussu\Api;

use Auth;
use Session;

use App\Flussu\General;
use App\Flussu\Api\V20\Flow;
use App\Flussu\Api\V20\Stat;
use App\Flussu\Api\V20\Sess;
use App\Flussu\Api\V20\Conn;
use App\Flussu\Api\V20\Engine;
use App\Flussu\Flussuserver\Request;

use Log;

class FlussuController 
{
    public function apiCall(Request $request, $apiPage){
        header('Access-Control-Allow-Origin: *'); 
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Max-Age: 200');
        header('Access-Control-Expose-Headers: Content-Security-Policy, Location');

        session_start();

        $uid=0;
        $res="";
        $theFlussuUser=new \App\Flussu\Persons\User();
        if (is_array(explode("?",$apiPage)))
            $apiPage=explode("?",$apiPage)[0];

        error_reporting(0); 

        switch ($apiPage){
            case "vatid":
                $rq=$request["vatid"];
                $pic=new PartitaIvaController();
                $res=$pic->PICheck(substr($rq,0,2),substr($rq,2));
                var_dump($res);
                break;
            case "flussuconn":
            case "flussuconn.php":
                header('Content-Type: application/json; charset=UTF-8');
                $st=new Conn();
                $st->exec($request,$theFlussuUser);
                //return $res;

                break;
            case "flussueng":
            case "flussueng.php":
                header('Content-Type: application/json; charset=UTF-8');
                $st=new Engine();
                $rawdata = file_get_contents('php://input');
                $res=$st->exec($request,$rawdata);
                $RET=json_encode($res, JSON_HEX_QUOT || JSON_HEX_APOS ); 
                //return $res; 
                //$reees=response()->make($res, 200);
                //return $reees; 
                die($RET);
            case "statdata":
            case "statdata.php":
                header('Content-Type: application/json; charset=UTF-8');
                $st=new Stat();
                $rawdata = file_get_contents('php://input');
                $st->extCall($request,$rawdata);
                //$RET=json_encode($res); 
                //return $res; 
                //$reees=response()->make($res, 200);
                //return $reees; 
                //die($RET);
                break;
            case "zap":
                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache');

                die("DUNNO???");
            default:
                $cwid=$request["CWID"];
                $authKey="";
                if (!empty($cwid)){
                    $parts=explode("|",$cwid);
                    if (count($parts)==2 && strlen($parts[0])<25){
                        $authKey=substr($parts[1],0,-1);
                        $cwid=$parts[0]."]";
                    } else {
                        $cwid="[".$parts[1];
                        $authKey=substr($parts[0],1);
                    }
                } else 
                    $authKey=$request["auk"];

                $uid=General::getUserFromDateTimedApiKey($authKey);

                //echo "UID=".$uid;

                if ($uid>0){
                    $theFlussuUser=new \App\Flussu\Persons\User();
                    $theFlussuUser->load($uid);
                }

                //echo "User ID=".$theFlussuUser->getId();


                $C=$request["C"];
                if (!empty($C)){
                    $rawdata = file_get_contents('php://input');
                    $fl=new Flow();
                    $fl->exec($request,$theFlussuUser,$rawdata);
                } else {
                    if ($theFlussuUser->getId()>0){
                        switch ($apiPage){
                            case "flow":
                                //echo "FLOW";
                                $rawdata = file_get_contents('php://input');
                                $fl=new Flow();
                                $fl->exec($request,$theFlussuUser,$rawdata);
                                break;
                            case "sess":
                                //echo "SESS";
                                $st=new Sess();
                                $st->exec($request,$theFlussuUser,0);
                                break;
                            case "stat":
                                //echo "FLOW";
                                $st=new Stat();
                                $st->exec($request,$theFlussuUser,0);
                                break;
                            case "stat0":
                            case "stat1":
                            case "stat2":
                                $pNum=substr($apiPage,-1);
                                $st=new stat();
                                $st->exec($request,$theFlussuUser,$pNum);
                                break;
                        }
                    }
                    header('HTTP/1.0 403 Forbidden');
                    die(\json_encode(["error"=>"403","message"=>"Unauthorized action"]));
                }
        }

        error_reporting(E_ALL); 
    }
}