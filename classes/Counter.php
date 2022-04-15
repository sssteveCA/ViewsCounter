<?php

namespace ViewsCounter\Classes;

use ViewsCounter\Interfaces\CounterError;

class Counter implements CounterError{
    private $table; //MySql table name
    private $total; //Sum of views of all pages
    private $query; //last MySql query sent by this class
    private $queries; //MySql queries list 
    private $wpdb; //Wordpress database handle
    private $errno; //error code
    private $error; //error message
    private $shortcode; //html shortcode of site total views
    //Log file path
    private static $logDir = ABSPATH.'wp-content/viewsCounterLog.txt'; 
    
    public function __construct($args = array()){
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = isset($args['table']) ? $args['table'] : $this->wpdb->prefix.'cv_views';
        if($this->checkTable() === false){
            throw new \Exception("la tabella {$this->table} non esiste");
        }
        $this->total = 0;
        $this->query = "";
        $this->errno = 0;
        $this->error = null;
        $this->queries = array();
        $this->shortcode = "";
    }

    //Check if $this->table table exists
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
    public function getTotal(){
        $this->query = <<<SQL
SELECT SUM(`n_views`) AS `total` FROM `{$this->table}`;
SQL;
        $this->total = $this->wpdb->get_var($this->query);
        if($this->total === null){
            //The query returned no result
            $this->errno = CounterError::NORESULT;
        }
        $this->queries[] = $this->query;
        return $this->total;
    }
    public function getQuery(){return $this->query;}
    public function getQueries(){return $this->queries;}
    public function getErrno(){return $this->errno;}
    public function getError(){
        switch($this->errno){
            case CounterError::NORESULT:
                $this->error = CounterError::NORESULT_MSG;
                break;
            default:
                $this->error = null;
                break;
        }
        return $this->error;
    }

    public function setTable($table){$this->table = $table;}


    //Returns a Page object if there is a row with id $id
    public function getPageById($id){
        $page = null;
        $this->errno = 0;
        $this->query = $this->wpdb->prepare("SELECT * FROM `{$this->table}` WHERE `id` = %d",$id);
        $pageData = $this->wpdb->get_results($this->query,ARRAY_A);
        $log = "";
        $log .= "pageData => ".var_export($pageData,true)."\r\n";
        //file_put_contents(Vc_contatore::$logDir,$log,FILE_APPEND);
        if($pageData !== null){
            $page = new Page($pageData[0]);
        }
        else{
            $this->errno = CounterError::NORESULT;
        }
        $this->queries[] = $this->query;
        return $page;
    }
    ////Returns a Page object if there is a row with page_id $page_id
    public function getPageByPageId($page_id){
        $page = null;
        $this->errno = 0;
        $this->query = $this->wpdb->prepare("SELECT * FROM `{$this->table}` WHERE `page_id` = %d",$page_id);
        $pageData = $this->wpdb->get_results($this->query,ARRAY_A);
        $log = "";
        $log .= "pageData => ".var_export($pageData,true)."\r\n";
        if($pageData !== null){
            $page = new Page($pageData[0]);
            $log .= "page => ".var_export($page,true)."\r\n";
        }
        else{
            $this->errno = CounterError::NORESULT;
        }
        //file_put_contents(Vc_contatore::$logDir,$log,FILE_APPEND);
        $this->queries[] = $this->query;
        return $page;
    }
    //returns an Array of Page objects that have title $title
    public function getPagesByTitle($title){
        $page = null;
        $this->errno = 0;
        $pages = array();
        $log = "";
        $this->query = <<<SQL
SELECT `id` FROM `{$this->table}` ORDER BY `id` ASC;
SQL;
        //returns the id list
        $ids = $this->wpdb->get_col($this->query);
        if(count($ids) > 0){
            //Create the Page object
            foreach($ids as $id){
                $aPage = array('id' => $id);
                $page = new Page($aPage);
                //if Page object has title = $title add it to the array
                if($page->getTitle() == $title){
                    $pages[] = $page;
                }
            }
        }
        else{
            $this->errno = Counter::NORESULT;
        }
        $log = "pages =>".var_export($pages,true)."\r\n";
        //file_put_contents(Vc_contatore::$logDir,$log,FILE_APPEND);
        $this->queries[] = $this->query;
        return $pages;
    }

    //return Page object that has url $url
    public function getPageByUrl($url){
        $page = null;
        $this->errno = 0;
        $this->query = $this->wpdb->prepare("SELECT * FROM `{$this->table}` WHERE `url` = '%s",$url);
        $pageData = $this->wpdb->get_results($this->query,ARRAY_A);
        file_put_contents(Counter::$logDir,"Counter. php getPage ByUrl pageData => ".var_export($pageData,true)."\r\n",FILE_APPEND);
        if($pageData != null){
            $page = new Page($pageData[0]);
        }
        else{
            $this->errno = CounterError::NORESULT;
        }
        $this->queries[] = $this->query;
        return $page;
    }
    //returns an array of Page objects based on the $views array options
    public function getPagesByViews($views){
        $pages = array();
        $page = null;
        $this->errno = 0;
        $this->query = <<<SQL
SELECT `id` FROM `{$this->table}` ORDER BY `id` ASC;
SQL;
        //returns the id list
        $ids = $this->wpdb->get_col($this->query);
        if(count($ids) > 0){
            foreach($ids as $id){
                $aPage = array('id' => $id);
                $page = new Page($aPage);
                if(!isset($views[1])){
                    if($views[0]['op'] == '>'){
                        if($page->getViews() > $views[0]['val']){
                            $pages[] = $page;
                        }
                    }
                    else if($views[0]['op'] == '<'){
                        if($page->getViews() < $views[0]['val']){
                            $pages[] = $page;
                        }  
                    }
                    else if($views[0]['op'] == '>='){
                        if($page->getViews() >= $views[0]['val']){
                            $pages[] = $page;
                        }  
                    }
                    else if($views[0]['op'] == '<='){
                        if($page->getViews() <= $views[0]['val']){
                            $pages[] = $page;
                        }  
                    }
                }// if(!isset($views[1]))
                else{
                    //> --- <
                    if($views[0]['op'] == '>' && $views[1]['op'] == '<'){
                        if($page->getViews() > $views[0]['val'] && $page->getViews() < $views[1]['val']){
                            $pages[] = $page;
                        }
                    }
                    //> --- <= 
                    else if($views[0]['op'] == '>' && $views[1]['op'] == '<='){
                        if($page->getViews() > $views[0]['val'] && $page->getViews() <= $views[1]['val']){
                            $pages[] = $page;
                        }
                    }
                    //>= --- <  
                    else if($views[0]['op'] == '>=' && $views[1]['op'] == '<') {
                        if($page->getViews() >= $views[0]['val'] && $page->getViews() < $views[1]['val']){
                            $pages[] = $page;
                        }
                    }
                    //>= --- <= 
                    else if($views[0]['op'] == '>=' && $views[1]['op'] == '<='){
                        if($page->getViews() >= $views[0]['val'] && $page->getViews() <= $views[1]['val']){
                            $pages[] = $page;
                        }
                    } 
                    //----<    >-----
                    else if($views[0]['op'] == '<' && $views[1]['op'] == '>'){
                        if($page->getViews() < $views[0]['val'] || $page->getViews() > $views[1]['val']){
                            $pages[] = $page;
                        }
                    }
                    //----<=   >----
                    else if($views[0]['op'] == '<=' && $views[1]['op'] == '>'){
                        if($page->getViews() <= $views[0]['val'] || $page->getViews() > $views[1]['val']){
                            $pages[] = $page;
                        }
                    }
                    //----<   >=----
                    else if($views[0]['op'] == '<' && $views[1]['op'] == '>='){
                        if($page->getViews() < $views[0]['val'] || $page->getViews() >= $views[1]['val']){
                            $pages[] = $page;
                        }
                    }
                    //----<=   >=----
                    else if($views[0]['op'] == '<=' && $views[1]['op'] == '>='){
                        if($page->getViews() <= $views[0]['val'] || $page->getViews() >= $views[1]['val']){
                            $pages[] = $page;
                        }
                    }
                }//else di if(!isset($views[1]))
            }//foreach($ids as $id)
            
        }//public function getPagesByViews($views)
        else{
            $this->errno = CounterError::NORESULT;
        }
        $this->queries[] = $this->query;
        return $pages;
    }// public function getPagesByViews($views){

    //Shortcode string to display total views of the entire site
    public function shortcode(){
        $this->errno = 0;
        $shortcode = "";
        $this->getTotal();
        if($this->errno == 0){
            $shortcode = "<p id=\"vc_totale\">Totale visualizzazioni: {$this->total}</p>";
        }
        $this->shortcode = $shortcode;
        return $this->shortcode;
    }
}

?>