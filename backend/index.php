<?php

date_default_timezone_set('Asia/Tokyo');

include(__DIR__ . "/conf/DB.php");
include(__DIR__ . "/conf/LOG.php");
include(__DIR__ . "/conf/conf.php");

$log = new LOG();

// ob_start();
// var_dump(json_decode(file_get_contents('php://input'), true));
// $dump = ob_get_contents();
// ob_end_clean();
// file_put_contents("./debug.log", $dump, FILE_APPEND);

preg_match('|'.dirname($_SERVER["SCRIPT_NAME"]).'/([\w%/-]*)|', $_SERVER["REQUEST_URI"], $matches);
$paths = explode('/',$matches[1]);
$file = array_shift($paths);

if ($file !== "basic") {

  $file_path = './controllers/'.$file.'.php';
  if(file_exists($file_path)){
    include($file_path);
    $class_name = ucfirst($file)."Controller";
    $method_name = strtolower($_SERVER["REQUEST_METHOD"]);
    $object = new $class_name();
    $response = json_encode($object->$method_name(...$paths));
    $response_code = $object->code ?? 200;
    header("Access-Control-Allow-Origin: *");
    header("Content-Type: application/json; charset=utf-8", true, $response_code);

    echo $response;
  }else{
    $log->error($_SERVER["PHP_AUTH_USER"], "指定されたパスが間違っています", date('Y-m-d H:i:s'));

    header("HTTP/1.1 404 Not Found");
    exit;
  }

} else {
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=utf-8", true, 200);

  $log->info($_SERVER["PHP_AUTH_USER"], "Basic認証通過", date('Y-m-d H:i:s'));

  echo $_SERVER["PHP_AUTH_USER"];
}

?>