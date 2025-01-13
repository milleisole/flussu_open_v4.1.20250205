<?php
/* --------------------------------------------------------------------*
 * Flussu v4.1.0 - Mille Isole SRL - Released under Apache License 2.0
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
 *
 *      This is the main entrance to the Flussu Server, a PHP script
 *      to handle all the requests to this server. 
 * 
 * --------------------------------------------------------------------*/

require_once __DIR__ . '/../vendor/autoload.php';

use Flussu\Controllers\FlussuController;
use Flussu\Controllers\ZapierController;
use Flussu\Controllers\VersionController;
use Flussu\Flussuserver\Request;
use Flussu\General;

// VERSION
$FlussuVersion="4.1.0.20240113";
$FVP=explode(".",$FlussuVersion);
$v=$FVP[0];
$m=$FVP[1];
$r=$FVP[2];

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load( );

if (!function_exists('config')) {
    function config($key, $default = null)
    {
        return Flussu\Config::get($key, $default);
    }
} 

if (isset($argv) && is_array($argv)){
    echo ("Flussu Server v".$_ENV['major'].".".$_ENV['minor'].".".$_ENV['release']."\n");
    if (count($argv)>2) {
        switch($argv[1]){
            case "-curt":
                die(General::curtatone(999,$argv[2])."\n");
            case "-iscu":
                die(General::isCurtatoned($argv[2])?"yes\n":"no\n");
            default:
                die ("Error:unknown command ".$argv[1]."\nuse: -curt | -iscu\n");
        }
        //die(json_encode($argv));
    } else {
        die ("Error:unknown command\nUse: php api.php -[cmd] [params]\n");
    }
}

if (strpos($_SERVER["SCRIPT_URL"],"license") || strpos($_SERVER["QUERY_STRING"],"license")!==false){
    $license = file_get_contents('../LICENSE.md');
    die("<html><head><title>Flussu Server License</title></head><body><p>".str_replace("<br><br>","</p><p>",str_replace("\n","<br>",htmlentities($license)))."</body></html>");
} else if ($_SERVER["SCRIPT_URL"]=="/favicon.ico"){
    die (file_get_contents(
        "favicon.ico"     
    ));
} else if ($_SERVER["SCRIPT_URL"]=="/checkversion" || $_SERVER["SCRIPT_URL"]=="/update"){
    $fc=new VersionController();
    die($fc->execCheck());
} else if ($_SERVER["SCRIPT_URL"]=="/"){
    header('Access-Control-Allow-Origin: *'); 
    header('Access-Control-Allow-Methods: *');
    header('Access-Control-Allow-Headers: *');
    header('Access-Control-Max-Age: 10');
    header('Access-Control-Expose-Headers: Content-Security-Policy, Location');
    header('Content-Type: application/json; charset=UTF-8');
    $V=$v.".".$m.".".$r;
    $hostname = gethostname();
    $fc=new VersionController();
    $dbv="v".$fc->getDbVersion();
    $srv=$_ENV["server"];
    die(json_encode(["host"=>$hostname,"server"=>$srv,"Flussu Open"=>$FlussuVersion,"v"=>$v,"m"=>$m,"r"=>$r,"db"=>$dbv,"pv"=>phpversion()]));
} else if ($_SERVER["SCRIPT_URL"]=="/notify"){
    /* 
        PHP Session is blocking asyncrhonous calls if you use the same session_id, so the
        notifications mechanism must be session-agnostic.
        The solution is to handle it here BEFORE we start the PHP Session.
        "notify.php"scrit, will handle the whole process and send back the notifications if any. 
    */
    if (isset($_GET["SID"])){
        include 'notify.php';
    } else {
        header('HTTP/1.0 403 Forbidden');
        die(\json_encode(["error"=>"403","message"=>"Unauthorized action"]));
    }
} else if (stripos($_SERVER["SCRIPT_URL"],"/wh/")===0){
    /*
    It's a WEB HOOK call, so we need to handle it here.
    The first part must be a Workflow-id
    If there is a second part, it must be a block id
    */
    try{
        $fc=new FlussuController();
        General::log("Webhook call: ".$_SERVER["SCRIPT_URL"]);
        $res=$fc->webhook($_SERVER["SCRIPT_URL"]);
        die($res);
    } catch(\Throwable $e){ 
        header('HTTP/1.0 500 Error');
        General::log("Webhook call error: ".json_encode( $e->getMessage()),true);
        die(\json_encode(["error"=>"500","message"=>"Webhook call error"]));
    }
} else {
    $apiPage=basename($_SERVER['REQUEST_URI']);
    if (strtolower(substr($apiPage,0,3))=="zap"){
        General::log("Extcall Zapier Controller: ".$apiPage." - ".$_SERVER["REQUEST_URI"]);
        $fc=new ZapierController();
    } else {
        General::log("Extcall Flussu Controller: ".$apiPage." - ".$_SERVER["REQUEST_URI"]);
        $fc=new FlussuController();
    }
    $req=new Request();
    $fc->apiCall($req,$apiPage);
}