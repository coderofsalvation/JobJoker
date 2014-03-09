<?
$workerJobJoker = dirname(__FILE__)."/../../libs/Worker.php";
$workerStub     = dirname(__FILE__)."/Worker.php";

if( is_file($workerJobJoker) ) define("STUBMODE",1); 

include_once( !defined("STUBMODE") ? workerJobJoker : $workerStub )


?>
