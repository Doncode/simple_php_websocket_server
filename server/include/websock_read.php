<?php
//чтение
$str_encode = $SESS[$keyINsock]['websock_encode'] ? websock_decode($str) : websock_unwrap($str)	;
websocket_onmessage($keyINsock, $str_encode);

