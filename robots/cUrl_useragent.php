<?php

require_once('../interfaces/constants.php');
require_once('../interfaces/robotsListError.php');
require_once('../classes/robotsList.php');

use ViewsCounter\Interfaces\Constants;
use ViewsCounter\Classes\RobotsList;

$msg = "";
$cUrl = curl_init();

curl_setopt($cUrl,CURLOPT_URL,Constants::URL_ROBOTS);
curl_setopt($cUrl,CURLOPT_RETURNTRANSFER,true);
curl_setopt($cUrl,CURLOPT_HEADER,false);
curl_setopt($cUrl,CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($cUrl,CURLOPT_TIMEOUT, 20);

$list = curl_exec($cUrl);
if($list === false){
    $msg = "Errore: ".curl_error($cUrl)."\r\nNumero: ".curl_errno($cUrl);
}
curl_close($cUrl);


if($list !== false){
    $olStart = strstr($list,'<ol>');
    if($olStart !== false){
        $olContent = strstr($olStart,'</ol>',true);
        if($olContent !== false){
            //file_put_contents('page.html',$olContent,FILE_APPEND);
            $expQ = '/<li><a href=".+">(.+)<\/a><\/li>/i';
            //file_put_contents('page.html',$expQ."\r\n",FILE_APPEND);
            $robots = preg_match_all($expQ,$olContent,$robotsList,PREG_PATTERN_ORDER);
            if($robots !== false){
                $file_content = "<?php\r\n".'$rList = '.var_export($robotsList[1],true).';'."\r\n?>";
                file_put_contents('robotList.php',$file_content);
                $msg = "Operazione completata con successo";
            }
            else{
                $msg = "Errore nell'espressione regolare {$expQ}";
            }
        }
        else{
            $msg = "Tag chiusura ol non trovato";
        }
    }
    else{
        $msg = "Tag 'ol' non trovato";
    }
}

echo $msg;

?>