<?php

function jobCreate($job){
    if( is_array($job) || is_object($job) ){
        $job=json_encode($job);
    }
    require_once '../app/ThirdParty/Credis/Client.php';
    $predis = new \Credis_Client();
    $predis->rpush('queue.priority.normal', $job);
}