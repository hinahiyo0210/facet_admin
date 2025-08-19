<?php

include(__DIR__ . "/conf.php");

class DB
{

  public $tenant;

  function __construct($tenant){
    $this->tenant = $tenant;
  }

  function pdo()
  {
    global $setting;
    global $hosts;

    $host = $hosts[$this->tenant];
    $dsn = "mysql:host={$host};dbname=ds;charset=utf8";

    try{
      $driver_option = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
      ];
      $pdo = new PDO($dsn,$setting["user"],$setting["password"],$driver_option);
    }catch(PDOException $error){
      header("Content-Type: application/json; charset=utf-8", true, 500);
      echo json_encode(["error" => ["type" => "server_error","message"=>$error->getMessage()]]);
      die();
    }

    return $pdo;
  }
}