<?php
Class Channels {
  static $list = Array();
  static $state = Array();
  function addChannel ($name, $founder) {
    $new_channel = new Channel($name, $founder);
    self::$list['channels'][strtolower($name)] = $new_channel;
  }
  function delChannel ($name) {
    unset(self::$list['channels'][strtolower($name)]);
  }
  function doesChannelExist ($name) {
    if ( isset(self::$list['channels'][strtolower($name)]) ) {
        return 1;
    } else {
        return 0;
    }
  }
  function getChannelObjectByName($name) {
    return self::$list['channels'][strtolower($name)];
  }
  /* returns 1 line of buffer from all open channels */
  function getChannelBufferNext () {
    $queue = Array();
    foreach (self::$list['channels'] as $name => $obj) {
      $tmp = $obj->bufferGetNext();
      if($tmp) {
        $queue[$name]= $tmp;
      }
    }
    if(sizeof($queue)>0) {
      return $queue;
    } else {
        return 0;
    }
  }
  function getChannelClients($room) {
    return self::$list['channels'][$room]->getClients();
  }
  function init() {
    self::$list['channels'] = Array();
    self::$state['last_channel_id'] = 0;
  }
  function clientJoin($cid, $hmask, $room) {
    $room = strtolower($room);
    if (self::doesChannelExist($room)) {
        self::$list['channels'][$room]->join ($cid, $hmask);
    } else {
        echo "*** channel doesn't exist and wasnt checked ahead of time\n";
    }
  }
  function clientPart($cid, $hmask, $room, $message) {
    $room = strtolower($room);
    if (self::doesChannelExist($room)) {
        self::$list['channels'][$room]->part ($cid, $hmask, $message);
    } else {
        echo "*** channel doesn't exist and wasnt checked ahead of time\n";
    }
  }
  function clientPrivmsg($cid, $hmask, $room, $message) {
    $room = strtolower($room);
    if (self::doesChannelExist($room)) {
        self::$list['channels'][$room]->privmsg ($cid, $hmask, $message);
    } else {
        echo "*** channel doesn't exist and wasnt checked ahead of time\n";
    }
  }
  function clientNotice($cid, $hmask, $room, $message) {
    $room = strtolower($room);
    if (self::doesChannelExist($room)) {
        self::$list['channels'][$room]->notice ($cid, $hmask, $message);
    } else {
        echo "*** channel doesn't exist and wasnt checked ahead of time\n";
    }
  }
}
