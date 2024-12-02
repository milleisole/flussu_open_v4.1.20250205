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
 * CLASS-NAME:       Autoload class
 * Special autoload class for flussuserver classes
 * -------------------------------------------------------*/

 
 /* ----------------- SECURITY -------------------------- */
$link_array = explode('/',__FILE__); if (count($link_array)<2) $link_array = explode('\\',__FILE__);
/* ----------------- SECURITY -------------------------- */

require_once __DIR__ . '/vendor/autoload.php';


/*
spl_autoload_register('theAutoLoader');


if (!isset ($dotenv)){
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ );
    $dotenv->load();
}

function theAutoLoader($className){

    $serverRoot=substr($_SERVER["PHP_SELF"],0,strpos($_SERVER["PHP_SELF"],"/src/"));
    // SERVER ROOT SE CALL DA WEB
    if (empty($serverRoot))
        $serverRoot =substr($_SERVER['DOCUMENT_ROOT'],0,strpos($_SERVER['DOCUMENT_ROOT'],"/webroot"));
    // SERVER ROOT SE CALL DA SYS
    if (empty($serverRoot) && isset($_SERVER["PWD"])){
        $p=strpos($_SERVER["PWD"],"/src/Flussu");
        if ($p>0)
            $serverRoot=substr($_SERVER["PWD"],0,$p);
    }
    if (isset($_SERVER['DOCUMENT_ROOT'])){
        //$path = $_SERVER['DOCUMENT_ROOT'];
        if (file_exists($serverRoot."/src/Flussu/General.php")){
            include_once($serverRoot."/src/Flussu/General.php");
        }
    }
    //$url= \General::getHttpHost().\General::getRequestUri();

    $className =str_replace("\\", "/", $className);
    $className =str_replace("src/Flussu/", "", $className);
    $className =str_replace("App/Flussu/", "", $className);
    $extension=".php";

    $fullPath=$serverRoot."/";
    if (file_exists($fullPath."src/Flussu/".$className.$extension)){
        include_once $fullPath."src/Flussu/".$className.$extension;
    } else if (file_exists($fullPath.$className.$extension)){
        include_once $fullPath.$className.$extension;
    } else if (file_exists($fullPath."src/Flussu/Flussuserver/".$className.$extension)){
        include_once $fullPath."src/Flussu/Flussuserver/".$className.$extension;
    } else if (file_exists($fullPath."src/Flussu/Api/V20/".$className.$extension)){
        include_once $fullPath."src/Flussu/Api/V20/".$className.$extension;
    } else if (file_exists($fullPath."src/Flussu/Api/".$className.$extension)){
        include_once $fullPath."src/Flussu/Api/".$className.$extension;
    } else {
        $notFound=true;
        $cn=explode("/",$className);
        if (count($cn)>1){
            $cn=$cn[count($cn)-1];
            if (file_exists($fullPath."src/Flussu/Api/".$cn.$extension)){
                include_once $fullPath."src/Flussu/Api/".$cn.$extension;
                $notFound=false;
            } 
            if ($notFound && file_exists($fullPath."src/Flussu/Api/V20/".$cn.$extension)) {
                include_once $fullPath."src/Flussu/Api/V20/".$cn.$extension;
                $notFound=false;
            }
        }
        if ($notFound){
            try{ 
                if (file_exists( __DIR__."/".$cn.$extension)) {
                    include_once __DIR__."/".$cn.$extension;
                    return true;
                }
            } catch (\Throwable $e){
                //echo $e->getMessage();
            }
            return false;
        }
    }
    return true;
}
        */
