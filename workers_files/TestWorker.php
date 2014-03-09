<?php

include_once 'libs/Worker.php';

class TestWorker extends Worker {
    
    public function getInformation() {
        return "This is a sample job";
    }

    public function run()  {
      $this->log("example logcall");
      system("sleep 5s");
      $this->response("example response");
    }

}

?>
