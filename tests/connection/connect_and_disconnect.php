<?php

  require_once "../../source/classes/RPC/RPC95.php";
  require_once "../../source/classes/Connection.php";
  require_once "../../source/classes/Settings.php";

  require_once "../classes/Timer.php";
  require_once "../settings.php";

  use Turbo9\RPC\Connection;

  $connection = new Connection\Connection(new Connection\Settings($proxy_server, $proxy_port));

  try {

    echo "подключение...\n";
    $connection->connect();

    echo "авторизация...\n";
    $connection->authorize($infobase, $username, $password);

    echo "вызов метода do_nothing...\n";
    $connection->execute("Tests.RPC", "do_nothing");

    echo "успех!\n";
    echo "отключение...\n";
    $connection->disconnect();
  }
  catch (Exception $e) {

    echo "ошибка! ".$e->getMessage()."\n";
    $connection->disconnect();
  }