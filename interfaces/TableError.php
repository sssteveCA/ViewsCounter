<?php

namespace ViewsCounter\Interfaces;

//This interface contains error codes and error messages of Table class
interface TableError{
    //error codes
    const NOTSETTED = 1; //Required parameters not setted
    const NOTCREATED = 2; //Error during table creation
    const NOTREMOVED = 3; //Error while deleting the table
    //error messages
    const NOTSETTED_MSG = "I parametri richiesti non sono stati impostati";
    const NOTCREATED_MSG = "Errore durante la creazione della tabella";
    const NOTREMOVED_MSG = "Errore durante la cancellazione della tabella";
    
}
?>