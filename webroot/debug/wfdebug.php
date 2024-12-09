<?php
include "_layout.php";
/* -------------------------------------------------------*
 * CLASS-NAME:       FlussuServer.debugger.page
 * UPDATED DATE:     04.08:2022 - Aldus - Flussu v2.2
 * UPDATED DATE:     25.05:2024 - Aldus - Flussu v3.0
 * -------------------------------------------------------*/

require __DIR__ . '/../../autoloader.php';

use Flussu\General;
$mustBeLogged = true;
$authLevel = 1;
$widd=null;
$wSess=null;
$wid="";
$uid=0;
$userId=0;
$isNew=false;
$cwid=General::getGetOrPost("cwid");
$sep=General::separateCwid($cwid);
if ($sep->wid!=""){
    $auk=$sep->auk;
    $wid=$sep->wid;
    $origwid=$wid;
    $uid=General::getUserFromDateTimedApiKey($auk);
    if ($uid>0){
        $theFlussuUser=new \Flussu\Persons\User();
        $theFlussuUser->load($uid);
        $userId=$theFlussuUser->getId();
        $wid=$sep->wid;
    }
}

// --------- DISPLAY ERRORS -------------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --------------------------------------------------------

$sid=General::getGetOrPost("sid");
$bid="No #BlockID";
$LNG=trim(strtoupper(General::getGetOrPost("lng")));
if ($LNG=="")
    $LNG="IT";
if ($sid!=""){
    //ripetizone
    $wSess=new \Flussu\Flussuserver\Session($sid);
    if (!$wSess->isExpired())
        $widd=$wSess->getWid();
    if ($widd==null) {
        //la sessione è sbagliata o scaduta!
        $sid="";
    } else {
        if ($widd!=$wid) $wid=$widd;
    }
}
if ($wid!="" && $sid=="" && $userId>0){
    // startup
    $isNew=true;
    $wSess=new \Flussu\Flussuserver\Session(null);
    $w_id=\Flussu\Flussuserver\NC\HandlerNC::WID2Wofoid($wid,$wSess);

    $IP=General::getCallerIPAddress();
    $UA=General::getCallerUserAgent();
    
    $wSess->createNew($w_id,$IP,$LNG,$UA,$userId,"",$origwid);

    $sid=$wSess->getId();
    foreach ($_GET as $key => $parm){
        if (strpos($key,"$")===0){
            $wSess->assignVars($key,$parm);
        }
    }


}
if (is_null($wSess)){
    echo "
        <body>   Can't start a session: nothing to do...   </body>
    </html>
    ";
} else {
    if ($wSess->isWorkflowInError()){
        echo "
            <body>   <strong>Error</strong> loading the workflow...    </body>
        </html>
        ";
    }
    elseif (!$wSess->isWorkflowActive()){
        echo "
            <body>   This workflow is <strong>inactive</strong>...   </body>
        </html>
        ";
    } else {
        if ($wSess->getlang()!="")
            $LNG=$wSess->getLang();
        $bid=$wSess->getBlockId();

        $wwork= new \Flussu\Flussuserver\Worker($wSess);
        $frmBid="";
        if (!$isNew)
            $frmBid=General::getPost("bid");

        // -----------------------------------------
        //  VERIFICA E GESTIONE FILE ATTACH
        //  se c'è un file viene caricato e salvato
        //  l'oggeto di ritorno contiene i dati
        //  la sessione ha registrato l'URL nella
        //  var [nomevar]_uri 
        // -----------------------------------------
        $wcmd= new \Flussu\Flussuserver\Command();
        $attachedFile=$wcmd->fileCheckExtract($wSess,$wwork,$frmBid,$_POST,$_FILES);
        // ------------------------------------------
        try {
            $hres=$wwork->execNextBlock($frmBid,$_POST);
        } catch(\Throwable $e){
            echo "<h3>ERROR:<br>Unhandled EXCEPTION:</h3><h3 style='background:red;color:white;font-weight:800'>EXCEPTION:".$e->getMessage()."</h3>";
        }
        
        $log=$wSess->getLog();
        $rowsLog="";
        $frmBid=$wwork->getBlockId();
        $wName=$wSess->getName();
        $wid=$wSess->getWid();
        $rowpos=0;

        $authApiCall="1234";//General::getDateTimedApiKeyFromUser($wSess->getId(),240);

        foreach($log as $row){
            $rowpos++;
            if (strpos($row["e_desc"],"\n")>0 && strpos($row["e_desc"],"EXCEPTION")===false){
                if (strpos($row["e_desc"],"SMTP")!==false || strpos($row["e_desc"],"sendEmail")!==false)
                {
                    $evt="EMAIL SEND";
                    if (strpos($row["e_desc"],"sendEmail")!==false)
                        $evt="EMAIL BUILD";
                    $rowsLog.=date('d-M H:i:s', strtotime($row["e_date"]))."<div id='O$rowpos' style='border-bottom:solid 1px silver;cursor:pointer;margin-bottom:5px' onclick='$(\"#$rowpos\").show();$(\"#O$rowpos\").hide();'>";
                    $rowsLog.="<strong style='font-size:1em'>[+] $evt</strong></div><div id='$rowpos' style='display:none;font-size:0.8em;border:inset 2px silver'><div id='O$rowpos' style='cursor:pointer' onclick='$(\"#$rowpos\").hide();$(\"#O$rowpos\").show();'>";
                    $rowsLog.="<strong style='font-size:1em'>[-]</strong></div>".$row["e_desc"]."</div>";
                } else {
                    //$rowsLog.="<hr>";
                    $rowsLog.="<div id='O$rowpos' style='border-bottom:solid 1px silver;cursor:pointer;margin-bottom:5px' onclick='$(\"#$rowpos\").show();$(\"#O$rowpos\").hide();'>";
                    $rowsLog.="<strong style='font-size:1em'>[+]</strong> DETAILS</div><div id='$rowpos' style='display:none;font-size:0.8em;border:inset 2px silver'><div id='O$rowpos' style='cursor:pointer' onclick='$(\"#$rowpos\").hide();$(\"#O$rowpos\").show();'>";
                    $rowsLog.="<strong style='font-size:1em'>[-]</strong><br>".date('d-M H:i:s', strtotime($row["e_date"]))."</div>".$row["e_desc"]."</div>";
                }
            } else {
                $rowsLog.=date('d-M H:i:s', strtotime($row["e_date"]))."\t";
                if (strpos($row["e_desc"],"EXCEPTION")!==false && strpos($row["e_desc"],"Undefined variable")!==false){
                    $uv=explode('=>',$row["e_desc"]);
                    foreach($uv as $urow){
                        if (strpos($urow,"Undefined variable")!==false){
                            $rowsLog.="<strong style='color:maroon'>".$row["e_type"]."&nbsp;WARNING: ".$urow."</strong>";    
                        }
                    }
                } else {
                    if (intval($row["e_type"])>0 || strpos($row["e_desc"],"EXCEPTION")!==false){
                        $rowsLog.="<strong>".$row["e_type"]."&nbsp;".$row["e_desc"]."</strong>";
                    }
                    else if (strpos($row["e_desc"],"lang:")>0){
                        $rowsLog.=$row["e_type"]."&nbsp;<strong>".$row["e_desc"]."</strong>";
                    } else {
                        $rowsLog.=$row["e_type"]."&nbsp;".$row["e_desc"];
                    }
                }
                $rowsLog.="<br>";
            }
        }
        $vars=explode("\n",$wSess->getLogWorkflowVars());
        $varsLog="<table cellpadding=1 cellspacing=2>";
        
        asort($vars);
        
        foreach ($vars as $var){
            if (!empty(trim($var))){
                $var2=str_replace("json_decode(","",$var);
                if ($var2!=$var){
                    if (substr($var2,-8)==",true);\r"){
                        $var2=str_replace("\\\"","\"",substr($var2,0,-8));
                    } else if (substr($var2,-3)==");\r"){
                        $var2=str_replace("\\\"","\"",substr($var2,0,-3));
                    }
                    $vv=explode("=",$var2);
                    if (substr($vv[1],0,1)==substr($vv[1],-1)){
                        $vv[1]=substr($vv[1],1,-1);
                    }
                    $var=$vv[0]."=".$vv[1];
                }
                $varsLog.="<tr><td align='right'>".str_replace("=","&nbsp;</td><td>",substr(trim($var2),0,-1))."</td></tr>";
            }
        }
        $varsLog.="</table>";

        $frmElms=$wwork->getExecElements();
        $ie=0;$ee=0;$ue=0;
        $errSt="";
        $errSt1="";
        $errSt2="";
        $errSt3="";
        if ($wSess->getStateIntError()) {$ie=1; $errSt1="background:yellow;color:red;font-weight:800";}
        if ($wSess->getStateExtError()) {$ee=1; $errSt2="background:yellow;color:red;font-weight:800";}
        if ($wSess->getStateUsrError()) {$ue=1; $errSt3="background:yellow;color:red;font-weight:800";}
        if ($errSt1!="" || $errSt2!="" || $errSt3!="")
            $errSt="background:red;color:white;font-weight:800";
        $sLangs=$wSess->getSuppLangs();
        $origwid=$wSess->getWholeWID();
        
        echo "
    <style>
        table, td {
            border: 1px solid black;
            border-collapse: collapse;
        }
        td {
            padding: 4px;
        }
    </style>
<!--<table width='100%' cellpadding=10 cellspacing=0><tr> <td width='55%'> -->
        <div class='row p-2 m-2'><div class='col-md-6'>
                <h2>$wName</h2>
                <style>
                    td{border-bottom:solid 1px silver;border-right:solid 1px silver}
                    table{border-top:solid 1px silver;border-left:solid 1px silver}
                </style>
                <table cellpadding=2 cellspacing=2 border=0 style='width:100%'>
                    <tr><td colspan=3 align='right' style='padding-right:8px;border-right:solid 1px #1e874c;background:#1e874c;color:#fff'>Workfow-id</td><td colspan=5 style='background:#1e874c;color:#fff;font-size:1em;font-weight:600'>$origwid<button style='margin-left:100px' onclick='editWf(\"";
                    echo str_replace("]","|",$origwid).$authApiCall; 
                    echo "]\")'>EDIT</button></td></tr>
                    <tr><td colspan=8 style='font-size:0.3em;border-right:none;border-left:solid 1px #fff'>&nbsp;</td></tr>
                    <tr><td style='background:#fefec0'>W $wid</td><td style='background:#fefec0'>INT</td><td style='background:#fefec0'>EXT</td><td style='background:#fefec0'>USR</td><td rowspan=2>&nbsp;</td><td style='background-color:#eee;border-bottom:solid 1px #eee' align='center'>Lang</td><td rowspan=2>&nbsp;</td><td>Sid</td></tr>
                    <tr><td style='background:#fefec0;$errSt'>ERR</td><td style='$errSt1'>$ie</td><td style='$errSt2'>$ee</td><td style='$errSt3'>$ue</td><td align='center' style='background-color:#eee'>
                    <select style='background-color:#fafaa0' onchange='changeLang(this);' id='lang'>";
            foreach ($sLangs as $Lang){
                echo ("<option ".(trim($LNG)==trim($Lang)?"selected":"").">$Lang</option>");
            }
            echo "</select>
                    </td><td style='font-size:0.8em'>$sid</td></tr>
                    <tr><td colspan=8 style='font-size:0.3em;border-right:none;border-left:solid 1px #fff'>&nbsp;</td></tr>
                    <tr><td colspan=3>start Bid</td><td colspan=5 style='font-size:0.8em'>$bid</td></tr>
                    <tr><td colspan=3>exec Bid</td><td colspan=5 style='font-size:0.8em'>$frmBid</td></tr>
                </table>
                <div style='margin-top:15px;padding:8px;border:solid 1px #333;height:280px;overflow:auto;background:#222;color:lime'>
                    <pre style='overflow:none;background:#222;color:#ccffb3'>$hres</pre>
                </div>
                <div style='border:inset 2px #f8f8f8;margin-top:15px;padding:5px;background:#eee;min-height:280px;'>
                <form method='post' enctype='multipart/form-data'>
                    <input type='hidden' name='sid' value='$sid'>
                    <input type='hidden' name='bid' value='$frmBid'>
        ";

        foreach ($frmElms as $Key => $Elm){
            $fe=explode("$",$Key);
            $fem="";
            if (is_array($fe) && count($fe)>1){
                $Key=$fe[0];
                $fem=$fe[1];
            }
            switch ($Key){
                case "L":
                    $se=Flussu\Flussuserver\Command::htmlSanitize($Elm[0]);
                    echo "<div class='".$Elm[1]["class"]."'>$se</div>";
                    break;
                case "M":
                    if (strpos($Elm[0],'flussu_qrc')===false){
                        $ext = strtolower(pathinfo($Elm[0], PATHINFO_EXTENSION));
                        switch ($ext){
                            case "jpg":
                            case "jpeg":
                            case "gif":
                            case "svg":
                            case "png":
                                echo "<img class='".$Elm[1]["class"]."' src='$Elm[0]'><br>";
                                break;
                            case "mp4":
                            case "avi":
                            case "mpg":
                            case "mpeg":
                                echo "<video class='".$Elm[1]["class"]."' controls>";
                                echo "<source src='$Elm[0]' type='video/$ext'>";
                                echo "Your browser does not support the video tag.";
                                echo "</video>";
                            break;
                            default:
                                echo "<a target='_blank' class='".$Elm[1]["class"]."' href='$Elm[0]'>download </a>";
                        }
                    } else {
                        echo "<img src='$Elm[0]'><br>";
                    }
                    break;
                case "A":
                    echo "<a class='".$Elm[1]["class"]."'  href='$Elm[0]'>Link</a>";
                    break;
                case "ITT":
                    $se=Flussu\Flussuserver\Command::htmlSanitize($Elm[0]);
                    if (!empty($se)){
                        echo "<div><label class='".$Elm[1]["class"]."' for='\$$fem'>$se</label></div>";
                    }
                    echo "<div style='padding-left:8px;margin-left:8px;'><input class='".$Elm[1]["class"]."' type='text' name='\$$fem' value=''></div>";
                    break;
                case "ITB":
                    echo "<div style='padding-left:108px;margin-left:108px;'><input class='".$Elm[1]["class"]."' type='submit' name='\$ex!$fem' value='$Elm[0]'></div>";
                    break;
                case "ITM":
                    $id_elm="img_".$fem.$bid;
                    echo "<!-- file input -->";
                    echo "  <div style='border:solid 1px silver;padding:5px;margin:5px;'>";
                    echo "      <!--input img--><p id='P$bid' class='".$Elm[1]["class"]."'>$Elm[0]</p>";
                    echo "      <div>";
                    echo "          <input id=\"img_$fem\" name=\"$$fem\" type='file' accpt=\"image/*\" class=\"I$bid $Elm[1]\" onchange=\"getPhoto(this,'$id_elm',true);\" ></input><br>";
                    echo "          <figure id='F$bid' class='about-picture' style='display:none;width:75%;height:60%;align-items:center;'>";
                    echo "              <img class='M$bid' id='$id_elm' src='' style='max-width:100%;max-height:100%'>";
                    echo "          </figure>";
                    echo "      </div>";
                    echo "  </div>";
                    break;
                case "ITS":
                    $opts=json_decode($Elm[0]);
                    $elmSel="<select class='$bid' name='$$fem'>";
                    foreach ($opts as $key => $value){
                        $elmSel.="<option value='@OPT[\"$key\",\"$value\"]'>$value</option>";    
                    }
                    $elmSel.="</select>";
                    echo "<div id='elminp$$fem'>$elmSel</div>";
                    break;
            }
        }
        $rowsLog=str_replace("\n","<br>",str_replace("ERROR","<strong style=\"color:red\">ERROR</strong>",$rowsLog));
        $rowsLog=str_replace("set lang","<strong style=\"color:#333\">LANG SETTING:</strong>",$rowsLog);
        $ending= "  
                </form></div>
            </div>
            <script>
                $(document).ready(function () {
                    $('#lang_chosen').hide();
                    $('#lang').show();
                    var element = document.getElementById('logdiv');
                    element.scrollTop = element.scrollHeight;
                });
                function swDebPanels(panelNameOn,panelName1Off,panelName2Off){
                    $('#'+panelName2Off).hide();
                    $('#'+panelName1Off).hide();
                    $('#'+panelNameOn).show();
                }
                function changeLang(elm){
                    uri=document.location.origin+document.location.pathname+'?wid=".$origwid."&lng='+elm.value+'&sid=".$sid."';
                    document.location=uri;
                }
                function editWf(wid){
                    server='".$_ENV["server"]."';
                    origin='".$_ENV["server"]."';
                    uri=\"https://editor.flussu.com/home/\"+wid+\"/edit?conn=\"+encodeURI(server+'/api/v2.0/flow')+\"&list=\"+encodeURI(origin+'/editor/list')+\"&prop=\"+encodeURI(origin+'/editor/prop?wid='+wid);
                    window.open(uri,'_blank');
                }
                var _getFileName='';
                function getPhoto(inp, prew, adj) {
                    if (inp.files && inp.files[0]) {
                        _getFileName=inp.files[0].name;
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            var pe=$('#'+prew);
                            pe.css('display','flex');
                            pe.attr('src',e.target.result);
                            pe[0].parentElement.style.display='flex';
                        };
                        reader.readAsDataURL(inp.files[0]);
                    }
                }
            </script>

<!--</td><td> -->

                <div class='col-md-6'><button onclick=\"swDebPanels('logdiv','vardiv','notdiv')\"><h2>LOG</h2></button>&nbsp;&nbsp;<button onclick=\"swDebPanels('vardiv','logdiv','notdiv')\"><h2>VARS</h2></button>&nbsp;&nbsp;<button onclick=\"swDebPanels('notdiv','vardiv','logdiv')\"><h2>NOTIFY</h2></button>
                    <div id='logdiv' style=\"background:#f8f8f8;padding:5px;font-size:0.8em;height:760px;width:100%;border:1px solid #ccc;overflow:auto;\">
                        ".$rowsLog."
                    </div>
                    <div id='vardiv' style=\"background:#f8f8f8;padding:5px;font-size:1.1em;height:760px;width:100%;border:1px solid #ccc;overflow:auto;display:none\">
                        ".$varsLog."
                    </div>
                    <div id='notdiv' style=\"background:#f8f8f8;padding:5px;font-size:1.1em;height:760px;width:100%;border:1px solid #ccc;overflow:auto;display:none\">
                        <iframe src='_notif_list.php?SID=".$sid."'  frameborder='0' style='overflow:hidden;height:100%;width:100%' height='100%' width='100%'></iframe>
                    </div>
                </div>
                </div>
            </body>
        </html>";
        echo $ending;    
    }
}
include "_endlayout.php";
?>
