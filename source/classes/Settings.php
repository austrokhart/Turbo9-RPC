<?php

  namespace Turbo9\RPC\Connection;


  /* класс для хранения настроек подключения */
  class Settings {


    /* данные для подключения к T9WebProxy */
    public $proxy_server;
    public $proxy_port;
    public $proxy_service = "T9WebProxy";
    public $proxy_guid    = "{9B4F96CB-39A1-4EA7-B3BB-052203517FD9}";

    /* данные для подключения к серверу данных и рассчётов относительно T9WebProxy */
    public $data_server;
    public $proc_server;


    function __construct(string $proxy_server, string $proxy_port, string $data_server = "localhost", string $proc_server = "localhost") {

      $this->proxy_server = $proxy_server;
      $this->proxy_port   = $proxy_port;
      $this->data_server  = $data_server;
      $this->proc_server  = $proc_server;
    }
  }