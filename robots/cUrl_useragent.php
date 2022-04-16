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
    $msg .= Constants::MSG_ROBOTSLISTOK."\r\n";
}//if($errno == 0){
else{
    $msg .= $robotsList->getError()."\r\n";
    if($errno == RobotsListError::CURL){
        $curlErrno = $robotsList->getCurlErrno();
        $curlError = $robotsList->getCurlError();
        $msg .= "Codice errore: {$curlErrno}\r\nMessaggio di errore: {$curlError}\r\n";
    }
}//else di if($errno == 0){

$curlInfo = $robotsList->getCurlInfo();
if($curlInfo !== false){
  /*last cUrl session info*/ 
  foreach($curlInfo as $option => $value){
      $msg .= "{$option}: {$value}\r\n";
  } 
}

echo $msg;

?>