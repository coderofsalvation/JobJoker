<?php

$config = array(
  'api'                        => "http://localhost/JobJoker",
  'php_command'                => "/usr/bin/php",
  'kill_command'               => "kill",

  'mysql'                      => true,
  'mysql_host'                 => "localhost",
  'mysql_database'             => "jobjoker",
  'mysql_user'                 => "",
  'mysql_password'             => "",

  'auth'                       => false,
  'user'                       => 'jobjoker',
  'password'                   => 'jobjoker',

  'dont_restart_job_when_done' => false,         // allows to (re)starting jobs when their status is 'done'/'error'/'stop'

  'maintainerEmails'           => array("my@email.com"), // receive emails when things go wrong
  'maintainerEmailFrom'        => "noreply@worker.com",
  'maintainerEmailFromName'    => "JobJoker",
);
extract($config);
$config = (object)$config;

?>
