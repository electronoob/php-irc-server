<?php

  foreach (glob("Constants/*.php") as $filename) {
    require_once( $filename );
  }
  foreach (glob("Classes/*.php") as $filename) {
    require_once( $filename );
  }
  require_once('Configuration.php');

  Clients::init();
  Channels::init();
  Server::start();

