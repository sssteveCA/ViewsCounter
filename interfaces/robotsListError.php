<?php

namespace ViewsCounter\Interfaces;


//This interface contains error code and error messages of RobotsList class
interface RobotsListError{
    //error codes
    const CURL = 1; //Error in cURL HTTP request
    const PARSE = 2; //Structure of HTML is unexpected
    const REGULAREXPRESSION = 3; //Error in regular expression
    const NOMATCHES = 4; //No matches found with the given regular expression
    const FILEWRITING = 5; //Error while writing data on the file

    //error messages
    const CURL_MSG = "C'è stato un errore con cUrl, durante l'esecuzione della richiesta HTTP";
    const PARSE_MSG = "Il documento HTML è in un formato inatteso";
    const REGULAREXPRESSION_MSG = "Espressione regolare non valida";
    const NOMATCHES_MSG = "Nessuna corrispondenza trovata con l'espressione regolare fornita";
    const FILEWRITING_MSG = "Errore durante la scrittura dei dati sul file";
}
?>