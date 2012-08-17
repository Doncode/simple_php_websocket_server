<?php
/*
* Прием новых соединений
*/
        $newsock = @socket_accept($mainSocket);
        if ($newsock !== FALSE) {
			// NOTICE:SERG:1:Новое подключение к моб. серверу
            echo "NEW CONNECT[$sID]\n";
            $sock[$sID] = $newsock;
            $SESS[$sID]['connecttime']  = time();    //Время первого подключения
            $SESS[$sID]['time']         = time();
            $SESS[$sID]['timeRX']       = time();         //Время последнего получения данных
            $SESS[$sID]['timeTX']       = time();         //Время последней отправки данных
            $SESS[$sID]['trafRX']       = 0;              //Количество полученных байт
            $SESS[$sID]['trafTX']       = 0;              //Количество переданных байт
            socket_getpeername($newsock, $ip, $port);
            $SESS[$sID]['REMOTE_ADDR']  = $ip;       //IP клиента
            $SESS[$sID]['REMOTE_PORT']  = $port;     //PORT клиента
            unset($ip, $port);
            $sID++;

        }
		
?>