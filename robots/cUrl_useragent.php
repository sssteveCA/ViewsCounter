<?php

$msg = "";
$cUrl = curl_init();

curl_setopt($cUrl,CURLOPT_URL,"http://www.robotstxt.org/db.html");
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
    $inizioOl = strstr($list,'<ol>');
    if($inizioOl !== false){
        $contenutoOl = strstr($inizioOl,'</ol>',true);
        if($contenutoOl !== false){
            //file_put_contents('page.html',$contenutoOl,FILE_APPEND);
            $exp = '';
            $expQ = '/<li><a href=".+">(.+)<\/a><\/li>/i';
            //file_put_contents('page.html',$expQ."\r\n",FILE_APPEND);
            $robots = preg_match_all($expQ,$contenutoOl,$robotsList,PREG_PATTERN_ORDER);
            if($robots !== false){
                $file_content = "<?php\r\n".'$rList='.var_export($robotsList[1],true).';'."\r\n?>";
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