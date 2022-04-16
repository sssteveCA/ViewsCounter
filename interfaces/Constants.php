<?php

namespace ViewsCounter\Interfaces;

interface Constants{
    const ROBOTS_FILE = '_robotsList.php'; //Array of robots list file
    const TABLE = 'cv_views'; //MySql table of the plugin
    const URL_ROBOTS = 'http://www.robotstxt.org/db.html'; //Where find robots list

    //Messages
    const MSG_CURLINFODESC = "Informazioni sull'ultima sessione cUrl";

    //Success messages
    const MSG_ROBOTSLISTOK = "L'array con la lista dei crawler è stata aggiornata";
}
?>