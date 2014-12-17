<?php
 /**
  * The Clients Class
  *
  * This class will eventually need to be replaced with
  * a linked list of clients instead of the '$list'
  * array. Functionally there should be no difference
  * whatsoever for the Tasks Class but it will be a long
  * term improvement for handling many connections.
  *
  * That said there is no rush at all for changing $list.
  *
  * @authored on    27/04/2014
  * @authored by    TheHypnotist
  *
  */
Class Clients {
  static $state = Array();
  static $list = Array();
  /*send a broadcast, like /os global, to all opers */
  function broadcastOpers ($id, $message) {
  }
  function broadcastStaff ($id, $message) {
  }
  function broadcastRegistered ($id, $message) {
  }
  function broadcastIdentified ($id, $message) {
  }
  function broadcastNotIdentified ($id, $message) {
  }
  /*
    broadcastChannelMessage
    mode == PRIVMSG or NOTICE.
    id == the source client id, the person triggering the event
  */
  function broadcastChannelMessage ($sid, $channel, $mode, $message) {
    $clients = Clients::getClients();
    /**
     * Get the source client information, construct a hostmask
     */
    $clientObject = Clients::getClientObjectById($sid);
    $address = $clientObject->getMeta('address');
    $nick    = $clientObject->getMeta('nick');
    $hostmask = "$nick!vmesh@$address";

    if ( ($clients != -1) ) {
      foreach($clients as $did => $client) {
        $client_channel = $client->getMeta('channels');
        if( $sid != $did) {
          if (in_array($client_channel, $channel)) {
            self::writeClient($client_id, ":$hostmask $mode :$message\r\n");
          }
        }
        // loop around the clients array
        // if client is in given target channel
/*        $buffer = socket_read($client->getSocket(), 2048, PHP_NORMAL_READ);
        if ($buffer != '') {
          self::parse_input_from_client($id, $buffer);
        }
*/
      }
    }
  }
  function addClient($fd, $address) {
    // argh I hate how ugly this function looks
    $client_id = self::$state['last_client_id'] + 1;
    $new_client = new Client($client_id);
    $new_client->setSocket($fd);
    $new_client->setMeta('address', $address);
    self::$list['clients'][$client_id] = $new_client;
    self::$state['last_client_id'] = $client_id;
    self::writeClient($client_id, ':'.Server::getHostname()." NOTICE AUTH :Looking up your hostname...\r\n");
    return $client_id;
  }
  function delClient($id) {
    socket_close(self::getClientDescriptor($id));
    unset(self::$list['clients'][$id]);
    echo "*** $id connection close\r\n";
  }
  function getClients() {
    if(sizeof(self::$list['clients']) > 0) {
      return self::$list['clients'];
    } else {
      return -1;
    }
  }
  function getClientDescriptor($id) {
    return self::$list['clients'][$id]->getSocket();
  }
  function getClientObjectById($id) {
    return self::$list['clients'][$id];
  }
  function nickInUse ($search) {
    $clients = Clients::getClients();
    if ( ($clients != -1) ) {
      foreach($clients as $id => $client) {
        $client_nick = $client->getMeta('nick');
        if ($search == $client_nick) {
          return 1;
        }
      }
    }
    return 0;
  }
  function init() {
    self::$list['clients'] = Array();
    self::$state['last_client_id'] = 0;
  }
  function writeClient($id, $buffer) {
    /*
    $result = socket_write(self::getClientDescriptor($id), $buffer);
    if ($result === 0) {
      self::delClient($id);
      echo "*** $id failed: reason: " . socket_strerror(socket_last_error()) . "\r\n";
    }
    */
    $clientObject = Clients::getClientObjectById($id);
    $clientObject->bufferSendAppend($buffer);
  }
  function sendClient($id, $buffer) {
    $result = socket_write(self::getClientDescriptor($id), $buffer);
    if ($result === 0) {
      self::delClient($id);
      echo "*** $id failed: reason: " . socket_strerror(socket_last_error()) . "\r\n";
    } else {
        // return bytes sent
        return $result;
    }
  }
}


