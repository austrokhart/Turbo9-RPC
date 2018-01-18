<?php

  class Settings {


    public $proc_server   = "localhost"; # сервер расчётов
    public $data_server   = "localhost"; # сервер данных

    public $information_bases = [
      "T9SED" => [
        "code" => "T9_Electrobalt_bak_1",
        "name" => "T9_Electrobalt_bak_1",
        "link" => "/Reports/tasks"
      ]
    ];

    public $proxy_server  = "192.168.1.115";
    public $proxy_port    = "25900";
    public $proxy_service = "T9WebProxy";
    public $proxy_guid    = "{9B4F96CB-39A1-4EA7-B3BB-052203517FD9}";

    public $charset       = "utf-8";


    function extract_information_base($name = null) {

      if ($name) {

        $base = $this->information_bases[$name];
      }
      else {

        $base = array_values($this->information_bases);
        $base = array_shift($base);
      }

      return $base;
    }
  }