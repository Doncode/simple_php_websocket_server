<?php
/*
*
* Читает сокет. Находит ключ $keyINsock. Конфертирет кодировку
*
*/
$str = socket_read($nowread, 2048, PHP_BINARY_READ);

/** 		Узнаем ключ в родительском массиве		* */
foreach ($sock as $key2 => $res) {
    if ($res === $nowread) {
        $keyINsock = $key2;
    }
}
//save_to_file("{$sock[$keyINsock]} => " . bin2hex($str));
$SESS[$keyINsock]['trafRX']+= strlen($str);
$SESS[$keyINsock]['timeRX'] = time();
$str = trim($str);
$strlen = strlen($str);
echo "RX[$keyINsock]: $str\n";