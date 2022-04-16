<?php
/**
 * Plugin name: Views Counter
 * Description: This plugin counts the views of every page of the site
 * Version: 0.1
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Stefano
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */



require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
require_once('interfaces/constants.php');
require_once('interfaces/pageError.php');
require_once('interfaces/counterError.php');
require_once('interfaces/tableError.php');
include_once('robots/robotsList.php');
require_once('classes/page.php');
require_once('classes/counter.php');
require_once('classes/table.php');

use ViewsCounter\Interfaces\Constants;
use ViewsCounter\Classes\Counter;
use ViewsCounter\Classes\Page;
use ViewsCounter\Classes\Table;

global $logDir;
$logDir = ABSPATH.'/wp-content/viewsCounterLog.txt';

add_action('wp_footer','vc_count');
function vc_count(){
    global $logDir,$post,$rList;
    $url = $_SERVER['REQUEST_URI'];
    $pageA = array(
        'page_id' => $post->ID,
        'session_array' => $_SESSION['pages'],
        'titolo' => $post->post_title,
        'url' => $url,
        'user_logged' => is_user_logged_in()    
    );
    file_put_contents($logDir,"vc_count pageA => ".var_export($pageA,true)."\r\n",FILE_APPEND);
    //file_put_contents($logDir,"vc_count SESSION prima => ".var_export($_SESSION['pages'],true)."\r\n",FILE_APPEND);
    //file_put_contents($logDir, "vc_count rList => ".var_export($rList,true)."\r\n",FILE_APPEND);
    if(isset($rList)){
        $pageA['robots_list'] = $rList;
        file_put_contents($logDir,"vc_count robots_list esiste\r\n",FILE_APPEND);
    }
    else{
        file_put_contents($logDir,"vc_count robots_list non esiste\r\n",FILE_APPEND);
    }
    try{
        $page = new Page($pageA);
        //file_put_contents($logDir,"vc_count User Agent => ".var_export($page->getUserAgent(),true)."\r\n",FILE_APPEND);
        $count = $page->countableView();
        if($count){
            $_SESSION['pages'] = $page->getSessionArray();
            //file_put_contents($logDir,"vc_count SESSION dopo => ".var_export($_SESSION['pages'],true)."\r\n",FILE_APPEND);
        }//if($count){
        else{
            $error = $page->getError();
            $log = "vc_count count error => {$error}\r\n";
            file_put_contents($logDir,"vc_count count error => {$error}\r\n",FILE_APPEND);
        }
    }
    catch(Exception $e){
        file_put_contents($logDir,"Errore Page: ".$e->getMessage()."\r\n",FILE_APPEND);
    } 
}

register_activation_hook(__FILE__,'vc_create_table');
//crea la tabella quando il plugin viene attivato
function vc_create_table(){
    $table = new Table();
    $create = $table->activation();
}


register_uninstall_hook(__FILE__,'vc_delete_table');
function vc_delete_table(){
    $table = new Table();
    $delete = $table->uninistall();
    if($delete !== true){
        //Error while removing the table
        $error = $table->getError();
        die($error);
    }
}

add_action('init', 'vc_register_my_session');
function vc_register_my_session()
{
    global $logDir;
    ob_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    session_start();
    $session_id = session_id();
    if( !$session_id )
    {
        file_put_contents($logDir,"vc_register_my_session() session id non esiste\r\n",FILE_APPEND);
        if(!isset($_SESSION['pages'])){
            $_SESSION['pages'] = array();
        }
    }
    else{
        file_put_contents($logDir,"vc_register_my_session() session id esiste\r\n",FILE_APPEND);
        file_put_contents($logDir,"vc_register_my_session() session id {$session_id}\r\n",FILE_APPEND);
    }
}

add_shortcode('vc_total_views','vc_total');
function vc_total(){
    global $logDir;
    $str = "";
    try{
        $cont = new Counter();
        //get html shortcode 
        $str = $cont->shortcode();
        if($cont->getErrno() != 0){
            file_put_contents($logDir,$cont->getError()."\r\n",FILE_APPEND);
        }
    }
    catch(Exception $e){
        file_put_contents($logDir,$e->getMessage()."\r\n",FILE_APPEND);
    }
    return $str;
}
?>