<?php
 /**
  * The Tasks Class
  *
  * For the most possible flexibiilty I chose to take
  * the commandline input to be individual commands.
  * This input can be categorised by setting client
  * flags as expressed in the Flags Class.
  *
  * By setting a flag in the appropriate place in the
  * Flags object it's possible to force a client to
  * identify with the system before proceeding.
  *
  * @authored on    27/04/2014
  * @authored by    TheHypnotist
  *
  */
Class Tasks {
  static $state = Array();
  function lookupCommand($cmd) {
    if(method_exists("Tasks", "cmd_".$cmd)) {
      return S_OK;
    } else {
      return E_NOT_EXIST;
    }
  }
  function cmd_quit ($id, $args) {
    Clients::writeClient($id, "ERROR :Closing Link: [x] (Disconnected)\r\n");
    Clients::delClient($id);
  }
  function cmd_nick ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    if ($clientObject->getFlag(F_REGISTERED)) {
      Clients::writeClient($id, ':'.Server::getHostname()." NOTICE ".$clientObject->getMeta('nick')." :*** Nick changes have been disabled.\r\n");
      return;
    }
    // :vengeance.ukchatters.co.uk 433 * TheHypnotist :Nickname is already in use.
    
    if (sizeof($args)>1) {
      /* if first character of nickname is a colon : character, remove it */
      $tmp_nick = "";
      if($args[1][0] == ':') {
        $tmp_nick = substr($args[1],1);
      } else {
        $tmp_nick = $args[1];
      }
      /* check if nick is in use */
      if (Clients::nickInUse ($tmp_nick)) {
        Clients::writeClient($id, ':'.Server::getHostname()." 433 * $tmp_nick :Nickname is already in use\r\n");
        return;
      }
      $clientObject->setMeta('nick', $tmp_nick);
      if ( ($clientObject->getMeta('nick') != '') & ($clientObject->getMeta('user') != '') ) {
        $clientObject->setFlag(F_REGISTERED, 1);
        $clientObject->eventRegistered();
      }
    } else {
      Clients::writeClient($id, ':'.Server::getHostname()." 431  :No nickname given\r\n");
    }
  }
  function cmd_user ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    if ($clientObject->getFlag(F_REGISTERED)) {
      //:endurance.ukchatters.co.uk 462 asdfsadfsafsa :You may not reregister
      Clients::writeClient($id, ':'.Server::getHostname()." 462 ".$clientObject->getMeta('nick')." :You may not reregister\r\n");
      return;
    }
    if (sizeof($args)>1) {
      $clientObject->setMeta('user', implode(' ', $args));
      if ( ($clientObject->getMeta('nick') != '') & ($clientObject->getMeta('user') != '') ) {
        // I guess we are now registered
        $clientObject->setFlag(F_REGISTERED, 1); //meow
        $clientObject->eventRegistered();
      }
    } else {
      Clients::writeClient($id, ':'.Server::getHostname()." 461  USER :Not enough parameters\r\n");
    }
  }
  function cmd_pass ($id, $args) {
  }
  function cmd_ping ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    Clients::writeClient($id, ':'.Server::getHostname()." PONG $args[1]\r\n");
  }
  function cmd_ison ($id, $args) {
  }
  function cmd_userhost ($id, $args) {
  }
  function cmd_mode ($id, $args) {
  }
  function cmd_list ($id, $args) {
  }
  function cmd_poke ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    Clients::writeClient($id, ':'.Server::getHostname()." NOTICE ".$clientObject->getMeta('nick')." :*** KEEP YOUR FINGERS AWAY FROM ME!\r\n");
  }
  function cmd_join ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    //:asdfsdafsfsda!guest@ukc-96704B8B.kimsufi.com JOIN :#idle
    $address = $clientObject->getMeta('address');
    $nick    = $clientObject->getMeta('nick');
    $hostmask = "$nick!vmesh@$address";
    // TODO: currently only joins 1 room at a time.
    $room = $args[1];
    //doesChannelExist
    if(Channels::doesChannelExist($room)) {
      Channels::clientJoin($id, $hostmask, $room);
    } else {
      Channels::addChannel ($room, $nick);
      Channels::clientJoin($id, $hostmask, $room);
    }
    Clients::writeClient($id, ":$hostmask JOIN :$room\r\n");
  }
  function cmd_part ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    //:asdfsdafsfsda!guest@ukc-96704B8B.kimsufi.com JOIN :#idle
    $address = $clientObject->getMeta('address');
    $nick    = $clientObject->getMeta('nick');
    $hostmask = "$nick!vmesh@$address";
    // TODO: currently only joins 1 room at a time.
    $room = $args[1];
    /* get message */
    $temp_args = $args;
    array_shift($temp_args);
    array_shift($temp_args);
    $temp_args = implode(' ', $temp_args);
    $message = substr($temp_args, 1);

    if(Channels::doesChannelExist($room)) {
      Channels::clientPart($id, $hostmask, $room, $message);
    } else {
      // channel didnt exist hm
    }
    Clients::writeClient($id, ":$hostmask PART :$room\r\n");
  }
  function cmd_privmsg ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    $address = $clientObject->getMeta('address');
    $nick    = $clientObject->getMeta('nick');
    $hostmask = "$nick!vmesh@$address";
    $room = strtolower($args[1]);
    $temp_args = $args;
    array_shift($temp_args);
    array_shift($temp_args);
    $temp_args = implode(' ', $temp_args);
    $message = substr($temp_args, 1);
    Channels::clientPrivmsg($id, $hostmask, $room, $message);
  }
  function cmd_notice ($id, $args) {
    $clientObject = Clients::getClientObjectById($id);
    $address = $clientObject->getMeta('address');
    $nick    = $clientObject->getMeta('nick');
    $hostmask = "$nick!vmesh@$address";
    $room = strtolower($args[1]);
    $temp_args = $args;
    array_shift($temp_args);
    array_shift($temp_args);
    $temp_args = implode(' ', $temp_args);
    $message = substr($temp_args, 1);
    Channels::clientNotice($id, $hostmask, $room, $message);
  }

}

