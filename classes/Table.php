<?php

namespace ViewsCounter\Classes;

use ViewsCounter\Interfaces\TableError;
use ViewsCounter\Interfaces\Constants;

class Table implements TableError,Constants{
    private $wpdb; //Wordpress database handle
    private $table; //MySql table
    private $collate; //MySql table collation
    private $prefix; //Wordpress table prefix
    private $query; //Last SQL query sent
    private $queries; //SQL list
    private $errno; //error code
    private $error; //error message
    private static $logDir = ABSPATH.'wp-content/viewsCounterLog.txt'; 

    public function __construct($dati = array())
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = isset($dati['prefix']) ? $dati['prefix'] : $wpdb->prefix;
        $this->table = isset($dati['table']) ? $this->wpdb->prefix.$dati['table'] : $this->wpdb->prefix.Constants::TABLE;
        $this->collate = $this->wpdb->collate;
        /*if(!$this->checkTable()){
            throw new \Exception("La tabella {$this->table} non esiste");
        }*/
        $this->errno = 0;
        $this->error = null;
        $this->query = null;
        $this->queries = array();
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
    public function getPrefix(){return $this->prefix;}
    public function getCollate(){return $this->collate;}
    public function getCharset(){return $this->charset;}
    public function getQuery(){return $this->query;}
    public function getQueries(){return $this->queries;}
    public function getErrno(){return $this->errno;}
    public function getError(){
        switch($this->errno){
            case TableError::NOTSETTED:
                $this->error = TableError::NOTSETTED_MSG;
                break;
            case TableError::NOTCREATED:
                $this->error = TableError::NOTCREATED_MSG;
                break;
            case TableError::NOTREMOVED:
                $this->error = TableError::NOTREMOVED_MSG;
                break;
            default:
                $this->error = null;
                break;
        }
        return $this->error;
    }

    //Create the table or update the structure
    public function activation(){
        $activated = false;
        $this->errno = 0;
        $this->query = <<<SQL
CREATE TABLE `{$this->table}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `url` varchar(200) NOT NULL,
  `n_views` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_id` (`page_id`),
  UNIQUE KEY `url` (`url`)
) COLLATE {$this->collate};
SQL;
        $this->queries[] = $this->query;
        $log = dbDelta($this->query);
        file_put_contents(Table::$logDir,"Table DbDelta => ".var_export($log,true)."\r\n",FILE_APPEND);
        $activated = true;
        return $activated;
    }

    //Delete the table when plugin is uninstalled
    public function uninistall(){
        $uninstalled = false;
        $this->errno = 0;
        $this->query = <<<SQL
DROP TABLE `{$this->table}`;
SQL;
        $this->queries[] = $this->query;
        $delete = $this->wpdb->query($this->query);
        if($delete == true){
            $log = "Table uninistall tabella eliminata";
            file_put_contents(Table::$logDir,$log,FILE_APPEND);
            $uninstalled = true;
        }
        else{
            $log = "Table uninistall tabella non eliminata";
            file_put_contents(Table::$logDir,$log,FILE_APPEND);
            $this->errno = TableError::NOTREMOVED;
        }
        return $uninstalled;
    }
}
?>