<?php

namespace ViewsCounter\Interfaces;

////This interface contains error codes and error messages of Page class
interface PageError{
    //error codes
    const NOTSETTED = 1; //Required properties not setted
    const NOTWRITTEN = 2;  //Can't do write operation in database 
    const NOROBOTLIST = 3; //Robot list file not exixts
    const ISROBOT = 4; //User-agent is a robot/crawler/spider

    //error messages
    const NOTSETTED_MSG = "Le proprietà non sono state impostate";
    const NOTWRITTEN_MSG = "Operazione di scrittura non eseguita";
    const NOROBOTLIST_MSG = "Non è stata trovata la lista degli spider";
    const ISROBOT_MSG = "Lo User-Agent che ha visitato il sito è un robot";
}
?>