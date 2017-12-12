<?

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


  function MaskQoute($str) {
    //  return strtr($str, array('"'=>'&quot;', "'"=>'&apos;', '&'=>'&amp;', '<'=>'&lt;', '>'=>'&gt;'));
    return strtr($str, array('"' => '&quot;', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;'));
  }


  function MaskBin($str) {
    return strtr(MaskQoute($str), array(chr(0) => '&nbsp;'));
  }


  function GetToday() {
    return date('d.m.Y', time());
  }


  function GetNow() {
    return UTimeToDateTime(time());
  }


  function UTimeToDateTime($UTime) {
    return date('d.m.Y H:i:s', $UTime);
  }


  function redirect_timeout($URL, $Timeout) {
    print "<script type='text/javascript'>";
    print "  setTimeout(\"location.href='$URL'\",{$Timeout});";
    print "</script>";
  }


  function WinCloseTimeout($Timeout) {
    print "<script type='text/javascript'>";
    print "  setTimeout('window.close()',{$Timeout});";
    print "</script>";
  }


  function DlgCloseTimeout($Timeout) {
    print "<script type='text/javascript'>";
    print "  setTimeout('window.top.hidePopWin(true)',{$Timeout});";
    print "</script>";
  }


  function PrintErrors($Errors) {
    $n = count($Errors);

    print "<div class=\"error\">\n";
    print "<b>Ошибка</b><br>\n";
    print "<ul>\n";

    for ($i = 0; $i < $n; $i++) {
      print "<li>".MaskQoute($Errors[$i])."</li>\n";
    }

    print "</ul>\n";
    print "</div>\n\n";
  }


  function HandleError($Error) {
    if (is_object($Error)) {
      PrintErrors(array($Error->GetMessage()));
    }
    else {
      PrintErrors(array($Error));
    }
  }

  ;


  function ChangeURL($UrlStr, $Set, $Unset = NULL) {
    $Url = parse_url($UrlStr);

    $Args = ArgsExplode($Url["query"]);

    if (is_array($Unset)) {
      foreach ($Unset as $Idx => $Arg) {
        unset($Args[$Arg]);
      }
    }

    if (is_array($Set)) {
      foreach ($Set as $Arg => $Val) {
        $Args[$Arg] = urlencode($Val);
      }
    }

    $Res = $Url["path"];
    if ($Args) {
      $Res .= "?".ArgsImplode($Args);
    };

    return $Res;
  }


  // "Arg1=Val1&Arg2=Val2"  -->  array(Arg1=>Val1, Arg2=Val2)
  function ArgsExplode($ArgsStr) {
    $Res = array();
    $Tmp = explode("&", $ArgsStr);
    foreach ($Tmp as $Idx => $Def) {
      $Arg = strtok($Def, "=");
      $Val = strtok("");
      if ($Arg && ($Val != "")) {
        $Res[$Arg] = $Val;
      };
    }
    return $Res;
  }


  // array(Arg1=>Val1, Arg2=Val2)  --> "Arg1=Val1&Arg2=Val2"
  function ArgsImplode($ArgsArray) {
    $Tmp = array();
    foreach ($ArgsArray as $Arg => $Val) {
      $Tmp[] = $Arg."=".$Val;
    }
    return implode("&", $Tmp);
  }


  function StrExplodeEx($AStr) {
    $vRes = Array();

    $vStrLen = strlen($AStr);
    $vPos = 0;

    while ($vPos < $vStrLen) {
      $vChr = $AStr[$vPos];

      if ($vChr != " ") {
        if (($vChr == "'") || ($vChr == '"')) {
          $vStr = StrExtractQuoted($AStr, &$vPos, $vChr);
        }
        else {
          $vLen = strcspn($AStr, " ", $vPos);
          $vStr = substr($AStr, $vPos, $vLen);
          $vPos += $vLen;
        }

        $vRes[] = $vStr;
      };

      while (($vPos < $vStrLen) && ($AStr[$vPos] == " ")) {
        $vPos++;
      }

    } //while

    return $vRes;
  }


  function StrExtractQuoted($AStr, &$APos, $AQuote) {
    if ($AStr[$APos] != $AQuote) {
      return "";
    }

    $vRes = '';

    $vStrLen = strlen($AStr);
    $APos++;

    while ($APos < $vStrLen) {
      $vPos = strpos($AStr, $AQuote, $APos);
      if ($vPos === false) {
        $vPos = $vStrLen;
      }

      $vRes .= substr($AStr, $APos, $vPos - $APos);
      $APos = $vPos;

      if ($APos < $vStrLen) {
        $APos++;

        if (($APos < $vStrLen) && ($AStr[$APos] == $AQuote)) {
          $vRes .= $AQuote;
          $APos++;
        }
        else {
          break;
        }
      };
    } //while

    return $vRes;
  }


  function AddFilter($AStr, $ACond) {
    if ($AStr) {
      $AStr .= " and ".$ACond;
    }
    else {
      $AStr = $ACond;
    };

    return $AStr;
  }


  function ExtractFileExt($FileName) {
    $Pos = strrpos($FileName, '.');
    if ($Pos) {
      return substr($FileName, $Pos + 1);
    }
    else {
      return $FileName;
    };
  }


  function FormatSize($Size) {
    if ($Size < 1024) {
      return $Size;
    }
    elseif ($Size < 1024 * 1024) {
      $Size = $Size / 1024;
      $Size = ($Size < 10 ? round($Size, 1) : round($Size));
      return $Size." Кб";
    }
    else {
      $Size = $Size / (1024 * 1024);
      $Size = ($Size < 10 ? round($Size, 1) : round($Size));
      return $Size." Мб";
    }
  }

  ;

?>