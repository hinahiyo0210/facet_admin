<?php

date_default_timezone_set('Asia/Tokyo');

include(__DIR__ . "/conf.php");

class LOG
{
  // public $datetime;

  function __construct(){
    // $this->datetime = date('Y-m-d H:i:s');
  }
  
  function info($user, $text, $datetime)
  {
      
    global $log_path;

    $log_text = "[info] ".$datetime." <".$user."> ".$text."\n";
    
    file_put_contents($log_path, $log_text, FILE_APPEND);

  }
  function warn($user, $text, $datetime)
  {
      
    global $log_path;

    $log_text = "[warn] ".$datetime." <".$user."> ".$text."\n";
    
    file_put_contents($log_path, $log_text, FILE_APPEND);

  }
  function error($user, $text)
  {
    
    global $log_path;

    $log_text = "[error] ".$datetime." <".$user.">".$text."\n";
    
    file_put_contents($log_path, $log_text, FILE_APPEND);

  }
}