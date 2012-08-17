<?php
$address 	= '0.0.0.0';
$port 		= 888;
$usleep 	= 10000;
$while 		= 1;
$sID 		= 0;
$timeout 	= 0;  




ignore_user_abort(1);
require_once(dirname(__FILE__).'/func_demon.php');
require_once(dirname(__FILE__).'/use.php');

echo "Starting($address, $port)...\r\n";

ini_set("display_errors", 1);
error_reporting(E_ALL & ~E_NOTICE);

//Проверка занятости сокета
for ($qqw = 0; $qqw < 100; $qqw++) {
	echo "websock_start($address, $port)...";
    $mainSocket = websock_start($address, $port);

    if ($mainSocket !== FALSE) {
        break;
    }
	sleep(2);
}

if ($mainSocket !== FALSE) {

    while ($while) {
		//прием новых подключений
		require (dirname(__FILE__).'/include/socket_accept.php');
		
		websocket_while();
		
        if (count($sock) > 0) {
			//Обработка массива сессий
			//require (dirname(__FILE__).'/include/sess.php');

            $read = $sock;
			$w = $e = null;
			if (false === socket_select($read, $w, $e, 0)) {
				echo "socket_select() failed, reason: " . socket_strerror(socket_last_error()) . "\n";
			}else{

				foreach ($read as $key => $nowread) {
					//Читает сокет. Находит ключ $keyINsock. 
					require (dirname(__FILE__).'/include/socket_read.php');
					
					// NOTICE:Клиент отключился
					if ($strlen == 0) {
						require (dirname(__FILE__).'/include/disconnect.php');

					}elseif($str == '<policy-file-request/>'){			
						sockSend($keyINsock, '<?xml version="1.0"?><!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd"><cross-domain-policy><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>');
						disconnect($keyINsock);
						
					}elseif(!isset($SESS[$keyINsock]['websock']) && substr($str,0,3)=='GET'){
						require (dirname(__FILE__).'/include/websock_hellow.php');
						
					}elseif(isset($SESS[$keyINsock]['websock'])){
						require (dirname(__FILE__).'/include/websock_read.php'); 
					
					} elseif ($str == "stp" || $str == 'shutdown' ) {
						shutdownSev($mainSocket, $sock);
						break;
						
					} else {
						echo "Not command\n";
					}
				}
            }
        }

		//для разгрузки процессора
		require (dirname(__FILE__).'/include/usleep.php');
    }
}


?>
