<?php

namespace ViewsCounter\Classes;

use ViewsCounter\Classes\RobotsList as ClassesRobotsList;
use ViewsCounter\Interfaces\RobotsListError;
use ViewsCounter\Interfaces\Constants;

//This class is useful to fetch the robots list from a URL and parse to an array
class RobotsList implements Constants,RobotsListError{
    //URL to search robots list
    private static $url = Constants::URL_ROBOTS; 
    //Regular expression to extract the robots from html <ol> list
    private static $expr = '/<li><a href=".+">(.+)<\/a><\/li>/i';

    private $curl; //cURL handle
    private $curlOptions; //cURL options array
    private $curlR; //cURL string response
    private $curlInfo; //array of information of last cURL session
    private $errno; //error code
    private $error; //error message
    private $curlErrno; //error code of last cURL session
    private $curlError; //error message of last cURL session

    public function __construct()
    {
        $this->curlOptions = array(
            CURLOPT_URL => RobotsList::$url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20
        );
        $this->curl = null;
        $this->curlR = null;
        $this->curlInfo = false; //On failure this array is false
        $this->errno = 0;
        $this->error = null;
        $this->curlErrno = 0; //No cURL error code
        $this->curlError = ''; //Empty string if cUrl session has no errors
    }

    public function getCurlResponse(){return $this->curlR;}
    public function getCurlInfo(){return $this->curlInfo;}
    public function getCurlErrno(){return $this->curlErrno;}
    public function getCurlError(){return $this->curlError;}
    public function getErrno(){return $this->errno;}
    public function getError(){
        switch($this->errno){
            case RobotsListError::CURLERROR:
                $this->error = RobotsListError::CURLERROR_MSG;
                break;
            default:
                $this->error = null;
                break;
        }
        return $this->error;
    }

    //Initialize, set and Execute the cUrl session
    private function cUrlCall(){
        $ok = false; //true if cURL returns response successfully
        $this->curl = curl_init();
        curl_setopt_array($this->curl,$this->curlOptions);
        $this->curlR = curl_exec($this->curl);
        if($this->curlR !== false){
            //this->curlR contains the response
            $ok = true;
        }//if($this->curlR !== false){
        else{
            $this->errno = RobotsListError::CURLERROR;
            $this->curlErrno = curl_errno($this->curl);
            $this->curlError = curl_error($this->curl);
        }
        $this->curlInfo = curl_getinfo($this->curl);
        curl_close($this->close);
        return $ok;
    }


}

?>