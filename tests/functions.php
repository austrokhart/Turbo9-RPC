<?php

  /* генерирует случайную строку заданной длины */
  function random_string($length) {

    $set = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $set_length = strlen($set);

    $result = "";

    for ($i = 0; $i < $length; $i++) {
      $result .= $set[rand(0, $set_length -1)];
    }

    return $result;
  }