<?php

  define("VarUnknown", 0);
  define("VarString", 1);
  define("VarInteger", 2);
  define("VarNumeric", 3);
  define("VarLogical", 4);
  define("VarDate", 5);
  define("VarObject", 6);
  define("VarNull", 10);


  function Error($Msg) {

    throw new Exception($Msg);
  }


  function Sorry() {

    Error("К сожалению, данная функция пока не реализована");
  }