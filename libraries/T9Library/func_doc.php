<?

//-----------------------------------------------------------------------------
// Документ из строки в массив

  function StrDocToArrDoc($AStr)
  {
    $vPos = 0;
    return StrDocExtractStruct($AStr, $vPos);
  }


  function StrDocExtractStruct($AStr, &$APos)
  {
    if ($AStr[$APos] != "[") 
      { BadFormat($APos, $AStr); }

    $vRes = Array();

    $vStrLen = strlen($AStr);
    $APos++;

    while (($APos < $vStrLen) && ($AStr[$APos] != ']'))
    {
      $vPos = strpos($AStr, '=', $APos);
      if ($vPos === FALSE)
        { BadFormat($APos, $AStr); }

      $vField = substr($AStr, $APos, $vPos - $APos);
      $vPos++;

      if ($AStr[$vPos] == "[")
      {
        $vValue = StrDocExtractSubtable($AStr, $vPos);
      }
      else
      {
        if ($AStr[$vPos] == '"')
        {
          $vValue = StrDocExtractStr($AStr, $vPos);
        }
        else
        {
          $vLen = strcspn($AStr, ',]', $vPos);
          $vValue = substr($AStr, $vPos, $vLen);
          $vPos += $vLen;
        }
      }

      $vRes[$vField] = $vValue;

      if ($AStr[$vPos] == ',')
        {$vPos++;}

      $APos = $vPos;
    } //while

    if ($APos >= $vStrLen) 
      { BadFormat($APos, $AStr); }

    $APos++;
    return $vRes;
  }


  function StrDocExtractSubtable($AStr, &$APos)
  {
    if ($AStr[$APos] != "[") 
      { BadFormat($APos, $AStr); }

    $vRes = Array();

    $vStrLen = strlen($AStr);
    $APos++;

    while (($APos < $vStrLen) && ($AStr[$APos] != ']'))
    {
      $vPos = strpos($AStr, '=', $APos);
      if ($vPos === FALSE)
        { BadFormat($APos, $AStr); }

      $vIndex = substr($AStr, $APos, $vPos - $APos);
      $vPos++;

      if ($AStr[$vPos] == "[")
      {
        $vValue = StrDocExtractStruct($AStr, $vPos);
      }
      else
        { BadFormat($vPos, $AStr); }

      $vRes[$vIndex] = $vValue;

      if ($AStr[$vPos] == ',')
        {$vPos++;}

      $APos = $vPos;
    } //while

    if ($APos >= $vStrLen) 
      { BadFormat($APos, $AStr); }

    $APos++;

    return $vRes;
  }


  function StrDocExtractStr($AStr, &$APos)
  {
    if ($AStr[$APos] != '"') 
      { BadFormat($APos, $AStr); }

    $vRes = '';

    $vStrLen = strlen($AStr);
    $APos++;

    while ($APos < $vStrLen)
    {
      $vPos = strpos($AStr, '"', $APos);
      if ($vPos === FALSE)
        { BadFormat($APos, $AStr); }

      $vRes .= substr($AStr, $APos, $vPos - $APos);
      $APos = $vPos + 1;

      if ($AStr[$APos] == '"') 
      {
        $vRes .= '"';
        $APos++;
      }
      else
        { break; }

    } //while

    return $vRes;
  }



  function BadFormat($Pos, $Str)
  {
    $Start = $Pos - 10;
    if ($Start < 0)
      {$Start = 0;};
    $vStr = substr($Str, $Start, 20);
    throw new Exception("Parse string: Bad format near pos={$Pos}: {$vStr})");
  }


//-----------------------------------------------------------------------------
// Документ из массива в строку

  function ArrDocToStrDoc($ADoc)
  {
    return ArrStructToStr($ADoc);
  }


  function ArrStructToStr($AStruct)
  {
    if (!is_array($AStruct)) 
      { throw new Exception("ArrStructToStr: AStruct is not array"); }

    $vArr = array();

    foreach ($AStruct as $Field => $Value)
    {
      if (is_array($Value))
      {
        $vArr[] = $Field."=".ArrSubtableToStr($Value);
      }
      elseif (is_string($Value))
      {
        $vArr[] = $Field."=".StrSafeMask($Value);
      }
      elseif (is_bool($Value))
      {
        $vArr[] = $Field."=".($Value ? "1" : "0");
      }
      else
      {
        $vArr[] = $Field."=".$Value;
      }
    };

    return '['.implode(',', $vArr).']';
  }


  function ArrSubtableToStr($ASubtable)
  {
    $vArr = array();

    foreach ($ASubtable as $Index => $Value)
    {
      $vArr[] = $Index."=".ArrStructToStr($Value);
    }

    return '['.implode(',', $vArr).']';
  }


  function StrSafeMask($AStr)
  {
    $vLen = strcspn($AStr, '",[]', $vPos);
    if ($vLen < strlen($AStr))
    {
      $AStr = '"'.strtr($AStr, array('"'=>'""')).'"';
    }
    return $AStr;
  }



//-----------------------------------------------------------------------------
// Метаописание документа из строки в массив

  function StrDocInfoToArray($AStr)
  {
    $vPos = 0;
    return StrDocInfoExtractStruct($AStr, $vPos);
  }


  function StrDocInfoExtractStruct($AStr, &$APos)
  {
    if ($AStr[$APos] != "[") 
      { BadFormat($APos, $AStr); }

    $vRes = Array();

    $vStrLen = strlen($AStr);
    $APos++;

    while (($APos < $vStrLen) && ($AStr[$APos] != ']'))
    {
      $vPos = strpos($AStr, ':', $APos);
      if ($vPos === FALSE)
        { BadFormat($APos, $AStr); }

      $vField = substr($AStr, $APos, $vPos - $APos);
      $vPos++;

      if ($AStr[$vPos] == "[")
      {
        $vDescr = StrDocInfoExtractSubtable($AStr, $vPos);
      }
      else
      {
        $vLen = strcspn($AStr, ',]', $vPos);
        $vDescr = substr($AStr, $vPos, $vLen);
        $vPos += $vLen;
      }

      $vRes[$vField] = $vDescr;

      if ($AStr[$vPos] == ',')
        {$vPos++;}

      $APos = $vPos;
    } //while

    if ($APos >= $vStrLen) 
      { BadFormat($APos, $AStr); }

    $APos++;
    return $vRes;
  }


  function StrDocInfoExtractSubtable($AStr, &$APos)
  {
    return StrDocInfoExtractStruct($AStr, &$APos);
  }


//-----------------------------------------------------------------------------
// Query из строки в массив

  function StrQueryToArray($AStr)
  {
    $vPos = 0;
    if ($AStr[$vPos] != "[") 
      { BadFormat($vPos, $AStr); }

    $vRes = Array();

    $vStrLen = strlen($AStr);
    $vPos++;

    while (($vPos < $vStrLen) && ($AStr[$vPos] != ']'))
    {
      $vRes[] = StrDocExtractStruct($AStr, $vPos);

      if ($AStr[$vPos] == ',')
        {$vPos++;}
    }

    return $vRes;
  }


//-----------------------------------------------------------------------------
// Список значений из строки в массив 

  function DocsToArray($AStr)
  {
    $vPos = 0;
    if ($AStr[$vPos] != "[") 
      { BadFormat($vPos, $AStr); }

    $vRes = Array();

    $vStrLen = strlen($AStr);
    $vPos++;

    while (($vPos < $vStrLen) && ($AStr[$vPos] != ']'))
    {
//    $vRes[] = StrDocExtractStruct($AStr, $vPos);

      if ($AStr[$vPos] == '"')
      {
        $vValue = StrDocExtractStr($AStr, $vPos);
      }
      else
      {
        $vLen = strcspn($AStr, ',]', $vPos);
        $vValue = substr($AStr, $vPos, $vLen);
        $vPos += $vLen;
      }

      $vRes[] = $vValue;

      if ($AStr[$vPos] == ',')
        {$vPos++;}
    }

    return $vRes;
  }


//-----------------------------------------------------------------------------

  function TrySplitDocRef($DocRef, &$DocKey, &$Descr = null)
  {
    if ($DocRef[0] != '{')
      { return false; }

    $vPos = strpos($DocRef, '}');
    if ($vPos === false)
      { return false; }

    if ($vPos > 1)
    {
      $DocKey = substr($DocRef, 0, $vPos + 1);
    }
    else
    {
      $DocKey = '';
    }

    $Descr = substr($DocRef, $vPos + 1);

    return true;
  }


  function SplitDocRef($DocRef, &$Descr = null)
  {
    $vLen = strcspn($DocRef, "}") + 1;
    $Descr = substr($DocRef, $vLen);
    return substr($DocRef, 0, $vLen);
  }


  function SplitDocID($DocKey, &$DocName = null)
  {
    $DocName = strtok($DocKey, "{:}");
    return strtok("{:}");
  };


  function MakeDocID($ADocName, $AID)
  {
    return "{".$ADocName.":".$AID."}";
  }


?>
