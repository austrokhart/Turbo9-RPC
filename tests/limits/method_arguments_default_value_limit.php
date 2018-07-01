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

    echo "вызов методов со значениями аргументов по-умолчанию...\n";
    //$connection->execute("Tests.RPC", "take_argument_with_default"); // Несоответствующее количество параметров (0 вместо 1)
    $connection->execute("Tests.RPC", "take_argument_with_default", [10]);
    //$connection->execute("Tests.RPC", "take_argument_with_default_other"); // Несоответствующее количество параметров (0 вместо 2)
    //$connection->execute("Tests.RPC", "take_argument_with_default_other", [10]); // Несоответствующее количество параметров (1 вместо 2)
    $connection->execute("Tests.RPC", "take_argument_with_default_other", [10, 15]);

    echo "отключение...\n";
    $connection->disconnect();
  }
  catch (Exception $e) {

    echo "ошибка! ".$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";

    $connection->disconnect();
  }