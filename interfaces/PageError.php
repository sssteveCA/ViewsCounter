<?php

namespace ViewsCounter\Interfaces;

////This interface contains error codes and error messages of Page class
interface PageError{
    //error codes
    const NOTSETTED = 1;
    const NOTWRITTEN = 2;
    const NOROBOTLIST = 3;

    //error messages
    const NOTSETTED_MSG = "Le proprietà non sono state impostate";
    const NOTWRITTEN_MSG = "Operazione di scrittura non eseguita";
    const NOROBOTLIST_MSG = "Non è stata trovata la lista degli spider";
}
?>