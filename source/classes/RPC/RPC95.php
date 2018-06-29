<?php

  namespace Turbo9\RPC\RPC95;

  
  class RPC {
    
    
    const VarUnknown = 0;
    const VarString  = 1;
    const VarInteger = 2;
    const VarNumeric = 3;
    const VarLogical = 4;
    const VarDate    = 5;
    const VarObject  = 6;
    const VarNull    = 10;

    const NetCmdNegotiating = 1;
    const NetCmdCall        = 2;
    const NetCmdRelease     = 5;
    const NetCmdReconnect   = 6;
    const NetCmdDisconnect  = 7;
  
    const NetRetOk       = 1;
    const NetRetError    = 2;
    const NetRetCallback = 3;
  
    const NetProtocolVersion1 = 7;
    const NetProtocolVersion2 = 4;
  
    const diUser  = 3 + 9;
    const dispIn  = 64;
    const dispOut = 128;
  
    const dispVoid   = 0;
    const dispString = 6;
  
    const esiCMTAbstract = 13;


    static function connect($server, $port, $service, $guid, &$info) {

      @$sock = fsockopen($server, $port, $errno, $errstr, 5);

      if (!$sock) {
        throw new \Exception(sprintf("Socket error %d: %s", $errno, iconv('WINDOWS-1251', 'UTF-8', $errstr)));
      }

      // Negotiating...

      $data =
        chr(self::NetCmdNegotiating).
        chr(self::NetProtocolVersion1).
        chr(self::NetProtocolVersion2).
        self::str_serialize($service).
        self::guid_serialize($guid).
        self::str_serialize(session_id()).                 //session_id() ProcessIdent
        self::str_serialize("").                           //SeanceIdent
        @self::str_serialize($_SERVER['REMOTE_ADDR']).      //ComputerName
        @self::str_serialize($_SERVER['HTTP_USER_AGENT']).  //UserName
        chr(0).                                          //x32
        chr(0).                                          //NoPack
        chr(0);                                          //NoUnicode

      $res = self::communicate($sock, $data);

      $info[0] = self::guid_unserialize($res);
      $info[1] = self::int_ex_unserialize($res);
      $info[2] = self::int_ex_unserialize($res);
      $info[3] = self::guid_unserialize($res);

      return $sock;
    }


    static function reconnect($server, $port, $info) {

      @$sock = fsockopen($server, $port, $errno, $errstr, 5);

      if (!$sock) {
        throw new \Exception(sprintf("Socket error %d: %s", $errno, $errstr));
      }

      $data =
        chr(self::NetCmdReconnect).
        chr(self::NetProtocolVersion1).
        chr(self::NetProtocolVersion2).
        $info[0].
        self::int_ex_serialize($info[1]).
        self::int_ex_serialize($info[2]).
        $info[3];

      self::communicate($sock, $data);

      return $sock;
    }


    static function call($sock, $method_idx, $arg) {

      // Поддерживается только один формат диспетчеризируемых функций:
      //   function XXX(const Arg :string) :string;
      $data =
        chr(self::NetCmdCall).
        chr(self::diUser + $method_idx).
        chr(1).
        chr(self::dispOut + self::dispString).
        chr(self::dispIn + self::dispString).
        self::str_serialize($arg);

      $res = self::communicate($sock, $data);
      $n = self::int_ex_unserialize($res);

      if ($n <> 1) {
        throw new \Exception("RPC protocol error: invalid result count ($n)");
      };

      $t = self::int_unserialize($res, 1);

      if ($t <> chr(self::dispOut + self::dispString)) {
        throw new \Exception('RPC protocol error: invalid result returned');
      };

      self::int_unserialize($res, 1);

      return self::str_unserialize($res);
    }


    static function call_ex($sock, $method_idx, $class, $proc, $args) {

      $data1 =
        chr(self::NetCmdCall).
        chr(self::diUser + $method_idx).
        chr(2).
        chr(self::dispOut + self::dispVoid).
        chr(self::dispIn + self::dispString).
        self::str_serialize($proc).
        chr(self::dispIn + self::dispString).
        self::str_serialize($class);

      if (is_array($args)) {

        $data2 = chr(count($args));

        foreach ($args as $arg => $val) {
          $data2 .= self::value_serialize($val);
        }
      }
      else {

        $data2 = chr(1).self::value_serialize($args);
      }

      $res = self::communicate($sock, $data1, $data2, $res2);
      $n = self::int_ex_unserialize($res);

      if ($n <> 0) {
        throw new \Exception("RPC protocol error: invalid result count ($n)");
      };

      return self::value_unserialize($res2);
    }


    static function disconnect($sock) {

      $data = chr(self::NetCmdDisconnect);

      self::communicate($sock, $data);
      fclose($sock);
    }


    static function standby($sock) {

      $data = chr(self::NetCmdRelease);

      self::communicate($sock, $data);
      fclose($sock);
    }


    static function communicate($sock, $data1, $data2 = "", &$res2 = "") {

      self::send_packet($sock, $data1, $data2);

      $res = self::recv_packet($sock, $res2);

      $code = ord($res{0});
      $res = substr($res, 1);

      if ($code == self::NetRetError) {
        throw new \Exception(sprintf("RPC call return error: %s", self::exception_unserialize($res)));
      };

      if ($code == self::NetRetCallback) {
        throw new \Exception("RPC callback not supported");
      }

      return $res;
    }


    static function send_packet($sock, $data1, $data2) {

      $packet =
        "TBNP".
        self::int_serialize(strlen($data1)).
        self::int_serialize(strlen($data2)).
        $data1.$data2;

      $res = fwrite($sock, $packet);

      if (!$res) {
        throw new \Exception("Socket write error");
      }
    }


    static function recv_packet($sock, &$res2) {

      $header = self::recv($sock, 12);

      if (substr($header, 0, 4) <> "TBNP") {
        throw new \Exception("RPC protocol error");
      };

      $header = substr($header, 4);

      $count1 = self::int_unserialize($header);
      $count2 = self::int_unserialize($header);

      $res1 = self::recv($sock, $count1);
      $res2 = self::recv($sock, $count2);

      return $res1;
    }


    static function recv($sock, $len) {

      $res = "";

      while ($len > 0) {

        $str = fread($sock, $len);

        if (!$str) {
          throw new \Exception("Socket read error");
        };

        $len -= strlen($str);
        $res .= $str;
      };

      return $res;
    }


    static function unserialize(&$str, $len) {

      $res = substr($str, 0, $len);
      $str = substr($str, $len);
      return $res;
    }


    static function str_serialize($val) {

      if (!$val) {
        return chr(0);
      }
      else {
        $str = iconv('UTF-8', 'UTF-16LE', $val);
        return chr(2).self::int_ex_serialize(strlen($str) / 2).$str;
      }
    }


    static function str_unserialize(&$str) {

      $code = self::int_unserialize($str, 1);

      if (!$code) {

        return "";
      }
      elseif ($code == 1) {

        $len = self::int_ex_unserialize($str);
        $res = self::unserialize($str, $len);

        return iconv('WINDOWS-1251', 'UTF-8', $res);
      }
      elseif ($code == 2) {

        $len = self::int_ex_unserialize($str);
        $res = self::unserialize($str, $len * 2);

        return iconv('UTF-16LE', 'UTF-8', $res);
      }
      else {

        throw new \Exception("RPC protocol error: string read error");
      }
    }


    static function int_serialize($val, $size = 4) {

      for ($str = "", $i = 0; $i < $size; $i++) {
        $str .= chr($val % 256);
        $val = floor($val / 256);
      }

      return $str;
    }


    static function int_unserialize(&$str, $size = 4) {

      $res = 0;

      for ($i = $size - 1; $i >= 0; $i--) {
        $res = ($res * 256) + ord($str{$i});
      };

      $str = substr($str, $size);

      return $res;
    }


    static function int_ex_serialize($val) {

      if (($val >= 0) && ($val <= 253)) {
        return chr($val);
      }
      elseif (($val >= -32768) && ($val <= 32767)) {
        return chr(254).self::int_serialize($val, 2);
      }
      else {
        return chr(255).self::int_serialize($val, 4);
      }
    }


    static function int_ex_unserialize(&$str) {

      $res = self::int_unserialize($str, 1);

      if ($res <= 253) {
        return $res;
      };
      if ($res == 254) {
        return self::int_unserialize($str, 2);
      }
      else {
        return self::int_unserialize($str, 4);
      }
    }


    static function guid_serialize($id) {

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


    static function guid_unserialize(&$str) {

      $guid = self::unserialize($str, 16);
      return $guid;
    }


    static function value_serialize($val) {

      if (is_string($val)) {
        $res = chr(self::VarString).self::str_serialize($val);
      }
      elseif (is_int($val)) {
        $res = chr(self::VarInteger).self::int_serialize($val);
      }
      elseif (is_bool($val)) {
        $res = chr(self::VarLogical).($val ? 1 : 0);
      }
      elseif (is_float($val)) {
        self::not_available();
      }
      elseif (is_object($val)) {
        $res = chr(self::VarObject).self::object_serialize($val);
      }
      else {
        throw new \Exception("RPC protocol error: invalid argument type");
      }

      return $res;
    }


    static function value_unserialize(&$str) {

      $res = null;

      if ($str) {

        $type = self::int_unserialize($str, 1);

        switch ($type) {
          case self::VarString:
            $res = self::str_unserialize($str);
            break;
          case self::VarInteger:
            $res = self::int_unserialize($str, 4);
            break;
          case self::VarLogical:
            $res = self::int_unserialize($str, 1) <> 0;
            break;
          case self::VarNumeric:
            self::not_available();
            break;
          case self::VarDate:
            self::not_available();
            break;
          case self::VarNull:
            break;
          case self::VarObject:
            $res = self::object_unserialize($str);
            break;
          default:
            throw new \Exception("RPC protocol error: invalid result returned");
        }
      }

      return $res;
    }


    static function object_serialize($obj) {

      return
        chr(self::esiCMTAbstract).
        self::str_serialize('Kernel').
        self::str_serialize($obj->GetClassName()).
        $obj->SerializeToStr();
    }


    static function object_unserialize(&$str) {

      $res = null;
      $code = self::int_unserialize($str, 1);

      if ($code <> 0) {

        if ($code <> self::esiCMTAbstract) {
          throw new \Exception("RPC protocol error: unknown object code ($code)");
        }

        $pckname = self::str_unserialize($str);
        $clsname = self::str_unserialize($str);

        if ((strtolower($pckname) == 'kernel') && (strtolower($clsname) == 'binaryobject')) {
          $res = new TBBlob('');
          $res->UnserializeFromStr($str);
        }

        if (!$res) {
          throw new \Exception("RPC protocol error: unknown class: $pckname.$clsname");
        }
      }

      return $res;
    }


    static function exception_unserialize(&$str) {

      self::str_unserialize($str);
      self::int_ex_unserialize($str);

      $mess = self::str_unserialize($str);

      return $mess;
    }


    static function not_available() {

      throw new \Exception("К сожалению, данная функция пока не реализована");
    }
  }

  
  class TBBlob {


    public $Data; // Строка данных


    function __construct($Data) {

      $this->Data = $Data;
    }


    function GetClassName() {

      return 'BinaryObject';
    }


    function SerializeToStr() {

      return RPC::int_ex_serialize(strlen($this->Data)).$this->Data;
    }


    function UnSerializeFromStr(&$str) {

      $len = RPC::int_ex_unserialize($str);
      $this->Data = RPC::unserialize($str, $len);
    }
  }