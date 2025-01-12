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
 * BEAN-NAME:        Databroker.bean
 * WRITTEN DATE:     09.04.2020 - Aldus
 * CLASS DIR:        D:\xampp\htdocs\aldus/classes/beans
 * FOR MYSQL TABLE:  Undefined
 * FOR MYSQL DB:     aldus
 * VERSION REL.:     4.1.0 20250113 
 * UPDATE DATE:      12.01:2025 
 * -------------------------------------------------------*/
namespace Flussu\Beans;

use PDO;
use Flussu\General;

class Databroker extends Dbh
{
    private $_opLog=""; // FOR DEBUG PURPOSES

    // ATTRIBUTES
    //----------------------
    private $searchData=null;
    private $sendData;  
    private $_lastError=null;

    private $isDebug=false;

    // File CONSTRUCTOR
    //----------------------
    public function __construct (bool $debug)  {
        $this->_opLog=date("D M d, Y H:i:s v")." Created new File.Bean;\r\n";
        if ($debug==true) $this->isDebug=true;
//        $this->clear();
    }

  // Data GETTERS
  //----------------------
    public function getsearchData()	{if (is_null($this->searchData)) return ""; else return $this->searchData;}
    public function getfoundRows()      {
        if (!isset($this->sendData) || is_null($this->sendData))
            return array(); 
        else 
            return $this->sendData;
    }
    public function getisActive()       {return true;} // FUTURE IMPLEMENTATION

  // Data SETTERS
  //----------------------
    public function setsearchData($val)    {
        // Anti HACKERS
        try{
            if (!isset($val)) {
                $this->searchData=null;
            }
            elseif (!is_array($val)) {
                $this->searchData=array($val);
                //echo ("set searchdata array\r\n");
	        } else {
                $this->searchData=$val;
                //echo ("set searchdata a value:".$val."\r\n");
            }
            return true;
        } catch (\Throwable $e){
            $this->_lastError=$e;
            return false;
        }
    }

    public function loadData($sqlString, $transactional=false){
        // Anti HACKERS
        try{
            return $this->exec($sqlString, $transactional);
        } catch (\Throwable $e){
            $this->_lastError=$e;
            // echo "suca";
            //die ($e->getMessage());
            //echo ("SQL:".$sqlString);
            //echo ("MSG:".$e->getMessage());
        }
        return false;
    }

    // Data SELECT METHOD
    //----------------------
    private function exec($sqlString, $transactional=false) {
        $this->_opLog.=date("H:i:s v")." DataBroker EXEC SQL:\r\n\t$sqlString\r\n\t";
//        $stmt = $this->connect(true)->prepare($sqlString);
        $stmt = $this->connect($transactional)->prepare($sqlString);
        if (isset($this->searchData)){//} && !is_null($this->searchData) && is_array($this->searchData)){
            $rres=false;
            try{$rres=$stmt->execute($this->searchData);}
            catch(\Throwable $e){
                //die ($sqlString."\r\n - - "."[DataBroker EXECUTE1 W/Parameters () ERROR:".implode($stmt->errorInfo())." ".$e->getMessage()."]");
                $this->_lastError=$e;
                $rres=false;
            }
            if(!$rres){
                $this->_lastError=$stmt->errorInfo();
                $lastErr2="W/Parameters () ERROR:".implode($stmt->errorInfo())."]";

                //die ($sqlString."\r\n - - "."[DataBroker EXECUTE W/Parameters () ERROR:".implode($stmt->errorInfo())."]");
                General::addRowLog("[DataBroker EXECUTE2 ".$lastErr2);
                $this->_opLog.="EXECUTE3 ".$lastErr2.";\r\n";
                if ($transactional) $this->rollBack();
                return false;
            }
        } else {
            $rres=false;
            try{
                $rres=$stmt->execute();
            }
            catch(\Throwable $e){
                //die ($sqlString."\r\n - - "."[DataBroker EXECUTE1 W/Parameters () ERROR:".implode($stmt->errorInfo())." ".$e->getMessage()."]");
                $this->_lastError=$e;
                $rres=false;
            }
            if (!$rres) {
                $this->_lastError=$stmt->errorInfo();
                General::addRowLog("[DataBroker EXECUTE4 ERROR:".implode($stmt->errorInfo())."]");
                $this->_opLog.="EXECUTE5 ERROR:".implode($stmt->errorInfo()).";\r\n";
                if ($transactional) $this->rollBack();
                return false;
            }
        }
        $row = $stmt->fetchall();
        if ($transactional)
            $this->commit();
        if (is_array($row)){
          $this->sendData=$row;
          return true;
        } /*else {*/
          $this->sendData=array();
          return true;
        /*}
        return false;*/
    }

    // Mult data INSERT
    //----------------------
    private $_mStmt;
    private $tBbh;
    public function prepareMultExecs(){
        $this->tBbh=new Dbh();
        $this->_opLog.=date("H:i:s v")." DataBroker START MULTIPLE TRANSACTIONAL INSERT\r\n\t";
        $this->_mStmt = $this->tBbh->connect(true);
    }

    public function execMultExecs($sqlString,$paramsArr){
        $res=true;
        try{
            $this->_opLog.=date("H:i:s v")." DataBroker EXEC SQL MULT INSERT\r\n\t";
            $this->_mStmt=$this->_mStmt->prepare($sqlString);
            foreach ($paramsArr as $parms){
                try{$res=$this->_mStmt->execute($parms);}
                catch(\Throwable $e){
                    $this->_lastError=$this->_mStmt->errorInfo();
                    General::addRowLog("[DataBroker MULT_EXECUTE W/Parameters () ERROR:".implode($this->_mStmt->errorInfo())."]");
                }
            }
        }
        catch(\Throwable $e){
            $this->_lastError=$this->_mStmt->errorInfo();
            General::addRowLog("[DataBroker MULT_EXECUTE W/Parameters () ERROR:".implode($this->_mStmt->errorInfo())."]");
            return false;
        }
        return $res;
    }

    public function closeMultExecs(){
        $this->_opLog.=date("H:i:s v")." DataBroker START MULTIPLE TRANSACTIONAL INSERT\r\n\t";
        $this->commit($this->_mStmt,true);
        $this->tBbh=null;
    }

    public function multDataInsert($sqlString,$paramsArr){
        $res=true;
        $this->_opLog.=date("H:i:s v")." DataBroker START MULT INSERT\r\n\t";
        $stmt = $this->connect(true)->prepare($sqlString);
        foreach ($paramsArr as $parms){
            try{$res=$stmt->execute($parms);}
            catch(\Throwable $e){
                $this->_lastError=$stmt->errorInfo();
                General::addRowLog("[DataBroker EXECUTE2 W/Parameters () ERROR:".implode($stmt->errorInfo())."]");
                //$res=false;
                //break;
            }
        }
         $this->commit();
    }

    public function transExecs($sqlArr){
        $res=true;
        try{
            $this->_mStmt = $this->connect(true);
            $this->_opLog.=date("H:i:s v")." DataBroker EXEC SQL TRANS-EXEC\r\n\t";
            $this->_mStmt->beginTransaction();
            
            foreach ($sqlArr as $sqlCmd){
                $SQL=$sqlCmd["SQL"];
                $PRM=$sqlCmd["PRM"];
                $eStmt=$this->_mStmt->prepare($SQL);
                //$this->_mStmt->prepare($SQL);
                try{$res.="|".$eStmt->execute($PRM);}
                catch(\Throwable $e){
                    $this->_lastError=$this->_mStmt->errorInfo();
                    General::addRowLog("[DataBroker EXEC SQL TRANS-EXEC W/Parameters () ERROR:".implode($this->_mStmt->errorInfo())."]");
                }
            }
            $res.=$this->_mStmt->commit();
        }catch(\Throwable $e){
            $this->_lastError=$this->_mStmt->errorInfo();
            General::addRowLog("[DataBroker EXEC SQL TRANS-EXEC W/Parameters () ERROR:".implode($this->_mStmt->errorInfo())."]");
            //$res=false;
            //break;
        }
        return $res;
    }


    function getLastId(){
        return $this->getLastInsertId();
    }
    // Data DEBUG LOG
    //----------------------
    public function getLog(){
        return $this->_opLog;
    }
    // Operation DEBUG ERR
    //----------------------
    public function getError(){
        return $this->_lastError;
    }
}
