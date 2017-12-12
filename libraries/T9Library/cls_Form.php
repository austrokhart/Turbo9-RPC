<?

abstract class TBForm
{
  public $DefField;  // Поле, на которое устанавливается фокус
  public $GetField;  // Поле, по которому определяется GET запрос
  public $CancelRef; //


  function TBForm()
  {
  }


  function Edit()
  {
    global $TBSession;

    if (!$_POST && !($this->GetField && $_GET[$this->GetField])) 
    {
      $this->MakeForm();

      if ($this->DefField)
      {
        $this->FormSetDefault($this->DefField);
      };
    }
    else
    {
      if (isset($_POST["Cancel"]))
      {
        $this->Cancel();
      }
      elseif (!$this->CustomSubmit())
      {
        if ($this->AcceptData())
        {
          $this->EndEdit();
        }  
        else
        {
          $this->ContinueEdit();
        }
      }
    }
  }


  function CustomSubmit()
  {
    // Перекрывается в наследниках для реакции на нестандартные submit запросы
    return false;
  }


  function AcceptData()
  {
    $Res = false;
    try 
    {
      if ($this->Validate())
      {
        $this->Save();
        $Res = true;
      }
    }
    catch (Exception $e) 
    {
      $this->ShowErrors(array($e->getMessage()));
    }
    return $Res;
  }



  function Validate()
  {
    return true;
  }


  function Save()
  {
    // Abstract
  }


  function EndEdit()
  {
    // Abstract
  }


  function ContinueEdit()
  {
    $this->MakeForm();
  }


  function Cancel()
  {
    // Abstract
  }


  function ShowErrors($Errors)
  {
    PrintErrors($Errors);
  }


//-----------------------------------------------------------------------------
// Методы создания форм

  function MakeForm()
  {
    // Abstract
  }


  function FormBegin($Class = "", $Name = "", $Method = "post", $EncType = "")
  {
    $Class = ($Class ? "class='$Class'" : "");
    $Name = ($Name ? "id='{$Name}' name='{$Name}'" : "");
    $EncType = ($EncType ? "enctype='{$EncType}'" : "");

    print "<div class='Form'>\n";
    print "<form $Class $Name $EncType method='$Method'>\n";
    print "<fieldset>\n";
  }


  function FormEnd()
  {
    print "</fieldset>\n";
    print "</form>\n";
    print "</div> <!-- Form -->\n";
  }


  // Добавление простого поля
  function FormAddField($AName, $ACaption = null, $AValue = null)
  {
    $this->FormAddFieldEx($AName, array("Caption"=>$ACaption), $AValue);
  }


  // Добавление простого поля с расширенными атрибутами.
  // Атрибуты задаются в ассоциативном массиве AInfo.
  // Возможные значения атрибутов:
  //   Caption  - Заголовок поля
  //   Size     - Длина поля (атрибут size)
  //   Align    - выравнивание (style='text-align:xxx') значения: left | right | center | justify
  //   Color    - цвет текста (style='color:xxx')
  //   BkColor  - цвет фона (style='background-color:xxx')
  //   Class    - Класс поля, добавляется в контейнер <dl>
  //   Style    - произвольная строка стилей в атрибуте style
  //   ReadOnly - 0/1 - поле только для вывода
  //   Disabled - 0/1 - поле "запрещено"
  //   Static   - 0/1 - поле заменятся на статический параграф
  //   Raw      - 0/1 - не использовать маскирование строки (совместно со Static)
  //   Wrap     - 0/1 - поле для многострочного ввода (заменяется на textarea)
  //   Rows     - Высота многострочного поля
  //   CheckBox - 0/1 - поле-checkbox
  //   Select   - 0/1 - поле со списком выбора (заменяется на select)
  //   Options  - Варианты выбора (ассоциативный массив)
  //   DocRef   - 0/1 - ссылка на документ
  //   Ref      - Ссылка для кнопочки обзора
  //   File     - Поле выбора файла
  //   MaxFileSize - Максимальный размер файла 
  //   Password - Пароль


  function FormAddFieldEx($AName, $AInfo = null, $AValue = null)
  {
    if (empty($AInfo))
      { $AInfo = array(); }

    $Name1 = strtok($AName, ".");
    $Deref = strtok(".");

    $Cls = $AInfo["Class"];
    if ($Cls)
      { $Cls = " class='$Cls'"; };

    print "<dl$Cls>\n";

    $Caption = $AInfo["Caption"];
    if ($Caption === null)
      { $Caption = $Name1; }

    if ($Caption)
    {
      print " <dt><label for='{$Name1}'>{$Caption}</label></dt>\n";
    }

/*
    if ($AValue === null)
      { $AValue = $this->Doc->Data[$Name1]; }

    $AInfo["Info"] = &$this->DocInfo[$Name1];
*/

    $this->AddField($Name1, $AValue, $AInfo);

    print "</dl>\n";
  }


  function InfoToStyle($AInfo)
  {
    $Add = "";

    if ($AInfo["Size"])
      {$Add .= "size='{$AInfo['Size']}' "; }

    $Style = $AInfo["Style"];
    if ($AInfo["Align"])
      { $Style .= "text-align:{$AInfo['Align']};"; };
    if ($AInfo["Color"])
      { $Style .= "color:{$AInfo['Color']};"; };
    if ($AInfo["BkColor"])
      { $Style .= "background-color:{$AInfo['BkColor']};"; };

    if ($Style)
      { $Add .= "style='{$Style}' "; }

    if ($AInfo["ReadOnly"])
      { $Add .= "readonly "; };
    if ($AInfo["Disabled"])
      { $Add .= "disabled "; };

    return $Add;
  }


  protected function AddField($AName, $AValue, $AInfo)
  {
    $Add = $this->InfoToStyle($AInfo);

    if ($AInfo["DocRef"]) 
    {
      // Ссылочное поле...
      $this->AddRefField($AName, $AValue, $AInfo, $Add);
    }
    elseif ($AInfo["Static"])
    {
      // Статический текст
      $Value = $AValue;
      if (!$AInfo["Raw"])
      {
        $Value = MaskQoute($AValue);
        if ($AInfo["Wrap"])
          { $Value = nl2br($Value); }
      };
      print " <dd><div class='Static' {$Add}>{$Value}</div></dd>\n";
    }
    elseif ($AInfo["Wrap"])
    {
      // Текстовое поле со сверткой 
      if ($AInfo["Rows"])
      { 
        $Add .= "rows='{$AInfo['Rows']}' "; 
      }
      else
      {
        $Add .= "onkeyup='TextareaKeyUp(this)'"; 
      }

      $Value = MaskQoute($AValue);
      print " <dd><textarea name='{$AName}' id='{$AName}' {$Add}/>{$Value}</textarea></dd>\n";
    }
    elseif ($AInfo["CheckBox"])
    {
      // CheckBox
      if ($AInfo["Value"])
        { $Add .= "value='{$AInfo['Value']}' "; };
      if ($AValue)
        { $Add .= "checked "; };

      print " <dd class='CheckBox'><input type='checkbox' name='{$AName}' id='{$AName}' {$Add}/>";

      if ($AInfo["Prompt"])
      {
        print "<label for='{$AName}'>{$AInfo['Prompt']}</label>";
      }

      print " </dd>\n";

    }
    elseif ($AInfo["Select"])
    {
      // Список выбора
      $Options = &$AInfo["Options"];
      print " <dd><select name='{$AName}' id='{$AName}' {$Add}/>\n";

      foreach ($Options as $iOpt => $iDef)
      {
        $iAdd = "";
        if ($iOpt[0] == '.')
          { $iAdd .= "disabled "; }
        if ($iOpt == $AValue)
          { $iAdd .= "selected "; };
        print "  <option value='$iOpt' {$iAdd}>{$iDef}</option>\n";
      }

      print " </select></dd>\n";
    }
    elseif ($AInfo["File"])
    {
      // Поле выбора файла
      print " <dd>\n";

      $MaxFileSize = $AInfo["MaxFileSize"];
      if ($MaxFileSize)
      {
        print "  <input type='hidden' name='MAX_FILE_SIZE' value='$MaxFileSize'/>\n";
      };

      print "  <input type='file' name='{$AName}' id='{$AName}' {$Add} value=\"{$Value}\"/>\n";
      print " </dd>\n";
    }
    elseif ($AInfo["Password"])
    {
      $Value = MaskQoute($AValue);
      print " <dd><input type='password' name='{$AName}' id='{$AName}' {$Add} value=\"{$Value}\"/></dd>\n";
    }
    else
    {
      // Однострочное поле ввода
      $Value = MaskQoute($AValue);
      print " <dd><input type='text' name='{$AName}' id='{$AName}' {$Add} value=\"{$Value}\"/></dd>\n";
    }
  }


  protected function AddRefField($AName, $AValue, $AInfo, $Add = "")
  {
    if (TrySplitDocRef($AValue, $DocID, $Descr))
    {
      if ($Descr == "")
        { $Descr = $DocID; }
      if ($DocID == "")
        { $AValue = ""; };
    }
    else
    {
      $Descr = $AValue;
      $AValue = "";
    }

    $Descr = MaskQoute($Descr);
    print " <dd class='Ref'>\n";
    print "  <div class='Ref'>\n";
    print "   <div class='Edt'><input type='text' name='{$AName}' id='{$AName}' {$Add} value=\"{$Descr}\"/></div>\n";

    if ($AInfo["Ref"])
    {
      // Кнопочка выбора
      print "   <div class='But'><button type='button' tabindex='-1' onClick=\"LookupClick('{$AInfo["Ref"]}', '{$AName}')\">...</button></div>\n";
    }

    $AValue = MaskQoute($AValue);
    print "   <div class='ID'><input type='hidden' name='id_{$AName}' id='id_{$AName}' value=\"{$AValue}\"/></div>\n";
    print "  </div>\n";
    print " </dd>\n";
  }


  function FormAddButtons($Buttons)
  {
    print "<dl class='Buttons'>\n";

    foreach ($Buttons as $Button => $Caption)
    {
      if ($Button == "Cancel" && $this->CancelRef)
      {
        print " <button type='button' name='{$Button}' onClick=\"CancelEdit('{$this->CancelRef}')\">{$Caption}</button>\n";
      }
      elseif ($Button == "Reset")
      {
        print " <button type='reset' name='{$Button}'>{$Caption}</button>\n";
      }
      else
      {
        print " <button type='submit' name='{$Button}'>{$Caption}</button>\n";
      };
    }

    print "</dl>\n";
  }


  //---------------------------------------------------------------------------

  function FormBeginTab($AClass = null, $AName = null, $AWidths = null)
  {
    $Cls = (!$AClass ? "" : "class='$AClass'");
    $Name = (!$AName ? "" : "name='$AName' id='$AName'");

    print "<table $Cls $Name>\n";

    if (is_array($AWidths))
    {
      print "<colgroup>\n";
      for ($i = 0; $i < count($AWidths); $i++)
      {
        $Str = (!$AWidths[$i] ? "" : "width='{$AWidths[$i]}'");
        print " <col $Str>\n";
      };
      print "</colgroup>\n";
    };

    print "<tr><td class='c1'>\n\n";
  }


  function FormNextCol($AClass = null)
  {
    $ACls = (!$AClass ? "" : "class='$AClass'");
    print "\n</td><td $ACls>\n\n";
  }


  function FormEndTab()
  {
    print "\n</td></tr>\n";
    print "</table>\n";
  }


  //---------------------------------------------------------------------------

  function FormBeginSection($AName, $ACaption=null, $AOptions = null)
  {
    if (!is_array($AOptions))
      { $AOptions = array(); }

    $Add = "";
    if ($AOptions["Collapse"])
      { $Add = "Collapse='1'"; };

    $InAdd = "";
    $InStyle = $AOptions["Style1"];
    if ($InStyle)
      { $InAdd .= "style='{$InStyle}' "; }

    print "\n";
    print "<div class='Section' id='$AName' $Add>\n";


    if (isset($AOptions["Collapse"]))
    {
      $ACaption1 = $AOptions["Caption1"];
      if (!$ACaption1)
        { $ACaption1 = $ACaption; };

      print "<div class='Caption_Closed' id='CCap_$AName' style='display:none'>\n";
      print " <button type='button' class='Toggle' onClick=\"ToggleSec('{$AName}')\">+</button>\n";
      if ($ACaption1)
        { print " <div class='SecCaption'>{$ACaption1}</div>\n"; };
      print "</div>\n";

      print "<div class='Caption_Opened' id='OCap_$AName'>\n";
      print " <button type='button' class='Toggle' onClick=\"ToggleSec('{$AName}')\">-</button>\n";
      if ($ACaption)
        { print " <div class='SecCaption'>{$ACaption}</div>\n"; };
      print "</div>\n";
    }
    else
    {
      if ($ACaption)
        { print "<div class='SecCaption'>{$ACaption}</div>\n"; };
    }

    print "<div class='Section_In' id='In_$AName' $InAdd>\n";
  }

  
  function FormEndSection()
  {
    print "</div>\n";
    print "</div> <!-- Section -->\n";
  }



  function FormSetDefault($AID)
  {
    print "<script type='text/javascript'>\n";
    print "  addEvent(window, 'load', function() { E = document.getElementById('{$AID}'); E.focus(); E.select() } );\n";
    print "</script>\n";
  }


}


?>