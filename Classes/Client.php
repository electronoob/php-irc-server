<?php
 /**
  * The Client Class
  *
  * @description    The Clients Class instantiates this Class,
  *                 populates this Class with the socket handle
  *                 and meta data as required. There is also a
  *                 Flags Class, which is embedded into into
  *                 this new instantiated Client object.
  *
  *                 The meta data will be such information as
  *                 the client's nickname, address, and open
  *                 channels. This information can be set and
  *                 retrieved using setMeta and getMeta methods.
  *
  *                 There are also a series of Flags which allow
  *                 the server to know certain client states.
  *                 For example it is possible to know if the
  *                 client has yet registered with the server.
  *                 This will be used for an IRC server.
  *
  * @authored on    27/04/2014
  * @authored by    TheHypnotist
  *
  */
Class Client {
  static $socket;
  static $bufferSend;
  static $bufferRecv;
  static $meta = Array();
  static $flags = Array();
  static $id = -1;
  function bufferRecvAppend($bytes) {
    $this->bufferRecv .= $bytes;
  }
  function bufferRecvGetLine() {
    //$this->bufferRecv;
    $offset = strpos($this->bufferRecv, "\n");
    if ( $offset === false) {
      return '';
    } else {
        $chunk = substr($this->bufferRecv,0,$offset);
        $this->bufferRecv = substr($this->bufferRecv, $offset +1);
        return $chunk;
    }
  }
  function bufferSendAppend($bytes) {
    $this->bufferSend .= $bytes;
  }
  function bufferSendPrepend($bytes) {
    $this->bufferSend = $bytes . $this->bufferSend;
  }
  function bufferSendGetLine() {
    //$this->bufferSend;
    $offset = strpos($this->bufferSend, "\n");
    if ( $offset === false) {
      return '';
    } else {
        $chunk = substr($this->bufferSend,0,$offset);
        $this->bufferSend = substr($this->bufferSend, $offset +1);
        return $chunk;
    }
  }
  function setId ($id) {
    $this->id = $id;
  }
  function getId () {
    return $this->id;
  }
  function getFlag ($flag) {
    return $this->flags[$flag];
  }
  function setFlag ($flag, $value) {
    $this->flags[$flag] = $value;
  }
  function setSocket ($fd) {
    $this->socket = $fd;
  }
  function getSocket () {
    return $this->socket;
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
  function __destruct () {
  }
  function __construct ($id) {
    $this->flags[F_SQLGATE]    = 0;
    $this->flags[F_REGISTERED] = 0;
    $this->flags[F_SILENCED]   = 0;
    $this->flags[F_OPER]       = 0;
    $this->flags[F_STAFF]      = 0;
    $this->flags[F_IDENTIFIED] = 0;
    $this->flags[F_MONITOR]    = 0;
    $this->flags[F_PRIVDEAF]   = 0;
    $this->id = $id;
    // $this->setMeta('nick', '');
    // $this->setMeta('user', '');
    $this->bufferSend = '';
    $this->bufferRecv = '';
  }
  function eventRegistered () {
    // This function is for when the user connects
    // and completes the client registration to the
    // server, this isnt nickserv. Rather it's
    // NICK, USER, PONG. If you know what I mean!
    $nick    = $this->getMeta('nick');
    $address = $this->getMeta('address');
    $this->setMeta('hmask', "$nick!vmesh@$address");
    Clients::writeClient($this->getId(), ':'.Server::getHostname()." 001 ".$this->getMeta('nick')." :You have connected to the ".Server::getNetworkname()." IRC Network as ".$this->getMeta('nick').".\r\n");
    // send server MOTD
    $motd = explode("\n", file_get_contents('motd.txt'));
    Clients::writeClient($this->getId(), ':'.Server::getHostname()." 375 ".$this->getMeta('nick')." :- ".Server::getNetworkname()." Message of the Day -\r\n");
    foreach($motd as $line) {
      $line = trim($line);
      Clients::writeClient($this->getId(), ':'.Server::getHostname()." 372 ".$this->getMeta('nick')." :- ".$line."\r\n");
    }
    Clients::writeClient($this->getId(), ':'.Server::getHostname()." 376 ".$this->getMeta('nick')." :End of MOTD.\r\n");
  }
}

