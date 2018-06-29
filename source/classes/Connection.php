<?php

  namespace Turbo9\RPC\Connection;
  use Turbo9\RPC\RPC95\RPC;


  /* класс для работы с подключением к Турбо9
     подключение проходит через T9WebProxy, класс является удобной обёрткой для функций библиотеки из стандартной поставки */
  class Connection {


    public $settings;

    public $socket = 0;
    public $info;


    function __construct(Settings $settings) {

      /* конструктор принимает объект класса с настройками */
      $this->settings = $settings;
    }


    /* производит подключение */
    function connect() {

      $this->socket = RPC::connect(
        $this->settings->proxy_server,
        $this->settings->proxy_port,
        $this->settings->proxy_service,
        $this->settings->proxy_guid,
        $info /* здесь запишутся данные о подключении, используются для восстановления подключения */
      );

      $this->info = $info;

      return $this;
    }


    /* производит авторизацию */
    function authorize(string $infobase, string $username, string $password, string $role = "") {

      $data = [
        $this->settings->proc_server,
        $this->settings->data_server,
        $infobase,
        $username,
        $password,
        $role
      ];

      RPC::call(
        $this->socket,
        0,
        implode("|", $data)
      );

      return $this;
    }


    /* производит вызов метода класса (сервера процедур Турбо9) */
    function execute(string $class_name, string $method_name, array $arguments = []) {

      return RPC::call_ex(
        $this->socket,
        3,
        $class_name,
        $method_name,
        $arguments
      );
    }


    /* производит перевод подключения в режим ожидания */
    function standby() {

      RPC::standby($this->socket);
      $this->socket = 0;

      return $this;
    }


    /* производит восстановление подключения */
    function restore() {

      $this->socket = RPC::reconnect(
        $this->settings->proxy_server,
        $this->settings->proxy_port,
        $this->info
      );

      return $this;
    }


    /* производит отключение */
    function disconnect() {

      RPC::disconnect($this->socket);
      $this->socket = 0;

      return $this;
    }
  }