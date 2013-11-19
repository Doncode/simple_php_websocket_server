<?php
//do not change this file
//
//

register_shutdown_function('shutdown_php');
function shutdown_php() {
	global $sock,$conf,$mainSocket;
	echo "\r\n========= SHUTDOWN_PHP ==================\r\n";
	shutdownSev($mainSocket, $sock);
}


function websock_start($address, $port) {
	global $conf;

        for ($i = 0; $i < 300; $i++) {
            if (is_resource($mainSocket) && get_resource_type($mainSocket) == "Socket") {
                return $this->mainSocket;
            }

            if (!$mainSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
                echo("socket_create ERR");
            } else if (!socket_set_option($mainSocket, SOL_SOCKET, SO_REUSEADDR, 1)) {
                echo(socket_strerror(socket_last_error($sock)));
            } elseif (!socket_bind($mainSocket, $address, $port)) {
                echo("socket_bind ERR $address:$port");
            } elseif (!socket_listen($mainSocket, 5)) {
                echo("socket_listen ERR");
            } elseif (!socket_set_nonblock($mainSocket)) {  //socket_set_blocking()
                echo("socket_set_nonblock ERR");
            } else {
                echo("Websock Server Start OK");
                return $mainSocket;
            }
            $mainSocket = false;
            sleep(1);
        }
}

function shutdownSev($mainSocket, $sockArr) {
	global $conf;
    foreach ($sockArr as $k => $res) {
		socket_shutdown($res,2);
        socket_close($res);
    }
	sleep(1);
    socket_shutdown($mainSocket,2);
    socket_close($mainSocket);
    exit("Bye\n");
}


function sockSend($key, $answer, $iconvEnable=true) {
    global $sock, $SESS;
	if($iconvEnable){
		$answer = iconv("cp1251","UTF8",$answer);
	}
	
    $sockFP = $sock[$key];
    if ($sockFP === false) {
        return false;
    }
    if($answer == ''){
        return false;
    }
    if (!@socket_set_nonblock($sockFP)) {
		$errorcode = socket_last_error();
		$errormsg = socket_strerror($errorcode);
        echo "ERR sock[$key]={$sock[$key]} IN FUNCTION socket_set_nonblock $errorcode - $errormsg";
        //////save_to_file("ERR sock[$key]={$sock[$key]} IN FUNCTION socket_set_nonblock $errorcode - $errormsg");
        //disconnect($key);
        return false;
    }
    $result = socket_write($sockFP, $answer, strlen($answer));
    if ($result === false) {
		$errorcode = socket_last_error();
		$errormsg = socket_strerror($errorcode);	
        echo "ERR sock[$key]={$sock[$key]} IN FUNCTION socket_write $errorcode - $errormsg";
        //save_to_file("ERR sock[$key]={$sock[$key]} IN FUNCTION socket_write $errorcode - $errormsg");
        disconnect($key);
        return false;
    }

    $SESS[$key]['trafTX']+= strlen($answer);
    $SESS[$key]['timeTX'] = time();

    if (!@socket_set_block($sockFP)) {
		$errorcode = socket_last_error();
		$errormsg = socket_strerror($errorcode);	
        echo "ERR sock[$key]={$sock[$key]} IN FUNCTION socket_set_block $errorcode - $errormsg";
        //save_to_file("ERR sock[$key]={$sock[$key]} IN FUNCTION socket_set_block $errorcode - $errormsg");
        disconnect($key);
        return false;
    }
    echo "TX[$key]: $answer\n";
    //save_to_file("Tx: $sockFP <= $answer");
}

function disconnect($key) {
    global $conf, $sock, $SESS;
    echo "DISCONNECT[$key]\n";
    //save_to_file("DISCONNECT sock[$key]={$sock[$key]}");
    @socket_shutdown($sock[$key], 1);
    usleep(500);
    @socket_shutdown($sock[$key], 0);
    usleep(500);
    @socket_close($sock[$key]);
    unset($sock[$key]);
    unset($SESS[$key]);

}





function websock_decode($data){
    $bytes = $data;
    $data_length = "";
    $mask = "";
    $coded_data = "" ;
    $decoded_data = "";        
    $data_length = $bytes[1] & 127;
    if($data_length === 126){
       $mask = substr($bytes, 4, 8);
       $coded_data = substr($bytes, 8);
    }else if($data_length === 127){
        $mask = substr($bytes, 10, 14);
        $coded_data = substr($bytes, 14);
    }else{
        $mask = substr($bytes, 2, 6);
        $coded_data = substr($bytes, 6);
    }
    for($i=0;$i<strlen($coded_data);$i++){
        $decoded_data .= $coded_data[$i] ^ $mask[$i%4];
    }
    //echo "Server Received->".$decoded_data."\r\n";
    return $decoded_data;
}
function websock_send($keyINsock, $answer){
	global $SESS;
	if($answer!=''){
		if($SESS[$keyINsock]['websock_encode']){
			$answer = websock_encode($answer);
		}else{
			$answer = websock_wrap($answer);
		}
		sockSend($keyINsock, $answer, false);
	}
}
function websock_unwrap($msg=""){	return substr($msg,0,(strlen($msg)-1));}
function websock_wrap($msg=""){ return chr(0).$msg.chr(255); }
function websock_encode($data, $binary=false)    {
        $databuffer = array();
        $sendlength = strlen($data);
        $rawBytesSend = $sendlength + 2;
        $packet;

        if ($sendlength > 65535) {
            // 64bit
            array_pad($databuffer, 10, 0);
            $databuffer[1] = 127;
            $lo = $sendlength | 0;
            $hi = ($sendlength - $lo) / 4294967296;

            $databuffer[2] = ($hi >> 24) & 255;
            $databuffer[3] = ($hi >> 16) & 255;
            $databuffer[4] = ($hi >> 8) & 255;
            $databuffer[5] = $hi & 255;

            $databuffer[6] = ($lo >> 24) & 255;
            $databuffer[7] = ($lo >> 16) & 255;
            $databuffer[8] = ($lo >> 8) & 255;
            $databuffer[9] = $lo & 255;

            $rawBytesSend += 8;
        } else if ($sendlength > 125) {
            // 16 bit
            array_pad($databuffer, 4, 0);
            $databuffer[1] = 126;
            $databuffer[2] = ($sendlength >> 8) & 255;
            $databuffer[3] = $sendlength & 255;

            $rawBytesSend += 2;
        } else {
            array_pad($databuffer, 2, 0);
            $databuffer[1] = $sendlength;
        }

        // Set op and find
        $databuffer[0] = (128 + ($binary ? 2 : 1));
        $packet = pack('c', $databuffer[0]);
        // Clear masking bit
        //$databuffer[1] &= ~128;
        // write out the packet header
        for ($i = 1; $i < count($databuffer); $i++) {
            //$packet .= $databuffer[$i];
            $packet .= pack('c', $databuffer[$i]);
        }

        // write out the packet data
        for ($i = 0; $i < $sendlength; $i++) {
            $packet .= $data[$i];
        }

        return $packet;
}
function websock_calcKey($strkey1,$strkey2,$data){
	  $pattern = '/[^\d]*/';
	  $replacement = '';
	  $numkey1 = preg_replace($pattern, $replacement, $strkey1);
	  $numkey2 = preg_replace($pattern, $replacement, $strkey2);

	  $pattern = '/[^ ]*/';
	  $replacement = '';
	  $spaces1 = strlen(preg_replace($pattern, $replacement, $strkey1));
	  $spaces2 = strlen(preg_replace($pattern, $replacement, $strkey2));

	  if ($spaces1 == 0 || $spaces2 == 0 || $numkey1 % $spaces1 != 0 || $numkey2 % $spaces2 != 0) {
		socket_close($user->socket);
		console('failed');
		return false;
	  }

	  $ctx = hash_init('md5');
	  hash_update($ctx, pack("N", $numkey1/$spaces1));
	  hash_update($ctx, pack("N", $numkey2/$spaces2));
	  hash_update($ctx, $data);
	  $hash_data = hash_final($ctx,true);
	   echo "hash_data:$hash_data \r\n";
	  return $hash_data;
	}
?>