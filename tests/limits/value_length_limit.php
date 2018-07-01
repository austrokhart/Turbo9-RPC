<?php

  require_once "../../source/classes/RPC/RPC95.php";
  require_once "../../source/classes/Connection.php";
  require_once "../../source/classes/Settings.php";

  require_once "../classes/Timer.php";
  require_once "../functions.php";
  require_once "../settings.php";

  use Turbo9\RPC\Connection;

  /* передаёт случайные строки длины от from до to с шагом step в метод класса,
     который возвращает полученную строку, и выполняет сравнение переданного и полученного значения */
  function handler(Connection\Connection $connection, int $from, int $to, int $step) {

    for ($i = $from; $i <= $to; $i += $step) {

      try {

        $str = random_string($i);

        echo "передача строки длиной $i символов... ";
        $result = $connection->execute("Tests.RPC", "reflect_str", [$str]);

        if ($result !== $str) throw new Exception("неудача! ожидалось $str, получено $result\n");
        echo "успех!\n";
      }
      catch (Exception $e) {
        echo "неудача! ".$e->getMessage()."\n";
      }
    }
  }

  $connection = new Connection\Connection(new Connection\Settings($proxy_server, $proxy_port));

  try {

    echo "подключение...\n";
    $connection->connect();

    echo "авторизация...\n";
    $connection->authorize($infobase, $username, $password);

    echo "тестирование...\n";
    echo "от 0 до 100`000 символов с шагом 5000:\n";
    handler($connection, 0, 100000, 5000);

    echo "от 0 до 1`000`000 символов с шагом 50`000:\n";
    handler($connection, 0, 1000000, 50000);

    echo "от 0 до 100`000`000 символов с шагом 500`000:\n";
    handler($connection, 0, 100000000, 500000);

    echo "отключение...\n";
    $connection->disconnect();
  }
  catch (Exception $e) {

    echo "ошибка! ".$e->getMessage()."\n";
    $connection->disconnect();
  }