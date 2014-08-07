<?php


function websocket_onopen($keyINsock){
	echo "\r\n";
	echo "WEBSOCKET_ONOPEN[$keyINsock]";
	echo "\r\n";
}


function websocket_onmessage($keyINsock, $str){
	echo "\r\n";
	echo "WEBSOCKET_ONMESSAGE[$keyINsock] $str \r\n";
	echo "\r\n";
	websock_send($keyINsock, $str); //эхо
}


function websocket_onclose($keyINsock){
	echo "\r\n";
	echo "WEBSOCKET_ONCLOSE[$keyINsock]";
	echo "\r\n";
}



function websocket_while(){
	global $STDIN,$sock,$SESS;
	if(!isset($STDIN)){
		$STDIN = fopen('php://stdin', 'r');
	}
	

	stream_set_blocking ($STDIN, FALSE );
	$STDINline = trim(fgets($STDIN));
	echo $STDINline;
	if(!empty($STDINline) && is_array($SESS) && count($SESS)>0){
		foreach($SESS as $k => $v){
			if($SESS[$k]['websock']){
				websock_send($k, $STDINline);
			}
		}
	}
	
	//echo '.';
}



