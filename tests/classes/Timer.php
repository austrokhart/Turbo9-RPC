<?php

  class Timer {


    public $stamp;


    function __construct() {

      $this->update();
    }


    function update() {

      $this->stamp = microtime(true);
    }


    function passed() {

      return (microtime(true) -$this->stamp);
    }
  }