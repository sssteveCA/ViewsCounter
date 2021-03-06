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
    private $title; //table title column
    private $url; //table url column
    private $n_views; //table number of views column(total views of specific page)
    private $robots_list; //array that contains list of robots to avoid while view counting
    private $userAgent; 
    private $user_logged; //true if visitors is a logged user
    private $session_array; //array from $_SESSION that contains user visited pages
    private $errno; //error code
    private $error; //error message
    //path of log file;
    private static $logDir = ABSPATH.'wp-content/viewsCounterLog.txt'; 
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
            $this->robots_list = isset($args['robots_list']) ? $args['robots_list'] : array();
            $this->session_array = isset($args['session_array']) ? $args['session_array'] : array();
            $this->title = isset($args['title']) ? $args['title'] : null;
            $this->url = isset($args['url']) ? $args['url'] : null;
            $this->userAgent = isset($args['userAgent']) ? $args['userAgent'] : $_SERVER['HTTP_USER_AGENT'];
            $this->user_logged = isset($args['user_logged']) ? $args['user_logged'] : false;
        }
    }

    //check if table $this->table exists
    private function checkTable(){
        $exists = false;
        $this->query = <<<SQL
SHOW TABLES LIKE '{$this->table}';
SQL;
        $this->queries[] = $this->query;
        if($this->wpdb->get_var($this->query) == $this->table){
            $exists = true; //table exists
        }
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
    public function getRobotsList(){return $this->robots_list;}
    public function getUserAgent(){return $this->userAgent;}
    public function getSessionArray(){return $this->session_array;}
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
            case PageError::ISROBOT:
                $this->error = PageError::ISROBOT_MSG;
                break;
            case PageError::NOTCOUNTABLEVIEW:
                $this->error = PageError::NOTCOUNTABLEVIEW_MSG;
                break;
            default:
                $this->error = null;
                break;
        }
        return $this->error;
    }

    public function isLogged(){return $this->user_logged;}

    //check if visitor is a bot/crawler/spider
    private function isRobot(){
        $isRobot = false;
        $this->errno = 0;
        //$rList is the array in robotsList.php that contains list of robots to exclude from views counting
        if(isset($this->robots_list)){
            //file_put_contents(Page::$logDir,"funzione isRobot() di Page\r\n",FILE_APPEND);
            foreach($this->robots_list as $robot){
                //file_put_contents(Page::$logDir,"foreach robot => {$robot}\r\n",FILE_APPEND);
                $Ebot = preg_quote($robot,'/');
                //file_put_contents(Page::$logDir,"foreach Ebot => {$Ebot}\r\n",FILE_APPEND);
                if(preg_match('/'.$Ebot.'/i',$this->userAgent)){
                    //file_put_contents(Page::$logDir,"preg_match di {$Ebot} con {$this->userAgent}\r\n",FILE_APPEND);
                    $isRobot = true;
                    break;
                }
            }
        }//if(isset($rList)){
        else{
            $this->errno = PageError::NOROBOTLIST;
            file_put_contents(Page::$logDir,"Page isRobot() no robots list\r\n",FILE_APPEND);
        }
        return $isRobot;
    }

    public function setSessionArray($session_array){$this->session_array = $session_array;}
    public function setTable($table){$this->table = $table;}
    public function setLogged($user_logged){$this->user_logged = $user_logged;}

    //Check if visitors is not a rboto a guest or the page is not already visited in this session
    public function countableView(){
        $countable = false;
        $this->errno = 0;
        //file_put_contents(Page::$logDir,"Page countableViews()\r\n",FILE_APPEND);
        $robot = $this->isRobot();
        //file_put_contents(Page::$logDir,"Page countableViews() robots => {$robot}\r\n",FILE_APPEND);
        if(!$robot){
            //Visitors is not a crawler
            if(!$this->user_logged && !in_array($this->page_id,$this->session_array) && $this->page_id != 0)
            {
                //If user is not logged and the page isn't already visited in this session
                $insert = $this->insertRow();
                if($insert){
                    //file_put_contents(Page::$logDir,"Page countableViews() session_array prima => ".var_export($this->session_array,true)."\r\n",FILE_APPEND);
                    array_push($this->session_array,$this->page_id);
                    //file_put_contents(Page::$logDir,"Page countableViews() session_array dopo => ".var_export($this->session_array,true)."\r\n",FILE_APPEND);
                    $countable = true;
                }//if($insert){
            }//if(!$this->user_logged && !in_array($this->page_id,$this->session_array) && $this->page_id != 0){
            else{
                $this->errno = PageError::NOTCOUNTABLEVIEW;
            }
        }//if(!$this->isRobot()){
        else{
            $this->errno = PageError::ISROBOT;
        }
        return $countable;
    }


    /*Search in table the row with id $this->id and insert all the values in their respective properties*/
    private function setValuesById(){
        $ok = false;
        $this->errno = 0;
        $this->query = <<<SQL
SELECT * FROM `{$this->table}` WHERE `id` = {$this->id};
SQL;
        $this->queries[] = $this->query;
        $result = $this->wpdb->get_results($this->query,ARRAY_A);
        /* $log = "Pagina Query =>".$this->query."\r\n";
        $log .= var_export($result,true)."\r\n";
        file_put_contents(Page::$logDir,$log,FILE_APPEND); */
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
        return $ok;
    }

    /*Search in table the row with id $this->id and insert all the values in their respective properties*/
    private function setValuesByPageId(){
        $ok = false;
        $this->errno = 0;
        $this->query = <<<SQL
SELECT * FROM `{$this->table}` WHERE `page_id` = $this->page_id};
SQL;
        $this->queries[] = $this->query;
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
        return $ok;
    }

    /*When a site page is visited the first time a new row
     in the database with that page info is created or the number of views is increased*/
    private function insertRow(){
        $ok = false;
        $this->errno = 0;
        $this->errno = 0;
        $q = <<<SQL
INSERT INTO `{$this->table}` (`page_id`,`title`,`url`,`n_views`) 
VALUES('%d','%s','%s',1) 
ON DUPLICATE KEY UPDATE `n_views` = `n_views` + 1
SQL;
        $this->query = $this->wpdb->prepare($q,$this->page_id,$this->title,$this->url);
        $this->queries[] = $this->query;
        $insert = $this->wpdb->query($this->query);
        if($insert !== false)$ok = true;
        else{
            $this->errno = PageError::NOTWRITTEN;
        }
        /*$log = "Query => {$this->query}\r\n Errore => {$this->wpdb->last_error}\r\n";
        file_put_contents(Page::$logDir,$log,FILE_APPEND);*/
        return $ok;
    }//if($this->isRobot() === false)

}
?>