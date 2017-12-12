<?        
include_once 'func_doc.php';

class TBCardfile
{
  public $DocName;      // Имя класса документа 
  public $DocInfo;      // Информация и классе документа
  public $Options;      // Опции картотеки
  public $Fields;       // Список полей картотеки
  public $Sort;         // Упорядочивание
  public $Filter;       // Фильтр (устанавливается программно)
  public $Mask;         // Маска (пользовательский фильтр в упрощенной записи)
  public $Data;         // Массив документов (документы - в виде массивов, а не TBDocument'ов)
  public $Packet;       // Число документов на странице
  public $Hierarchical; // Иерархическая картотека
  public $DescrField;   // Описательное плоле (для GroupPath, если не задано, используется ForeignKey)
  public $Group;        // Текущая группа в иерархической картотеке
  public $GroupPath;    // Путь в иерархической картотеке.
  public $ListRef;      // Ссылка, при нажатии на иконку листика
  public $BackID;       // ID элемента window.opener для обратной вставки выбранного значения
  public $BackField;    // Поле, используемое в качестве описательного для обратной вставки. Если не задано = DescrField
  public $URL;
  public $DefField;     // Поле, на которое устанавливается фокус


  function TBCardfile($ADocName)
  {
    global $TBSession;
//  print "construct TBCardfile {$ADocName}<br>";

    $this->DocName = $ADocName;
    $this->DocInfo = $TBSession->GetDocumentInfo($ADocName);

    $this->Options = array("ShowIcons"=>1);
    $this->Packet = 30;

    $this->URL = ChangeUrl($_SERVER['REQUEST_URI'], null, array("doc"));
  }


  function Show()
  {
    $this->InitForm();
    $this->GetData();
    $this->MakeForm();
  }


  function GetData()
  {
    global $TBSession;

    $this->BackID = $_GET["back"];
//  print "Back: {$this->BackID}<br>\n";


    if (isset($_GET["selgroup"]))
    {
      // Можно выбирать группы. Для входа в группу - нажимаем на листик.
      $this->Options["SelGroup"] = $_GET["selgroup"];
    };


    // Допустимы как узкие так и широкие id. Широкие - на случай гетерогенных картотек...
    $DocID = $_GET["doc"];

    $Backward = false;
    if (substr($DocID,0,1) == "-")
    {
      $Backward = true;
      $DocID = substr($DocID,1);
    };

    if ($DocID && ($DocID[0] !== '{'))
      { $DocID = '{'."{$this->DocName}:{$DocID}".'}'; };

    $GroupID = $_GET["group"];


//    if (isset($_GET["hierarchical"]))
//    {
//      $this->Hierarchical = $_GET["hierarchical"] == "1";
//      print "Hr:{$this->Hierarchical}<br>\n";
//    }


    if (isset($_GET["mask"]))
    {
      $this->Mask = stripslashes($_GET["mask"]);
      $this->Options["ShowFilter"] = 1;
    }


    if ($this->Hierarchical && !$this->Mask)
    {
      if ($GroupID === null)
      {
        if ($DocID)
        {
          // Инициализируем группу на основании текущего документа...
          $vDoc = $TBSession->GetDocument($DocID, "GroupDoc");
          $GroupID = SplitDocID($vDoc["GroupDoc"]);

          $this->URL = ChangeURL($this->URL, array("group"=>$GroupID));
        };

        if (!$GroupID)
        { 
          $GroupID = $this->GetInitGroup(); 
          if (!$GroupID)
            { $GroupID = 0; }
        }
      }
      $this->Group = $GroupID;
    }


    if (isset($_GET["sort"]))
    {
      $this->Sort = $_GET["sort"];
//    print "Sort: {$this->Sort}<br>\n";
    }

    // Формируем список загружаемых полей
    $Fields = $this->GetFields();

    // Получаем объединенный фильтр: программный + пользовательский (mask)
    $Filter = $this->GetFilter();


    // Запрашиваем данные 
    $this->Data = $TBSession->Query(array("Document"=>$this->DocName, "Fields"=>$Fields, "Order"=>$this->Sort, "Filter"=>$Filter, 
      "Group"=>$this->Group, "Current"=>$DocID, "PacketSize"=>$this->Packet, "Backward"=>$Backward, "CheckBorder"=>true, "Greedy"=>true));


    if ($this->Hierarchical && $this->Group)
    {
      // Запрашиваем иерархический путь
      $vStr = $TBSession->ProcCall("Web.Docs", "GetGroupPath", '{'."{$this->DocName}:{$this->Group}".'}|'.$this->DescrField);
//    print "{$vStr}<br>\n";
      $this->GroupPath = DocsToArray($vStr);
    }
  }


  function GetFields()
  {
    foreach ($this->Fields as $Field => $Info)
    {
      $vRes .= $Info["Name"].",";
    }
    return $vRes;
  }


  function GetFilter()
  {
    $Filter = $this->Filter;

    if ($this->Mask)
    {
      $MaskFilter = $this->ConvertMaskToFilter($this->Mask);

      if ($Filter)
      {
        $Filter = "($Filter) and ($MaskFilter)";
      }
      else
      {
        $Filter = $MaskFilter;
      };

    };

    return $Filter;
  }


  function ConvertMaskToFilter($AMask)
  {
    if (preg_match('/^\s*filter:.*$/i', $AMask))
    {
      $Res = preg_replace('/^\s*filter:\s*(.*)$/i', "$1", $AMask);
//    echo "Filter: \"$Res\"\n";
    }
    else
    {
      $Field = $this->DescrField;
      if (!$Field)
        { $Field = "Имя"; }; //!!!


      $vStrs = StrExplodeEx($AMask);
//    print_r($vStrs);


      if ($Field)
      {
        foreach ($vStrs as $vIdx => $vStr)
        {
          if (strpos($vStr, "*") === false)
          {
            $vStr = "*".$vStr."*";
          };

          $Res .= ($Res ? " and " : "")."Match($Field, '$vStr')";
        }
      };
    }

    return $Res;
  }


  function GetInitGroup()
  {
    // Если текущая группа не задана явно - здесь можно установить начальное значение.
    // например, для запоминания последней группы в картотеке...
    return 0;
  }


//-----------------------------------------------------------------------------
// Создание форм


  function InitForm()
  {
    // Abstract
  }


  function AddColumn($AName, $ACaption = NULL, $AWidth = 0)
  {
    $this->AddColumnEx($AName, array("Caption"=>$ACaption, "Width"=>$AWidth));
  }

  
  function AddColumnEx($AName, $AInfo = NULL)
  {
    $Name1 = strtok($AName, ".");

    $FldInfo = &$this->DocInfo[$Name1];
//  if (!$FldInfo)
//    { throw new Exception("Field {$Name1} not found"); };   Вычислимое поле...

    if (empty($AInfo))
      { $AInfo = array(); }

    $AInfo["Name"] = $AName;
    if (!$AInfo["Info"]) 
    {
      $AInfo["Info"] = $FldInfo;
    }

    $this->Fields[] = $AInfo;
  }


//-----------------------------------------------------------------------------

  protected function MakeForm()
  {
    print "<div class='Card'>\n\n";

    $this->MakeTitle();

    if ($this->Options["ShowFilter"])
      { $this->MakeFilter(); }

    if ($this->Hierarchical)
      { $this->MakeGroupPath(); }

    $this->MakeNavigator(0);
    $this->MakeTable();
    $this->MakeNavigator(1);

    if ($this->DefField)
      { $this->FormSetDefault($this->DefField); }

    print "</div> <!-- Card -->\n";
  }

             
  protected function MakeTitle()
  {
  }


  protected function MakeFilter()
  {
//  $URL = ChangeURL($this->URL, NULL, array("mask", "group") );
//  <form class="FilterForm" method="get" action="<?=$this->URL">

?>
<div class="Filter">
<form class="FilterForm" method="get">
<fieldset class>
<?

    $URL = parse_url($this->URL);
    $Args = ArgsExplode($URL["query"]);

    unset($Args["mask"]);
    unset($Args["group"]);

    if ($Args)
    {
      print "<div class='Hidden'>\n";
      foreach ($Args as $Id => $Val)
      {
        $Val = MaskQoute(urldecode($Val));
        print " <input type='hidden' name='{$Id}' id='{$Id}' value=\"$Val\"/>\n";
      }
      print "</div>\n";
    };


    $Mask = MaskQoute($this->Mask);
    print " <input type='text' name='mask' id='mask' value=\"$Mask\"/>\n";
?>
</fieldset>
</form>
</div> <!-- Filter -->

<?
  }


  protected function MakeGroupPath()
  {
    print "<div class='GroupPath'>\n";
    print "<ul>\n";

    $href = ChangeURL($this->URL, array("group"=>0)); 
    print " <li><a href='{$href}'>Root</a></li>\n";

    if (is_array($this->GroupPath))
    {
      print " <div class='Div'></div>\n";

      for ($i = count($this->GroupPath) - 1; $i >= 0; $i--)
      {
        $Doc = $this->GroupPath[$i];

        $vID = strtok($Doc, "{}");
        $vDescr = strtok("");  
        if (!$vDescr)
          { $vDescr = $vID; }

//      if ($i > 0)
//      {
          $vDocName = strtok($vID, ":");
          $vDocId = strtok("");  

          $href = ChangeURL($this->URL, array("group"=>$vDocId)); 
          print " <li><a href='{$href}'>{$vDescr}</a></li>\n";
//      }
//      else
//      {
//        print "{$vDescr}\n";
//      }

        if ($i > 0)
        {
          print " <div class='Div'></div>\n";
        }
      }
    }

    print "</ul>\n";
    print "</div> <!--GroupPath-->\n\n";
  }


  protected function MakeNavigator($APlace)
  {
    $Doc = &$this->Data[0];
    $First = $Doc["DocID"];

    $Doc = &$this->Data[count($this->Data) - 1];
    $Last = $Doc["DocID"];

//  if (!$First && !$Last)
//  {
//    return;
//  };

    print "<div class='Navigator'>\n";

    if ($First)
    {
      print " <a href='".ChangeURL($this->URL, array("doc"=>0))."'>Начало</a>";
      print "&nbsp;&nbsp;\n";
      print " <a href='".ChangeURL($this->URL, array("doc"=>-$First))."'>Пред</a>";
    }
    else
    {
      print " Начало &nbsp;&nbsp; Пред\n";
    }

    print "&nbsp;&nbsp;\n";

    if ($Last)
    {
      print " <a href='".ChangeURL($this->URL, array("doc"=>$Last))."'>След</a>";
      print "&nbsp;&nbsp;\n";
      print " <a href='".ChangeURL($this->URL, array("doc"=>"-0"))."'>Конец</a>\n";
    }
    else
    {
      print " След &nbsp;&nbsp; Конец\n";
    }

    print "</div> <!--Navigator-->\n\n";
  }


  protected function MakeTable()
  {
    print "<table class='Cardfile' width='100%'>\n";

    //Colgroup
    $ColCount = 0;
    print "<colgroup>\n";

    if ($this->Options["ShowIcons"])
    {
      print "<col width='20' />\n";
    }

    foreach ($this->Fields as $Field => $Info)
    {
      $Width = $Info["Width"];
      print " <col".($Width > 0 ? " width='{$Width}'" : "")." />\n";
      $ColCount++;
    }
    print "</colgroup>\n";


    //Строка заголовока
    print "<tr class='Header'>\n";

    if ($this->Options["ShowIcons"])
    {
      // Выход из группы...
//    $href = ChangeURL($this->URL, array("group"=>$Doc["DocID"]));
//    print "<td><a href='$href'>^</a></td>\n";
      print " <td></td>\n";
    }

    foreach ($this->Fields as $Field => $Info)
    {
      $Style = "";
      $Align = $Info["Align"];
      if ($Align)
        { $Style .= "text-align:$Align;"; };

      $Caption = $Info["Caption"];
      if ($Caption === NULL)
        { $Caption = strtok($Info["Name"], "."); };

      $HRef = NULL;
      if ($Info["Sort"])
        { $HRef = $this->MakeSortRef($Info); };

      print " <td".($Style ? " style='$Style'" : "").">";

      if ($HRef)
        { print "<a $HRef>"; }

      if ($this->Sort == $Info["Sort"])
        { print "<div class='Sorted'>"; };

      print $Caption;

      if ($this->Sort == $Info["Sort"])
        { print "</div>"; };

      if ($HRef)
        { print "</a>"; }

      print "</td>\n";
    }
    print "</tr>\n";


    //Строки по документам
    $Odd = false;
    foreach ($this->Data as $Index => $Doc)
    {
      if (!$Doc)
      {
        // Пропускаем маркеры EOF/BOF
        continue;
      };

      print "<tr class='".(!$Odd ? "Data1" : "Data2")."'>\n";
      $Odd = !$Odd;


      // Столбец с иконкой состояния...
      if ($this->Options["ShowIcons"])
      {
        $Dummy = null;
        $HRef = $this->MakeRef($Dummy, $Doc);
        $Cls = ($Doc["IsGroup"]) ? "Group" : "Doc";
        print " <td class='Icon'><a $HRef><div class='$Cls'>&nbsp;</div></a></td>\n";
      }

      // Столбцы (по полям)
      foreach ($this->Fields as $Field => $Info)
      {
        $Add = $this->InfoToStyle($Info);
        print " <td$Add>";

        try
        {
          $this->MakeTableField($Doc, $Field, $Info);
        }
        catch (Exception $e)
        {
          HandleError($e);
        };

        print "</td>\n";
      }

      print "</tr>\n";
    }

    print "</table> <!-- Cardfile -->\n\n";
  }



  function MakeTableField(&$ADoc, $AColIdx, &$AColInfo)
  {
    $FldInfo = $AColInfo["Info"];

    $Name = strtok($AColInfo["Name"], ".");
    $Deref = strtok("");  // До конца

    $Value = $ADoc[$Name];

    if ($FldInfo[0] == '{') 
    {
      $vID = strtok($Value, "{}");
      $Value = strtok("");  // До конца
      if (!$Value && !$Deref)
        { $Value = $vID; }
    }
    else
    {
      $Fmt = $AColInfo["Format"];
      if ($Fmt)
      {
        if ($FldInfo == VarDate)
        {
          // Форматирование даты
          $Value = date($Fmt, strtotime($Value));
        }
        elseif (($FldInfo == VarInteger)||($FldInfo == VarNumeric))
        {
          // Форматирование числа
//        $Value = money_format($Fmt, $Value);
          $Value = sprintf($Fmt, $Value);
        }
      }
    }

    $HRef = NULL;
    if ($AColInfo["Ref"])
    {
      $HRef = $this->MakeRef($AColInfo, $ADoc);
    };

    if ($HRef)
    {
      print "<a $HRef>$Value</a>";
    }
    else
    {
      print $Value;
    };
  }



  function InfoToStyle($AInfo)
  {
    $Add = "";

//  if ($AInfo["Size"])
//    {$Add .= " size='{$AInfo['Size']}'"; }

    $Style = $AInfo["Style"];
    if ($AInfo["Align"])
      { $Style .= "text-align:{$AInfo['Align']};"; };
    if ($AInfo["Color"])
      { $Style .= "color:{$AInfo['Color']};"; };
    if ($AInfo["BkColor"])
      { $Style .= "background-color:{$AInfo['BkColor']};"; };

    if ($Style)
      {$Add .= " style='{$Style}'"; }

    return $Add;
  }


  protected function MakeSortRef(&$AColumn)
  {
    $HRef = ChangeURL($this->URL, array("sort"=>$AColumn["Sort"]));
    return "href='$HRef'";
  }


/*
  protected function MakeRef(&$AColumn, &$ADoc)
  {
    if ($ADoc["IsGroup"])
    {
      $HRef = ChangeURL($this->URL, array("group"=>$ADoc["DocID"]));
    }
    else
    {
      if ($this->BackID)
      {
        // Поддержка "обратной вставки"
        $DocID = '{'.$this->DocName.':'.$ADoc["DocID"].'}';

        $BackField = $this->BackField;
        if (!$BackField)
          { $BackField = $this->DescrField; }

        $Descr = "";
        if ($BackField)
          $Descr = $ADoc[$BackField];
        if (!$Descr)
          $Descr = $DocID;

        return "href='' onclick='BackRef(\"{$this->BackID}\", \"{$DocID}\", \"$Descr\")'";
      }
      elseif ($this->ListRef)
      {
        $HRef = $this->ListRef.$ADoc["DocID"];
      }
    }

    if ($HRef)
    {
      return "href='$HRef'";
    };
  }
*/


  protected function MakeRef(&$AColumn, &$ADoc)
  {
    if (($AColumn == null) && $this->Hierarchical && $ADoc["IsGroup"])
    {
      // Клик на листик в иерархической картотеке - всегда вход в группу
      $HRef = ChangeURL($this->URL, array("group"=>$ADoc["DocID"]), array("mask"));
    }
    else
    {
      $SelGroup = $this->Options["SelGroup"];  
      // 0 - Можно выбирать только элементы
      // 1 - Можно выбирать группы и элементы
      // 2 - Можно выбирать только группы

      if ($this->Hierarchical && $ADoc["IsGroup"] && !$SelGroup)
      {
        // Вход в группу
        $HRef = ChangeURL($this->URL, array("group"=>$ADoc["DocID"]), array("mask"));
      }
      else
      {
        $CanSelect = ($this->BackID) &&
          (($ADoc["IsGroup"] && $SelGroup) ||
          (!$ADoc["IsGroup"] && ($SelGroup != 2)));

        if ($CanSelect)
        {
          // Поддержка "обратной вставки"
          $DocID = '{'.$this->DocName.':'.$ADoc["DocID"].'}';

          $BackField = $this->BackField;
          if (!$BackField)
            { $BackField = $this->DescrField; }

          $Descr = "";
          if ($BackField)
            $Descr = $ADoc[$BackField];
          if (!$Descr)
            $Descr = $DocID;

          //!!! Маскировать Descr
          return "href='' onclick='BackRef(\"{$this->BackID}\", \"{$DocID}\", \"$Descr\")'";
        }
        elseif ($this->ListRef)
        {
          $HRef = $this->ListRef.$ADoc["DocID"];
        }

      }

    }

    if ($HRef)
    {
      return "href='$HRef'";
    };
  }


  function FormSetDefault($AID)
  {
    print "<script type='text/javascript'>\n";
    print "  addEvent(window, 'load', function() { E = document.getElementById('{$AID}'); E.focus(); E.select() } );\n";
    print "</script>\n";
  }

}

?>