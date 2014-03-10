<?php

include_once 'restserver/RestClient.class.php';

define("DONE",'done');
define("ERROR",'error');
define("ACTIVE",'active');
define("STOP",'stop');
define("IDLE",'idle');

abstract class Worker {
    private $parameters ;
    private $active = true;

    public function __construct()  {
    }

    public function setParameters($parameters){
        $this->parameters = $parameters ;
        return $this;
    }

    public function getParameter($parameter) {
        return $this->parameters->$parameter;
    }

    public function start() {
        global $config;
        $this->log('STARTED');
        $this->active = true;
        $this->setStartTime(time());
        try {
            $this->setStatus('active');
            switch( $this->getParameter("_scheduler") ){ // singleshot or run forever?
              case "none":    $this->run(); $this->setStatus('done'); break;
              case "crontab": $this->run(); $this->setStatus('idle'); break;
              case "repeat":  while( $this->isActive() ){ 
                                $this->setStatus('active');
                                $this->run(); 
                                $this->setStopTime(time());
                                sleep( $this->getParameter("repeat_sleep_seconds") ); 
                              }; 
                              break;
            }
        } catch (Exception $e) {
            $this->setStatus('error');
            $this->log("EXCEPTION");
            $this->log($e->getFile()." at ".$e->getLine());
            $this->log($e->getMessage());
            $this->log($e->getTraceAsString());
            foreach( $config->maintainerEmails as $to ) 
              sendMail( $to, 
                        "uncaught exception: ".$e->getMessage(), 
                        basename($e->getFile()).":".$e->getLine()." ".$e->getMessage()."\n\n".$e->getTraceAsString(),
                        $config->maintainerEmailFrom,
                        $config->maintainerEmailFromName
                      );
        }
        $this->active = false;
        $this->setStopTime(time());
        $this->log('ENDED');
    }

    public function stop() {
        if( !$this->active ) return;
        $this->active = false;
        $this->log('STOPPED');
        $this->setStatus( $this->getParameter("_scheduler") == "crontab" ? "idle" : "stop" );
        $this->setStopTime(time());
    }

    public function isActive() {
        return $this->active ;
    }

    public function setStatus($status) {
        $status_url = $this->getParameter("_api")."/jobs/".$this->getParameter("_id")."/status";
        $this->log("setting status '{$status}'");
        switch($status) {
            case ACTIVE:
            case DONE:
            case STOP:
            case IDLE:
            case ERROR:
                RestClient::put($status_url,$status,null,null,"text/plain");
                break;
            default:
                $this->log("status does not exist: '{$status_url}'");
                break;
        }
    }

    private function setStartTime($time) {
        $time_url = $this->getParameter("_api")."/".$this->getParameter("_id")."/starttime";
        $time = microtime(true);
        RestClient::put($time_url,$time,null,null,"text/plain");
        return $this;
    }

    private function setStopTime($time) {
        $time_url = $this->getParameter("_api")."/".$this->getParameter("_id")."/stoptime";
        $time = microtime(true);
        RestClient::put($time_url,$time,null,null,"text/plain");
        return $this;
    }

    public function log($message) {
        if(is_string($message)) {
            $log_url = $this->getParameter("_api")."/jobs/".$this->getParameter("_id")."/log";
            RestClient::post($log_url,$message,null,null,"text/plain");
        }
        return $this;
    }

    public function response($message) {
        if(is_string($message)) {
            $resp_url = $this->getParameter("_api")."/jobs/".$this->getParameter("_id")."/response";
            RestClient::post($resp_url,$message,null,null,"text/plain");
        }
        return $this;
    }

    public abstract function run() ;
    public abstract function getInformation();

}

?>
