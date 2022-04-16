<?php

namespace ViewsCounter\Interfaces;

//This interface contains error code and error messages of RobotsList class
interface RobotsListError{
    //error codes
    const CURLERROR = 1; //Error in cURL HTTP request

    //error messages
    const CURLERROR_MSG = "C'è stato un errore con cUrl, durante l'esecuzione della richiesta HTTP";

}
?>