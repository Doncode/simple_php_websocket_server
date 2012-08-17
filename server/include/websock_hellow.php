<?php
//подключение

list($head, $body) = explode("\r\n\r\n",$str,2);
if(preg_match("/\r\n(.*?)\$/",$str,$match)){ $l8=$match[1]; }

unset($params);
$arr = explode("\n",$head);
foreach($arr as $key => $val){
	if(trim($val) == ''){
		//print_r($arr);
		//$body = implode("\n",$arr);
	}
	$arr[$key] = explode(":",$val,2);
	if(count($arr[$key])==2){
		$params[trim($arr[$key][0])] = trim($arr[$key][1]);
		unset($arr[$key]);
	}
}

list($host, $port) = explode(':',$params['Host']);


if(empty($params['Origin']) &&  !empty($params['Sec-WebSocket-Origin'])){
	$params['Origin'] = $params['Sec-WebSocket-Origin'];
}

if(preg_match("/GET (.*) HTTP/"   ,$str,$match)){ $r=$match[1]; }
$answer = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n";
$answer .= "Upgrade: WebSocket\r\n";
$answer .= "Connection: Upgrade\r\n";
$answer .= "Sec-WebSocket-Origin: {$params['Origin']}\r\n";
$answer .= "Sec-WebSocket-Location: ws://{$params['Host']}{$r}\r\n";


if(isset($params['Sec-WebSocket-Key1']) && isset($params['Sec-WebSocket-Key2']) && !empty($params['Sec-WebSocket-Key1']) && !empty($params['Sec-WebSocket-Key2'])){
	$SESS[$keyINsock]['websock_encode'] = false;
	$answer .= "\r\n".websock_calcKey($params['Sec-WebSocket-Key1'],$params['Sec-WebSocket-Key2'],$l8 );
	

}else{
	$SESS[$keyINsock]['websock_encode'] = true;
	$params['Sec-WebSocket-Accept'] = base64_encode( sha1 ($params['Sec-WebSocket-Key']  . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',true ) );
	$answer .= "Sec-WebSocket-Accept: {$params['Sec-WebSocket-Accept']}\r\n";
	$answer .= "Access-Control-Allow-Origin: {$params['Origin']}\r\n";
	$answer .= "Access-Control-Allow-Credentials: true\r\n";
	$answer .= "Access-Control-Allow-Headers: content-type\r\n";
	$answer .= "\r\n";
}




sockSend($keyINsock,$answer,false);
//dissconect($keyINsock);
$SESS[$keyINsock]['websock'] = true;
unset($params);
?>