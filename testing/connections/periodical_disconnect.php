<?php

  require_once "../Timer.php";
  require_once "../Settings.php";
  require_once "../Connection.php";
  require_once "../../source/classes/9.5/RPC.php";

  use Swiftdoc\TurboRPC\RPC95\RPC;

  try {

    $connection = new Connection(new Settings());

    while (true) {

      sleep(3);

      $timer = new Timer();

      echo "открытие соединения...\n";
      $connection->connect();

      echo "попытка авторизации...\n";
      $connection->authorize("Администратор", "1");

      echo "авторизация успешна\n";
      echo "запрос...\n";

      var_dump(
        RPC::call(
          $connection->socket,
          1,
          implode("|", [
            "Web.Docs",
            "GetDocument",
            "{Kernel.Settings.User:1}"
          ])
        )
      );

      echo "завершение работы...\n";
      $connection->disconnect();

      echo "времени прошло: ".($timer->passed())."\n";
    }
  }
  catch (Exception $e) {

    echo "ошибка!\n";
    print_r($e);
  }