<?php
include_once("../config.php");

class JobWriterController implements RestController {

    public function execute(RestServer $rest) {
        $rest->getResponse()->setResponse("Hello, world!");
        return $rest;
    }

    public function jobs(RestServer $rest) {
        $db   = $rest->getParameter("db");
        $view = new View;
        $jobs = array();
        
        $req = json_decode($rest->getRequest()->getBody());

        $sql = "insert into job (id,worker,status,parameters,scheduler) values (?,?,?,?,?)";
        $insert = $db->prepare($sql);
        if(!$insert) {
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
            return $view;
        }

        $id = strlen($req->id) ? $req->id : sha1($req->worker.":".microtime(true));
        $worker = $req->worker;
        $status = 'idle';
        $parameters = json_encode($req->parameters);
        $scheduler = $req->scheduler;

        $ok = $insert->execute(array($id,$worker,$status,$parameters,$scheduler));
        if(!$ok) {
          $view->success = false;
          $view->message = implode("\n",$insert->errorinfo());
        } else {
          $jobs = $db->query("select * from job where id = '".$id."'")->fetchAll(PDO::FETCH_ASSOC);
        }
        $rest->setParameter("data",$jobs);
        return $view;
    }

    public function job(RestServer $rest) {
        $db   = $rest->getParameter("db");
        $php  = $rest->getParameter("php_command");
        $view = new View;
        $jobs = array();
        $id   = $rest->getRequest() ->getURI(2);

        $stmnt = $db->prepare("SELECT * FROM job WHERE id = ?");
        $query = $stmnt->execute(array($id));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
            return $view;
        }
        $job = $stmnt->fetchObject();
        if($job->status == "active") {
            $view->success = false;
            $view->message = "Can't delete a running job'";
            return $view;
        }

        $stmnt = $db->prepare("DELETE FROM job WHERE id = ?");
        $query = $stmnt->execute(array($id));
        if(!$query) { 
            var_dump($stmnt->errorInfo());
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
        } else {
            $jobs = $stmnt->fetchAll();

            $stmnt = $db->prepare("DELETE FROM log WHERE job_id = ?");
            $query = $stmnt->execute(array($id));
            $stmnt = $db->prepare("DELETE FROM response WHERE job_id = ?");
            $query = $stmnt->execute(array($id));
        }
        exec("id && ".$php." cron.php", $stdout); // update cron

        $rest->setParameter("data",$jobs);
        return $view;
    }

    public function jobLog(RestServer $rest) {
        $db   = $rest->getParameter("db");
        $view = new View;

        $stmnt = $db->prepare("insert into log (id, job_id, time, message) values (?,?,?,?)");
        $id =  sha1($job_id.":".microtime(true));
        $job_id = $rest->getRequest() ->getURI(2);
        $time = microtime(true);
        $message = $rest->getRequest()->getBody();
        $message = "[".date("Y-m-d H:i:s",time())."] {$message}"; 
        $query = $stmnt->execute(array($id,$job_id,$time,$message));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
            return $view;
        }

        $jobs = array();
        $stmnt = $db->prepare("SELECT message FROM log WHERE id = ?");
        $query = $stmnt->execute(array($id));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
        } else {
            $jobs = $stmnt->fetchAll();
        }
        $rest->setParameter("data",$jobs);
        return $view;
    }

    public function jobResponse(RestServer $rest) {
        $db   = $rest->getParameter("db");
        $view = new View;

        $stmnt = $db->prepare("insert into response (id, job_id, time, message) values (?,?,?,?)");
        $id =  sha1($job_id.":".microtime(true));
        $time = microtime(true);
        $job_id = $rest->getRequest() ->getURI(2);
        $message = $rest->getRequest()->getBody();
        $query = $stmnt->execute(array($id,$job_id,$time,$message));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
            return $view;
        }

        $jobs = array();
        $stmnt = $db->prepare("SELECT message FROM response WHERE id = ?");
        $query = $stmnt->execute(array($id));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
        } else {
            $jobs = $stmnt->fetchAll();
        }
        $rest->setParameter("data",$jobs);
        return $view;
    }

    public function jobPid(RestServer $rest) {
        $db   = $rest->getParameter("db");
        $view = new View;
        $pid  = $rest->getRequest()->getBody();
        $id = $rest->getRequest() ->getURI(2);

        $stmnt = $db->prepare("update job set pid = ? where id = ?");
        $query = $stmnt->execute(array($pid,$id));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
            return $view;
        }

        $jobs = array();
        $stmnt = $db->prepare("SELECT * FROM job WHERE id = ?");
        $query = $stmnt->execute(array($id));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
        } else {
            $jobs = $stmnt->fetchAll();
        }
        $rest->setParameter("data",$jobs);
        return $view;
    }
    
    public function jobParameters(RestServer $rest) {
        $db   = $rest->getParameter("db");
        $view = new View;
        $php  = $rest->getParameter("php_command");
        $parameters = $rest->getRequest()->getBody();
        $parameters = preg_replace('~[\r\n]+~', '', $parameters);
        $parameters = preg_replace('/\s+/', ' ',$parameters);
        $parameters = $this->indentJSON($parameters);
        $id = $rest->getRequest() ->getURI(2);
        
        if( json_decode($parameters) == NULL ){
            $view->success = false;
            $view->message = "Your json is not valid, therefore it is not saved";
            return $view;
        }

        $stmnt = $db->prepare("update job set parameters = ? where id = ?");
        $query = $stmnt->execute(array($parameters,$id));
        if(!$query) { 
            $view->success = false;
            $view->message = implode("\n",$db->errorinfo());
            return $view;
        }

        $jobs = array();
        $stmnt = $db->prepare("SELECT * FROM job WHERE id = ?");
        $query = $stmnt->execute(array($id));
        if(!$query) { 
          $view->success = false;
          $view->message = implode("\n",$db->errorinfo());
        } else {
          $jobs = $stmnt->fetchAll();
        }
        exec("id && ".$php." cron.php", $stdout); // update cron
        $view->message = $stdout;
        $rest->setParameter("data",$jobs);
        return $view;
    }

    public function jobStatus(RestServer $rest) {
        global $config;
        $db   = $rest->getParameter("db");
        $view = new View;
        $php  = $rest->getParameter("php_command");
        $kill  = $rest->getParameter("kill_command");

        $status = $rest->getRequest()->getBody();
        $id = $rest->getRequest()->getURI(2);

        $stmt = $db->prepare("SELECT * FROM job WHERE id = ?");
        $ok = $stmt->execute(array($id));
        if(!$ok) {
            $view->success = false;
            $view->message = implode("\n",$stmt->errorinfo());
            return $view;
        }
        $job = $stmt->fetchObject();

        if( $dont_restart_job_when_done && in_array( $job->status, array("done","error") ) ) {
            $view->success =false;
            $view->message ="Job has finished";
            return $view;
        }
        if($status == "start" && in_array( $job->status, array("idle","done","stop") ) ){
            $stmt = $db->prepare("UPDATE job SET starttime = ?, status = ?, stoptime = 0 where id = ?");
            $ok = $stmt->execute(array(microtime(true),"idle",$id));
            if( $job->scheduler != "crontab" ) exec($php." run.php ".$id." > /dev/null &");
        } else if($status == "stop" && in_array($job->status,array("active","idle") ) ) {
            exec($kill." ".$job->pid);
            $stmt = $db->prepare("UPDATE job SET stoptime = ? where id = ?");
            $ok = $stmt->execute(array(microtime(true),$id));
        } else if($status == "kill") {
            exec($kill." -9 ".$job->pid);
            $stmt = $db->prepare("UPDATE job SET stoptime = ? where id = ?");
            $ok = $stmt->execute(array(microtime(true),$id));
            $stmt = $db->prepare("UPDATE job SET status = 'stop' where id = ?");
            $ok = $stmt->execute(array($id));
        } else if($status == "active") {
            $stmt = $db->prepare("UPDATE job SET status = ? where id = ?");
            $ok = $stmt->execute(array($status,$id));
            if(!$ok) {
                $view->success = false;
                $view->message = implode("\n",$stmt->errorinfo());
            }
        } else if( in_array($status,array("done","error","idle")) ) {
            $stmt = $db->prepare("UPDATE job SET status = ? where id = ?");
            $ok = $stmt->execute(array($status,$id));
            if(!$ok) {
                $view->success = false;
                $view->message = implode("\n",$stmt->errorinfo());
            }
            $stmt = $db->prepare("UPDATE job SET stoptime = ? where id = ?");
            $ok = $stmt->execute(array(microtime(true),$id));
            if( $status == "error" ){
              foreach( $config->maintainerEmails as $to ) 
                sendMail( $to, 
                          "worker error: ".$this->getParameter('_id'),
                          "an error occured in {$id}, please check the logs @ ".$this->getParameter('_api'),
                          $config->maintainerEmailFrom,
                          $config->maintainerEmailFromName
                        );
            }
        }

        $stmt = $db->prepare("SELECT * FROM job WHERE id = ?");
        $ok   = $stmt->execute(array($id));
        $job  = $stmt->fetchObject();
        $rest->setParameter("data",array($job));
        return $view;
    }

  /**
   * indentJSON is a pretty print for json text
   * 
   * @param mixed $json 
   * @static
   * @access public
   * @return void
   */
    public static function indentJSON($json) {

      $result      = '';
      $pos         = 0;
      $strLen      = strlen($json);
      $indentStr   = '  ';
      $newLine     = "\n";
      $prevChar    = '';
      $outOfQuotes = true;

      for ($i=0; $i<=$strLen; $i++) {

          // Grab the next character in the string.
          $char = substr($json, $i, 1);

          // Are we inside a quoted string?
          if ($char == '"' && $prevChar != '\\') {
              $outOfQuotes = !$outOfQuotes;
          
          // If this character is the end of an element, 
          // output a new line and indent the next line.
          } else if(($char == '}' || $char == ']') && $outOfQuotes) {
              $result .= $newLine;
              $pos --;
              for ($j=0; $j<$pos; $j++) {
                  $result .= $indentStr;
              }
          }
          
          // Add the character to the result string.
          $result .= $char;

          // If the last character was the beginning of an element, 
          // output a new line and indent the next line.
          if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
              $result .= $newLine;
              if ($char == '{' || $char == '[') {
                  $pos ++;
              }
              
              for ($j = 0; $j < $pos; $j++) {
                  $result .= $indentStr;
              }
          }
          
          $prevChar = $char;
      }

      return $result;
    }


}
?>
