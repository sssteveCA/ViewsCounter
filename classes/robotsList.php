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
    //Array of robots list file
    private static $file = Constants::ROBOTS_FILE;

    private $curl; //cURL handle
    private $curlOptions; //cURL options array
    private $curlR; //cURL string response
    private $curlInfo; //array of information of last cURL session
    private $robots; //list of all crawler finded
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
        $this->robots = array();
        $this->errno = 0;
        $this->error = null;
        $this->curlErrno = 0; //No cURL error code
        $this->curlError = ''; //Empty string if cUrl session has no errors
        $call = $this->cUrlCall();
        if($call){
            //cUrl returns a string response
            $parse = $this->parseResponse();
            if($parse){
                $this->writeToFile();
            }//if($parse){
        }//if($call){
    }//public function __construct()

    public function getCurlResponse(){return $this->curlR;}
    public function getCurlInfo(){return $this->curlInfo;}
    public function getCurlErrno(){return $this->curlErrno;}
    public function getCurlError(){return $this->curlError;}
    public function getErrno(){return $this->errno;}
    public function getError(){
        switch($this->errno){
            case RobotsListError::CURL:
                $this->error = RobotsListError::CURL_MSG;
                break;
            case RobotsListError::PARSE:
                $this->error = RobotsListError::PARSE_MSG;
                break;
            case RobotsListError::REGULAREXPRESSION:
                $this->error = RobotsListError::REGULAREXPRESSION_MSG;
                break;
            case RobotsListError::NOMATCHES:
                $this->error = RobotsListError::NOMATCHES_MSG;
                break;
            case RobotsListError::FILEWRITING:
                $this->error = RobotsListError::FILEWRITING_MSG;
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
            $this->errno = RobotsListError::CURL;
            $this->curlErrno = curl_errno($this->curl);
            $this->curlError = curl_error($this->curl);
        }
        $this->curlInfo = curl_getinfo($this->curl);
        curl_close($this->close);
        return $ok;
    }

    //Extract crawlers list from HTML
    private function parseResponse(){
        $ok = false; //true if
        //substring that start with '<ol>'
        $olStart = strstr((string)$this->curlR,'<ol>');
        if($olStart !== false){
            //Portion of olStart string that ends with </ol>
            $olContent = strstr($olStart,'</ol>',true);
            if($olContent !== false){
                $robots = preg_match_all(RobotsList::$expr,$olContent,$robotsList,PREG_PATTERN_ORDER);
                if($robots !== false){
                    if($robots > 0){
                        //At least one pattern matches
                        $this->robots = $robotsList[1];
                        $ok = true;
                    }//if($robots > 0){
                    else
                        $this->errno = RobotsListError::NOMATCHES;
                }//if($robots !== false){
                else
                    $this->errno = RobotsListError::REGULAREXPRESSION;
            }//if($olContent !== false){
            else
                $this->errno = RobotsListError::PARSE;
        }//if($olStart !== false){
        else
            $this->errno = RobotsListError::PARSE;
        return $ok;
    }

    //Put the array in a file
    private function writeToFile(){
        $ok = false; //true if array content is written successfully
        $file_content = "<?php\r\n".'$rList = '.var_export($this->robots,true).';'."\r\n?>";
        $put = file_put_contents(RobotsList::$file,$file_content);
        if($put !== false){
            //Data put on the file
            $ok = true;
        }
        else
            $this->errno = RobotsListError::FILEWRITING;
        return $ok;
    }

}

?>