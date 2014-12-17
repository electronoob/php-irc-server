<?php

 /**
  * The Server Class
  *
  * This Class is currently used to store some
  * information about the server, such as it's
  * hostname. In addition to this information
  * we also handle the socket loop and input
  * parsing.
  *
  * @authored on    27/04/2014
  * @authored by    TheHypnotist
  *
  */
  
Class Server {
  static $state = array();
  static $tick = 0;
  function getHostname() {
    return self::$state['hostname'];
  }
  function setHostname($hostname) {
    self::$state['hostname'] = $hostname;
  }
  function getNetworkname() {
    return self::$state['networkname'];
  }
  function setNetworkname($networkname) {
    self::$state['networkname'] = $networkname;
  }
  function deal_with_new_connection ($fd){
    $address = "";
    socket_getpeername ( $fd,  $address );
    echo "*** Received connection request from $address and now accepting.\r\n";
    $id = Clients::addClient($fd, $address);
  }
  function parse_input_from_client($id, $buffer) {
    $clientObject = Clients::getClientObjectById($id);
    $line = trim($buffer);
    $NOOP_TEMP_ARGS = explode(" ", $line);
    $NOOP_TEMP_ARG1 = strtolower($NOOP_TEMP_ARGS[0]);
    if ( $NOOP_TEMP_ARG1 != "" ) {
      //F_REGISTERED
      if ( !$clientObject->getFlag(F_REGISTERED) ) {
        // set the permitted commands here, which can be used, when unregistered to server (nic, user)
        $allowed_cmds = array('nick','user','pong');
        if(!in_array($NOOP_TEMP_ARG1, $allowed_cmds)) {
          Clients::writeClient($id, ':'.Server::getHostname()." 451 $NOOP_TEMP_ARG1 :You have not registered\r\n");
          return;
        }
      }
      if ( Tasks::lookupCommand($NOOP_TEMP_ARG1) == S_OK ) {
        $NOOP_TEMP_METHOD = "cmd_".$NOOP_TEMP_ARG1;
        Tasks::$NOOP_TEMP_METHOD($id, $NOOP_TEMP_ARGS);
      } else {
        $NOOP_TEMP_SERVER = Server::getHostname();
        $NOOP_TEMP_DESTINATION = $clientObject->getMeta('nick');
        Clients::writeClient($id, ":$NOOP_TEMP_SERVER 421 $NOOP_TEMP_DESTINATION $NOOP_TEMP_ARG1 :Unknown command\r\n");
      }
    }
  }
  function tick() {
    self::doNextClientBufferRecv();
    self::doNextClientBufferSend();
    self::doNextChannelBufferRead();
    self::$tick++;
    if (self::$tick > 100) {
        usleep(10);
        self::$tick = 0;
    }
  }
  function doNextChannelBufferRead() {
    $events = Channels::getChannelBufferNext();
    if (!$events) { 
        return;
    }
    foreach($events as $channel => $event) {
        //Array('cid' => $cid, 'source' => $hmask, 'message' => $message, 'type' => 'privmsg');
        $cid = $event['cid'];
        $hmask = $event['source'];
        $message = trim($event['message']);
        $type = $event['type'];
        $channel = $channel;
        // buffer is filled with the switch below
        $buffer = "";
        switch ($type) {
            case 'privmsg':
              $buffer = ":$hmask PRIVMSG $channel :$message\r\n";
              //Clients::writeClient($id, $buffer);
            break;
            case 'notice':
              $buffer = ":$hmask NOTICE $channel :$message\r\n";
              //Clients::writeClient($cid, $buffer);
            break;
            case 'join';
              $buffer = ":$hmask JOIN :$channel\r\n";
            break;
            case 'part';
              $buffer = ":$hmask PART :$channel\r\n";
            break;
        }
        // now get clients list for that room, and write this to their buffers as required.
        $clients = Channels::getChannelClients($channel);
        foreach ($clients as $id => $notused) {
          // make sure we dont send this event back to the originator
          if ($cid != $id) {
            Clients::writeClient($id, $buffer);
          } else {
          }
        }
    }
  }
  function doNextClientBufferSend () {
    //TODO;
    //bufferSendGetLine
    $clients = Clients::getClients();
    if ( ($clients != -1) ) {
      foreach($clients as $id => $client) {
        $clientObject = Clients::getClientObjectById($id);
        $buffer = $clientObject->bufferSendGetLine();
        if ($buffer != '') {
          // finally send a trailing \r\n
          $buffer .= "\r\n";
          $sentBytesCount = Clients::sendClient($id, $buffer);
          if(strlen($buffer) != $sentBytesCount) {
            // we probably have an issue.
            $clientObject->bufferSendPrepend(substr($buffer, $sentBytesCount));
          }
        }
      }
    } 
  }
  function doNextClientBufferRecv () {
    $clients = Clients::getClients();
    if ( ($clients != -1) ) {
      foreach($clients as $id => $client) {
        $clientObject = Clients::getClientObjectById($id);
        $buffer = $clientObject->bufferRecvGetLine();
        if ($buffer != '') {
          self::parse_input_from_client($id, $buffer);
        }
      }
    }      
  }
  function start() {
    $fd = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_set_option($fd, SOL_SOCKET, SO_REUSEADDR, 1);
    socket_set_nonblock($fd);
    if ( socket_bind($fd, "0.0.0.0", 6667) ) {
      if (socket_listen($fd)) {
        socket_set_nonblock($fd);
        while (1) {
          self::tick();
          if ($newclient = socket_accept($fd)) {
            socket_set_nonblock($newclient);
            self::deal_with_new_connection($newclient);
            continue;
          }
          $clients = Clients::getClients();
          if ( ($clients != -1) ) {
            foreach($clients as $id => $client) {
              $buffer = socket_read($client->getSocket(), 2048);
              if ($buffer != '') {
                //self::parse_input_from_client($id, $buffer);
                 $clientObject = Clients::getClientObjectById($id);
                 $clientObject->bufferRecvAppend($buffer);
              }
            }
          }
        }
      }
    } else {
      die("Unable to bind\r\n");
    }
  }
}
