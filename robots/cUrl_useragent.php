<?php

require_once('../interfaces/constants.php');
require_once('../interfaces/robotsListError.php');
require_once('../classes/robotsList.php');

use ViewsCounter\Interfaces\Constants;
use ViewsCounter\Classes\RobotsList;
use ViewsCounter\Interfaces\RobotsListError;

$msg = "";

/*Execute operations to update the crawler list*/
$robotsList = new RobotsList();
$errno = $robotsList->getErrno();
if($errno == 0){
    //No error
    $msg .= Constants::MSG_ROBOTSLISTOK."<br><br><br><br>";
}//if($errno == 0){
else{
    $msg .= $robotsList->getError()."<br>";
    if($errno == RobotsListError::CURL){
        $curlErrno = $robotsList->getCurlErrno();
        $curlError = $robotsList->getCurlError();
        $msg .= "Codice errore: {$curlErrno}<br>Messaggio di errore: {$curlError}<br><br><br><br>";
    }
}//else di if($errno == 0){

$curlInfo = $robotsList->getCurlInfo();
if($curlInfo !== false){
    $msg .= Constants::MSG_CURLINFODESC."<br><br>";
  /*last cUrl session info*/ 
  foreach($curlInfo as $option => $value){
      if(!is_array($value)){
        $msg .= "{$option}: {$value}<br>";
      }
  } 
}

echo $msg;

?>