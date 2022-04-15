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
require_once('interfaces/Constants.php');
require_once('interfaces/PageError.php');
require_once('interfaces/CounterError.php');
require_once('interfaces/TableError.php');
include_once('robots/robotsList.php');
require_once('classes/Page.php');
require_once('classes/Counter.php');
require_once('classes/Table.php');

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
    $log = "vc_count rList => ".var_export($rList,true)."\r\n";
    //file_put_contents($logDir,$log,FILE_APPEND);
    if(isset($rList)){
        $pageA['robots_list'] = $rList;
        $log = "vc_count robots_list esiste\r\n";
        file_put_contents($logDir,$log,FILE_APPEND);
    }
    else{
        $log = "vc_count robots_list non esiste\r\n";
        file_put_contents($logDir,$log,FILE_APPEND);
    }
    try{
        $page = new Page($pageA);
        $log = "vc_count User Agent => ".var_export($page->getUserAgent(),true)."\r\n";
        file_put_contents($logDir,$log,FILE_APPEND);
        $count = $page->countableView();
        if($count){
            $_SESSION['pages'][] = $page->getSessionArray();
        }//if($count){
        else{
            $error = $page->getError();
            $log = "vc_count count error => {$error}\r\n";
            file_put_contents($logDir,$log,FILE_APPEND);
        }
    }
    catch(Exception $e){
        $log .= "Errore Page: ".$e->getMessage()."\r\n";
        file_put_contents($logDir,$log,FILE_APPEND);
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
    ob_start();
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    if( !session_id() )
    {
        session_start();
        if(!isset($_SESSION['pages'])){
            $_SESSION['pages'] = array();
        }
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
            $log = $cont->getError()."\r\n";
            file_put_contents($logDir,$log,FILE_APPEND);
        }
    }
    catch(Exception $e){
        $log = $e->getMessage()."\r\n";
        file_put_contents($logDir,$log,FILE_APPEND);
    }
    return $str;
}
?>