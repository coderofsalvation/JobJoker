<?php

include_once 'libs/Worker.php';

class ShellCommand extends Worker {

    public function getInformation() {
        return "Runs a shell command";
    }

    public function run()  {
        $cmd = $this->getParameter("command");
        $this->log("running '{$cmd}'");
        exec( $cmd, $stdout );
        $this->response( implode("\n",$stdout) );
    }
}

?>
