<?php

Class Channel {
  static $state = Array();
  static $meta = Array();
  static $buffer = Array();
  static $clients = Array();
  function __destruct () {
    $name = $this->getMeta('name');
    echo "^^^ Erased channel $name\r\n";
  }
  function __construct ($name, $founder) {
    echo "^^^ Created channel $name\r\n";
    $this->setMeta('name', $name);
    $this->setMeta('founder', $founder);
    $this->clients = Array();
  }
  private function addClient ($cid) {
    $this->clients[$cid] = true;
  }
  private function delClient ($cid) {
    unset($this->clients[$cid]);
  }
  function getClients () {
    return $this->clients;
  }
  function setMeta ($item, $value) {
    $this->meta[$item] = $value;
  }
  function getMeta ($item) {
    if (isset($this->meta[$item])) {
      return $this->meta[$item];
    } else {
      return false;
    }
  }
  function privmsg ($cid, $hmask, $message) {
    $this->buffer[] = Array('cid' => $cid, 'source' => $hmask, 'message' => $message, 'type' => 'privmsg');
    $room = $this->getMeta('name');
    echo ":$hmask PRIVMSG $room :$message\n";
  }
  function notice ($cid, $hmask, $message) {
    $this->buffer[] = Array('cid' => $cid, 'source' => $hmask, 'message' => $message, 'type' => 'notice');
    $room = $this->getMeta('name');
    echo ":$hmask NOTICE $room :$message\n";
  }
  function join ($cid, $hmask) {
    $this->buffer[] = Array('cid' => $cid, 'source' => $hmask, 'message' => '', 'type' => 'join');
    $room = $this->getMeta('name');
    echo ":$hmask JOIN :$room\n";
    $this->addClient($cid);
  }
  function part ($cid, $hmask, $message) {
    $this->buffer[] = Array('cid' => $cid, 'source' => $hmask, 'message' => $message, 'type' => 'part');
    $room = $this->getMeta('name');
    echo ":$hmask PART :$room\n";
  }
  function bufferGetNext () {
    if ( sizeof($this->buffer) > 0 ) {
        return array_shift($this->buffer);
    } else {
        return 0;
    }
  }
  function hasBuffer () {
    if ( sizeof($this->buffer) > 0 ) {
      return 1;    
    } else {
        return 0;
    }
  }
}
