<?php

namespace ViewsCounter\Interfaces;

//Error codes and messages for Counter class 
interface CounterError{
    //error codes
    const NORESULT = 1; //The query returned no result

    //error messages
    const NORESULT_MSG = "La query non ha restituito nessun risultato";
}
?>