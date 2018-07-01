<?php

  require_once "../../source/classes/RPC/RPC95.php";
  require_once "../../source/classes/Connection.php";
  require_once "../../source/classes/Settings.php";

  require_once "../classes/Timer.php";
  require_once "../functions.php";
  require_once "../settings.php";

  use Turbo9\RPC\Connection;

  $connection = new Connection\Connection(new Connection\Settings($proxy_server, $proxy_port));

  try {

    echo "подключение...\n";
    $connection->connect();

    echo "авторизация...\n";
    $connection->authorize($infobase, $username, $password);

    echo "передача пустого значения...\n";
    $connection->execute("Tests.RPC", "take_nothing");

    echo "передача null...\n";
    //$connection->execute("Tests.RPC", "take_null", [null]); // RPC protocol error: invalid argument type

    echo "передача boolean...\n";
    //$connection->execute("Tests.RPC", "take_boolean",  []); // Несоответствующее количество параметров (0 вместо 1)
    //$connection->execute("Tests.RPC", "take_boolean",  [null]); // RPC protocol error: invalid argument type
    $connection->execute("Tests.RPC", "take_boolean",  [false]);
    $connection->execute("Tests.RPC", "take_boolean",  [true]);
    $connection->execute("Tests.RPC", "take_booleans", [false, true]);


    echo "передача integer...\n";
    $connection->execute("Tests.RPC", "take_integer",  [-10]);
    $connection->execute("Tests.RPC", "take_integer",  [10]);
    $connection->execute("Tests.RPC", "take_integers", [-10, 10]);

    echo "передача real...\n";
    //$connection->execute("Tests.RPC", "take_real",  [-10.33]); // К сожалению, данная функция пока не реализована
    //$connection->execute("Tests.RPC", "take_real",  [10.33]);
    //$connection->execute("Tests.RPC", "take_reals", [-10.33, 10.33]);

    echo "передача string...\n";
    $connection->execute("Tests.RPC", "take_string",  ["abc"]);
    $connection->execute("Tests.RPC", "take_strings", ["abc", "abc"]);

    echo "передача array...\n";
    //$connection->execute("Tests.RPC", "take_array",  [["abc"]]); // RPC protocol error: invalid argument type
    //$connection->execute("Tests.RPC", "take_strings", [["abc"], ["abc"]]);

    echo "передача variant...\n";
    //$connection->execute("Tests.RPC", "take_variant", [null]); // RPC protocol error: invalid argument type
    $connection->execute("Tests.RPC", "take_variant", [true]);
    $connection->execute("Tests.RPC", "take_variant", [10]);
    //$connection->execute("Tests.RPC", "take_variant", [10.33]); // К сожалению, данная функция пока не реализована
    $connection->execute("Tests.RPC", "take_variant", ["abc"]);
    //$connection->execute("Tests.RPC", "take_variant", [["abc"]]); // RPC protocol error: invalid argument type

    echo "отключение...\n";
    $connection->disconnect();
  }
  catch (Exception $e) {

    echo "ошибка! ".$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";

    $connection->disconnect();
  }