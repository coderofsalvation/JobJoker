<?php
declare(ticks = 1);

if(!isset($argv[1])) {
    echo "Must pass a job id\n";
    exit;
}

include_once 'config.php';
include_once 'libs/restserver/RestClient.class.php';
if ( $config->auth  ){
  RestClient::$user = $config->user;
  RestClient::$password = $config->password;
}

/*
 * basic email function
 */
    
function sendMail($to, $subject, $message, $emailFrom = "", $emailFromName = ""){
  global $config;
  if( !strlen($emailFrom) ) $emailFrom = $config->maintainerEmailFrom;
  if( !strlen($emailFromName) ) $emailFromName = $config->maintainerEmailFrom;
  $headers   = array();
  $headers[] = "MIME-Version: 1.0";
  $headers[] = "Content-type: text/plain; charset=iso-8859-1";
  $headers[] = "From: {$emailFromName} <{$emailFrom}>";
  $headers[] = "Reply-To: {$emailFromName} <{$emailFrom}>";
  $headers[] = "Subject: {$subject}";
  $headers[] = "X-Mailer: PHP/".phpversion();
  mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * throw exceptions based on E_* error types
 */

function severityToString( $err_severity ){
  switch($err_severity)
  {
      case E_ERROR:             return "ERROR"; break;
      case E_WARNING:           return "WARNING"; break;
      case E_PARSE:             return "PARSE"; break;
      case E_NOTICE:            return "NOTICE"; break;
      case E_CORE_ERROR:        return "CORE_ERROR"; break;
      case E_CORE_WARNING:      return "CORE_WARNING"; break;
      case E_COMPILE_ERROR:     return "COMPILE_ERROR"; break;
      case E_COMPILE_WARNING:   return "COMPILE_WARNING"; break;
      case E_USER_ERROR:        return "USER_ERROR"; break;
      case E_USER_WARNING:      return "USER_WARNING"; break;
      case E_USER_NOTICE:       return "USER_NOTICE"; break;
      case E_STRICT:            return "STRICT"; break;
      case E_RECOVERABLE_ERROR: return "RECOVERABLE_ERROR"; break;
      case E_DEPRECATED:        return "DEPRECATED"; break;
      case E_USER_DEPRECATED:   return "USER_DEPRECATED"; break;
  }
}

function notifyFatal()
{
    $error = error_get_last();
    if ( $error["type"] == E_ERROR )
        notifyError( $error["type"], $error["message"], $error["file"], $error["line"], array() );
}

function notifyError($err_severity, $err_msg, $err_file, $err_line, array $err_context)
{
  global $log_url,$status_url;
  switch($err_severity)
  {
      case E_ERROR:             
      case E_CORE_ERROR:        
      case E_COMPILE_ERROR:     
      case E_USER_ERROR: RestClient::put($status_url,"error",null,null,"text/plain"); break;
  }
  $message = basename($err_file).":".sprintf("%-5s",$err_line).":".
             sprintf("%-8s",severityToString($err_severity)) . 
             ": {$err_msg}";
  RestClient::post($log_url,$message,null,null,"text/plain");
  return true;
}

// set the errorhandler
set_error_handler("notifyError", E_ALL);
register_shutdown_function( "notifyFatal" );

/*
 * lets go
 */

$request = RestClient::get($api."/jobs/".$argv[1]);
$data = json_decode($request->getResponse());
if(count($data->data) < 1) {
    echo "Bad Job description\n";
    exit;
}

// get worker data
RestClient::put($api."/jobs/".$argv[1]."/pid",getmypid(),null,null,"text/plain");
$work = $data->data[0];
$class = $work->worker ;
$parameters = (object) json_decode($work->parameters);
$parameters->_scheduler = $work->scheduler;
$parameters->_id = $work->id;
$parameters->_api = $api;
$log_url    = $api."/jobs/".$work->id."/log";
$status_url = $api."/jobs/".$work->id."/status";

if( $work->status == 'error' || ($work->scheduler == "crontab" && $work->status == "stop" ) ) 
  exit(0); // dont repeat errors or cheat on crontab 

// try including worker file
$file  = "workers_files/".$class.".php";

if( !file_exists($file) ){
  RestClient::put($status_url,"error",null,null,"text/plain");
  RestClient::post($log_url,"could not include \"{$file}\"..aborting",null,null,"text/plain");
  exit(1);
}else include_once($file);

// try creating class
if( !class_exists($class) ){
  RestClient::put($status_url,"error",null,null,"text/plain");
  RestClient::post($log_url,"could not create \"{$class}\"..aborting",null,null,"text/plain");
  exit(1);
}

$worker = new $class ;
$worker->setParameters($parameters);

if(function_exists("pcntl_signal")) {
    pcntl_signal(SIGTERM, array($worker,'stop'));
}

$worker->start();

?>
