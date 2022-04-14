<?php

namespace ViewsCounter\Classes;

use ViewsCounter\Interfaces\PageError;
use ViewsCounter\Interfaces\Constants;

class Page implements PageError,Constants{

    private $wpdb; //Wordpress database handle
    private $query; //last SQL query sent from this class
    private $queries; //list of SQL queries sent
    private $table; //MySql table
    private $id; //table id column 
    private $page_id; //wordpress page id column
    private $page_id_get; 
    private $title; //table title column
    private $url; //table url column
    private $n_views; //table number of views column(total views of specific page)
    private $userAgent; 
    private $errno; //error code
    private $error; //error message
    //path of log file;
    private static $logDir = ABSPATH.'/wp-content/viewsCounterLog.txt'; 
    //unique fields array
    public static $fields = array('id','page_id_get');

    public function __construct($args){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = isset($args['table']) ? $args['table'] : $this->wpdb->prefix.Constants::TABLE;
        //check if this->table exists
        if($this->checkTable() === false){
            throw new \Exception("la tabella {$this->table} non esiste");
        }
        $this->id = isset($args['id']) ? $args['id'] : null;
        $this->page_id_get = isset($args['page_id_get']) ? $args['page_id_get'] : null;
        $this->userAgent = isset($args['userAgent']) ? $args['userAgent'] : $_SERVER['HTTP_USER_AGENT'];
        $this->query = "";
        $this->queries = array();
        $this->errno = 0;
        //if is not specified a field in the passed array, the table row is selected by his id
        if(!isset($args['field']))$args['field'] = Page::$fields[0];
        if(in_array($args['field'],Page::$fields) && isset($this->{$args['field']}))
        {
            //if the property specified in args['field'] exists do a SELECT query
            if($args['field'] == 'id')$set = $this->setValuesById();
            else if($args['field'] == 'page_id_get')$set = $this->setValuesByPageId();
        }
        //Don't send a SQL query
        else{
            $this->page_id = isset($args['page_id']) ? $args['page_id'] : null;
            $this->title = isset($args['title']) ? $args['title'] : null;
            $this->url = isset($args['url']) ? $args['url'] : null;
        }
    }

    //check if table $this->table exists
    private function checkTable(){
        $exists = false;
        $this->query = <<<SQL
SHOW TABLES LIKE '{$this->table}';
SQL;
        if($this->wpdb->get_var($this->query) == $this->table){
            $exists = true; //table exists
        }
        $this->queries[] = $this->query;
        return $exists;
    }

    public function getTable(){return $this->table;}
    public function getId(){return $this->id;}
    public function getPageId(){return $this->page_id;}
    public function getTitle(){return $this->title;}
    public function getUrl(){return $this->url;}
    public function getViews(){return $this->n_views;}
    public function getQuery(){return $this->query;}
    public function getQueries(){return $this->queries;}
    public function getUserAgent(){return $this->userAgent;}
    public function getErrno(){return $this->errno;}
    public function getError(){
        switch($this->errno){
            case PageError::NOTSETTED:
                $this->error = PageError::NOTSETTED_MSG;
                break;
            case PageError::NOTWRITTEN:
                $this->error = PageError::NOTWRITTEN_MSG;
                break;
            case PageError::NOROBOTLIST:
                $this->error = PageError::NOROBOTLIST_MSG;
                break;
            default:
                $this->error = null;
                break;
        }
        return $this->error;
    }

    //check if visitor is a bot/crawler/spider
    private function isRobot(){
        $robot = false;
        $this->errno = 0;
        //$rList is the array in robotsList.php that contains list of robots to exclude from views counting
        if(isset($rList)){
            $log = "funzione isRobot() di Page";
            file_put_contents(Page::$logDir,$log,FILE_APPEND);
            foreach($rList as $robot){
                $Ebot = preg_quote($robot,'/');
                if(preg_match('/'.$Ebot.'/i',$this->userAgent)){
                    $robot = true;
                    break;
                }
            }
        }//if(isset($rList)){
        else{
            $this->errno = PageError::NOROBOTLIST;
        }
        return $robot;
    }

    public function setTable($table){
        $this->table = $table;
    }

    /*Search in table the row with id $this->id and insert all the values in their respective properties*/
    private function setValuesById(){
        $ok = false;
        $this->errno = 0;
        $this->query = <<<SQL
SELECT * FROM `{$this->table}` WHERE `id` = {$this->id};
SQL;
        $result = $this->wpdb->get_results($this->query,ARRAY_A);
        $logDir = ABSPATH.'wp-content/plugins/viewsCounterClass/log.txt';
        $log = "Pagina Query =>".$this->query."\r\n";
        $log .= var_export($result,true)."\r\n";
        file_put_contents($logDir,$log,FILE_APPEND);
        if($result !== null){
            $this->page_id = $result[0]['page_id'];
            $this->title = $result[0]['title'];
            $this->url = $result[0]['url'];
            $this->n_views = $result[0]['n_views'];
            $ok = true;
        }
        else{
            $this->errno = PageError::NOTSETTED;
        }
        $this->queries[] = $this->query;
        return $ok;
    }

    /*Search in table the row with id $this->id and insert all the values in their respective properties*/
    private function setValuesByPageId(){
        $ok = false;
        $this->errno = 0;
        $this->query = <<<SQL
SELECT * FROM `{$this->table}` WHERE `page_id` = $this->page_id};
SQL;
        $result = $this->wpdb->get_results($this->query,ARRAY_A);
        if($result !== null){
            $this->id = $result['page_id'];
            $this->title = $result['title'];
            $this->url = $result['url'];
            $this->n_views = $result['n_views'];
            $ok = true;
        }
        else{
            $this->errno = PageError::NOTSETTED;
        }
        $this->queries[] = $this->query;
        return $ok;
    }

    /*When a site page is visited the first time a new row
     in the database with that page info is created or the number of views is increased*/
    public function InsertRow(){
        $ok = false;
        $this->errno = 0;
        if($this->isRobot() === false){
            $this->errno = 0;
            $q = <<<SQL
INSERT INTO `{$this->table}` (`page_id`,`title`,`url`,`n_views`) 
VALUES('%d','%s','%s',1) 
ON DUPLICATE KEY UPDATE `n_views` = `n_views` + 1
SQL;
        $this->query = $this->wpdb->prepare($q,$this->page_id,$this->title,$this->url);
        $insert = $this->wpdb->query($this->query);
        if($insert !== false)$ok = true;
        else{
            $this->errno = PageError::NOTWRITTEN;
        }
        $this->queries[] = $this->query;
        /*$log = "Query => {$this->query}\r\n Errore => {$this->wpdb->last_error}\r\n";
        file_put_contents(Page::$logDir,$log,FILE_APPEND);*/
        }
        return $ok;
    }//if($this->isRobot() === false)

}
?>