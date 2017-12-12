<?php

  include_once "cls_Blob.php";

  define("NetCmdNegotiating", 1);
  define("NetCmdCall", 2);
  define("NetCmdRelease", 5);
  define("NetCmdReconnect", 6);
  define("NetCmdDisconnect", 7);

  define("NetRetOk", 1);
  define("NetRetError", 2);
  define("NetRetCallback", 3);

  define("NetProtocolVersion1", 7);
  define("NetProtocolVersion2", 4);

  define("diUser", 3 + 9);
  define("dispIn", 64);
  define("dispOut", 128);

  define("dispVoid", 0);
  define("dispString", 6);

  define("esiCMTAbstract", 13);


  function rpc_connect($server, $port, $service, $guid, &$info) {

    @$sock = fsockopen($server, $port, $errno, $errstr, 5);

    if (!$sock) {
      throw new Exception(sprintf("Socket error %d: %s", $errno, iconv('WINDOWS-1251', 'UTF-8', $errstr)));
    }

    // Negotiating...

    $data =
      chr(NetCmdNegotiating).
      chr(NetProtocolVersion1).
      chr(NetProtocolVersion2).
      rpc_str_serialize($service).
      rpc_guid_serialize($guid).
      rpc_str_serialize(session_id()).                 //session_id() ProcessIdent
      rpc_str_serialize("").                           //SeanceIdent
      rpc_str_serialize($_SERVER['REMOTE_ADDR']).      //ComputerName
      rpc_str_serialize($_SERVER['HTTP_USER_AGENT']).  //UserName
      chr(0).                                          //x32
      chr(0).                                          //NoPack
      chr(0);                                          //NoUnicode

    $res = rpc_communicate($sock, $data);

    $info[0] = rpc_guid_unserialize($res);
    $info[1] = rpc_int_ex_unserialize($res);
    $info[2] = rpc_int_ex_unserialize($res);
    $info[3] = rpc_guid_unserialize($res);

    return $sock;
  }


  function rpc_reconnect($server, $port, $info) {

    @$sock = fsockopen($server, $port, $errno, $errstr, 5);

    if (!$sock) {
      throw new Exception(sprintf("Socket error %d: %s", $errno, $errstr));
    }

    $data =
      chr(NetCmdReconnect).
      chr(NetProtocolVersion1).
      chr(NetProtocolVersion2).
      $info[0].
      rpc_int_ex_serialize($info[1]).
      rpc_int_ex_serialize($info[2]).
      $info[3];

    $res = rpc_communicate($sock, $data);

    return $sock;
  }


  function rpc_call($sock, $method_idx, $arg) {

    // Поддерживается только один формат диспетчеризируемых функций:
    //   function XXX(const Arg :string) :string;
    $data =
      chr(NetCmdCall).
      chr(diUser + $method_idx).
      chr(1).
      chr(dispOut + dispString).
      chr(dispIn + dispString).
      rpc_str_serialize($arg);

    $res = rpc_communicate($sock, $data);
    $n = rpc_int_ex_unserialize($res);

    if ($n <> 1) {
      throw new Exception("RPC protocol error: invalid result count ($n)");
    };

    $t = rpc_int_unserialize($res, 1);

    if ($t <> chr(dispOut + dispString)) {
      throw new Exception('RPC protocol error: invalid result returned');
    };

    $t = rpc_int_unserialize($res, 1);

    return rpc_str_unserialize($res);
  }


  function rpc_call_ex($sock, $method_idx, $class, $proc, $args) {

    $data1 =
      chr(NetCmdCall).
      chr(diUser + $method_idx).
      chr(2).
      chr(dispOut + dispVoid).
      chr(dispIn + dispString).
      rpc_str_serialize($proc).
      chr(dispIn + dispString).
      rpc_str_serialize($class);

    if (is_array($args)) {

      $data2 = chr(count($args));

      foreach ($args as $arg => $val) {
        $data2 .= rpc_value_serialize($val);
      }
    }
    else {

      $data2 = chr(1).rpc_value_serialize($args);
    }

    $res = rpc_communicate($sock, $data1, $data2, $res2);
    $n = rpc_int_ex_unserialize($res);

    if ($n <> 0) {
      throw new Exception("RPC protocol error: invalid result count ($n)");
    };

    return rpc_value_unserialize($res2);
  }


  function rpc_disconnect($sock) {

    $data =
      chr(NetCmdDisconnect);

    $res = rpc_communicate($sock, $data);

    fclose($sock);
  }


  function rpc_standby($sock) {

    $data =
      chr(NetCmdRelease);

    $res = rpc_communicate($sock, $data);

    fclose($sock);
  }


  function rpc_communicate($sock, $data1, $data2 = "", &$res2 = "") {

    rpc_send_packet($sock, $data1, $data2);

    $res = rpc_recv_packet($sock, $res2);

    $code = ord($res{0});
    $res = substr($res, 1);

    if ($code == NetRetError) {
      throw new Exception(sprintf("RPC call return error: %s", rpc_exception_unserialize($res)));
    };

    if ($code == NetRetCallback) {
      throw new Exception("RPC callback not supported");
    }

    return $res;
  }


  function rpc_send_packet($sock, $data1, $data2) {

    $packet =
      "TBNP".
      rpc_int_serialize(strlen($data1)).
      rpc_int_serialize(strlen($data2)).
      $data1.$data2;

    $res = fwrite($sock, $packet);

    if (!$res) {
      throw new Exception("Socket write error");
    }
  }


  function rpc_recv_packet($sock, &$res2) {

    $header = rpc_recv($sock, 12);

    if (substr($header, 0, 4) <> "TBNP") {
      throw new Exception("RPC protocol error");
    };

    $header = substr($header, 4);

    $count1 = rpc_int_unserialize($header);
    $count2 = rpc_int_unserialize($header);

    $res1 = rpc_recv($sock, $count1);
    $res2 = rpc_recv($sock, $count2);

    return $res1;
  }


  function rpc_recv($sock, $len) {

    $res = "";

    while ($len > 0) {

      $str = fread($sock, $len);

      if (!$str) {
        throw new Exception("Socket read error");
      };

      $len -= strlen($str);
      $res .= $str;
    };

    return $res;
  }


  function rpc_unserialize(&$str, $len) {

    $res = substr($str, 0, $len);
    $str = substr($str, $len);
    return $res;
  }


  function rpc_str_serialize($val) {

    if (!$val) {
      return chr(0);
    }
    else {
      $str = iconv('UTF-8', 'UTF-16LE', $val);
      return chr(2).rpc_int_ex_serialize(strlen($str) / 2).$str;
    }
  }


  function rpc_str_unserialize(&$str) {

    $code = rpc_int_unserialize($str, 1);

    if (!$code) {

      return "";
    }
    elseif ($code == 1) {

      $len = rpc_int_ex_unserialize($str);
      $res = rpc_unserialize($str, $len);

      return iconv('WINDOWS-1251', 'UTF-8', $res);
    }
    elseif ($code == 2) {

      $len = rpc_int_ex_unserialize($str);
      $res = rpc_unserialize($str, $len * 2);

      return iconv('UTF-16LE', 'UTF-8', $res);
    }
    else {

      throw new Exception("RPC protocol error: string read error");
    }
  }


  function rpc_int_serialize($val, $size = 4) {

    for ($str = "", $i = 0; $i < $size; $i++) {
      $str .= chr($val % 256);
      $val  = floor($val / 256);
    }

    return $str;
  }


  function rpc_int_unserialize(&$str, $size = 4) {

    $res = 0;

    for ($i = $size - 1; $i >= 0; $i--) {
      $res = ($res * 256) + ord($str{$i});
    };

    $str = substr($str, $size);

    return $res;
  }


  function rpc_int_ex_serialize($val) {

    if (($val >= 0) && ($val <= 253)) {
      return chr($val);
    }
    elseif (($val >= -32768) && ($val <= 32767)) {
      return chr(254).rpc_int_serialize($val, 2);
    }
    else {
      return chr(255).rpc_int_serialize($val, 4);
    }
  }


  function rpc_int_ex_unserialize(&$str) {

    $res = rpc_int_unserialize($str, 1);

    if ($res <= 253) {
      return $res;
    };
    if ($res == 254) {
      return rpc_int_unserialize($str, 2);
    }
    else {
      return rpc_int_unserialize($str, 4);
    }
  }


  function rpc_guid_serialize($id) {

    $str = strtr($id, array("{" => "", "}" => "", "-" => ""));
    $bin = "";

    for ($i = 0; $i < strlen($str); $i += 2) {
      $bin .= chr(hexdec($str{$i}.$str{$i + 1}));
    }

    $res = "";

    for ($i = 3; $i >= 0; $i--) {
      $res .= $bin{$i};
    }

    for ($i = 5; $i >= 4; $i--) {
      $res .= $bin{$i};
    }

    for ($i = 7; $i >= 6; $i--) {
      $res .= $bin{$i};
    }

    for ($i = 8; $i < 16; $i++) {
      $res .= $bin{$i};
    }

    return $res;
  }


  function rpc_guid_unserialize(&$str) {

    $guid = rpc_unserialize($str, 16);
    return $guid;
  }


  function rpc_value_serialize($val) {

    if (is_string($val)) {
      $res = chr(VarString).rpc_str_serialize($val);
    }
    elseif (is_int($val)) {
      $res = chr(VarInteger).rpc_int_serialize($val);
    }
    elseif (is_bool($val)) {
      $res = chr(VarLogical).($val ? 1 : 0);
    }
    elseif (is_float($val)) {
      Sorry();
    }
    elseif (is_object($val)) {
      $res = chr(VarObject).rpc_object_serialize($val);
    }
    else {
      throw new Exception("RPC protocol error: invalid argument type");
    }

    return $res;
  }


  function rpc_value_unserialize(&$str) {

    $res = null;

    if ($str) {

      $type = rpc_int_unserialize($str, 1);

      switch ($type) {
        case VarString:
          $res = rpc_str_unserialize($str);
          break;
        case VarInteger:
          $res = rpc_int_unserialize($str, 4);
          break;
        case VarLogical:
          $res = rpc_int_unserialize($str, 1) <> 0;
          break;
        case VarNumeric:
          Sorry();
          break;
        case VarDate:
          Sorry();
          break;
        case VarNull:
          break;
        case VarObject:
          $res = rpc_object_unserialize($str);
          break;
        default:
          throw new Exception("RPC protocol error: invalid result returned");
      }
    }

    return $res;
  }


  function rpc_object_serialize($obj) {

    return
      chr(esiCMTAbstract).
      rpc_str_serialize('Kernel').
      rpc_str_serialize($obj->GetClassName()).
      $obj->SerializeToStr();
  }


  function rpc_object_unserialize(&$str) {

    $res = null;
    $code = rpc_int_unserialize($str, 1);

    if ($code <> 0) {

      if ($code <> esiCMTAbstract) {
        throw new Exception("RPC protocol error: unknown object code ($code)");
      }

      $pckname = rpc_str_unserialize($str);
      $clsname = rpc_str_unserialize($str);

      if ((strtolower($pckname) == 'kernel') && (strtolower($clsname) == 'binaryobject')) {
        $res = new TBBlob('');
        $res->UnserializeFromStr($str);
      }

      if (!$res) {
        throw new Exception("RPC protocol error: unknown class: $pckname.$clsname");
      }
    }

    return $res;
  }


  function rpc_exception_unserialize(&$str) {

    $class = rpc_str_unserialize($str);
    $code = rpc_int_ex_unserialize($str);
    $mess = rpc_str_unserialize($str);

    return $mess;
  }