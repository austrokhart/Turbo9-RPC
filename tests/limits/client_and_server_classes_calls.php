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

    //echo "вызовы клиентского класса...\n";
    //echo $connection->execute("Tests.ClientClass", "self_value")."\n"; // Класс Tests.ClientClass не найден
    //echo $connection->execute("Tests.ClientClass", "server_value")."\n";

    echo "вызовы серверного класса...\n";
    echo $connection->execute("Tests.ServerClass", "self_value")."\n";
    //echo $connection->execute("Tests.ServerClass", "client_value")."\n"; // Класс "Jet.Tests.ClientClass" не найден или недоступен

    echo "отключение...\n";
    $connection->disconnect();
  }
  catch (Exception $e) {

    echo "ошибка! ".$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";

    $connection->disconnect();
  }