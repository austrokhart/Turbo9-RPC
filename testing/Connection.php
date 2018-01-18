<?php

  use Swiftdoc\TurboRPC\RPC95\RPC;


  class Connection {


    public $settings;

    public $socket = 0;
    public $info;


    function __construct(Settings $settings) {

      $this->settings = $settings;
    }


    function connect() {

      $this->socket = RPC::connect(
        $this->settings->proxy_server,
        $this->settings->proxy_port,
        $this->settings->proxy_service,
        $this->settings->proxy_guid,
        $info # здесь запишутся данные о подключении
      );

      $this->info = $info;
    }


    function standby() {

      RPC::standby($this->socket);
      $this->socket = 0;
    }


    function restore() {

      $this->socket = RPC::reconnect(
        $this->settings->proxy_server,
        $this->settings->proxy_port,
        $this->info
      );
    }


    function disconnect() {

      RPC::disconnect($this->socket);
      $this->socket = 0;
    }


    function authorize($username, $password, $role = "") {

      $data = [
        $this->settings->proc_server,
        $this->settings->data_server,
        $this->settings->extract_information_base()["name"],
        $username,
        $password,
        $role
      ];

      RPC::call(
        $this->socket,
        0,
        implode("|", $data)
      );
    }
  }