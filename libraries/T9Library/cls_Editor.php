<?
include_once 'cls_Document.php';
include_once 'cls_Form.php';

/*
Перекрываемые методы:

  function NewDoc()
  function Validate()
  function CustomSubmit()

  function BeforeInsertRow($ATabName, $AIndex)
  function AfterInsertRow($ATabName, $AIndex, &$AStruct)
  function BeforeDeleteRow($ATabName, $AIndex, &$AStruct)
  function AfterDeleteRow($ATabName, $AIndex)

  function MakeForm()
  function MakeTableField($AStructIdx, $AColIdx, $ANameEx, $AValue, $AColInfo)

  function ShowErrors($Errors)

Методы:
*/

abstract class TBEditor extends TBForm
{
  public $ID;
  public $DocName;
  public $DocID;


  public $Doc;            // Документ (объект класса TBDocument)
  public $DocInfo;        // Метаданные документа (ассоциативный массив: "ИмяПоля"=>ТипПоля)
  public $LoadedFields;   // Список загружаемых полей документа

  public $DocClass;       // Имя класса ТБ.Скрипт, обработывабщего события документа
  public $ProfileName;    // Логическое имя редактора, по умолчанию = имени PHP-класса 


  function TBEditor($AID)
  {
    global $TBSession;

    $this->ID = $AID;
    $this->DocID = SplitDocID($AID, $this->DocName);

    // Запрашиваем метаописание документа
    $this->DocInfo = $TBSession->GetDocumentInfo($this->DocName);

    $this->ProfileName = get_class($this).":";
  }


  function Edit()
  {
    global $TBSession;

    if (!$_POST) 
    {
      $this->RememberRefferer();

      $this->InitDoc(true);
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
      elseif (isset($_POST["Delete"]))
      {
        $this->Delete();
      }
      elseif (isset($_POST["AddTab"]))
      {
        $this->InitDoc();
        $this->GetPost();

        $TabName = strtok($_POST["AddTab"], "_");
        $TabIndex = strtok("_");
        if ($TabIndex == "")
          { $TabIndex = -1; }

//      echo "Add position. Table: $TabName, Index: $TabIndex<br>\n";
        $this->AddTableRow($TabName, $TabIndex);
        $this->EndModifySubtable(true);
      }
      elseif (isset($_POST["DelTab"]))
      {
        $this->InitDoc();
        $this->GetPost();

        $TabName = strtok($_POST["DelTab"], "_");
        $TabIndex = strtok("_");

//      echo "Del position. Table: $TabName, Index: $TabIndex<br>\n";
        $this->DelTableRow($TabName, $TabIndex);
        $this->EndModifySubtable(false);
      }
      elseif (!$this->CustomSubmit())
      {
        $this->InitDoc();
        $this->GetPost();

        if ($this->AcceptData())
        {
          $this->EndEdit();
          return;
        }  

        $this->MakeForm();
      }
    }
  }


  function RememberRefferer()
  {
    if ($this->ProfileName)
    {
      $Refferer = $_SERVER['HTTP_REFERER'];

      if ($Refferer)
      {
        $URL = parse_url($Refferer);
        $Refferer1 = $URL["path"] . ( $URL["query"] ? "?".$URL["query"] : "");
//      print $Refferer1."<br>\n";
//      print $_SERVER['REQUEST_URI']."<br>\n";
        if ($Refferer1 != $_SERVER['REQUEST_URI'])
        {
          $this->SetRefferer($Refferer);
        };
      };
    };
  }


  function RecallRefferer($AClear = false)
  {
    global $TBSession;
    $Refferer = "";
    if ($this->ProfileName)
    {
      $Refferer = $TBSession->GetRefferer($this->ProfileName.$this->ID);
      if ($AClear)
      {
        $this->SetRefferer(null);
      };
    };
    return $Refferer;
  }



  function SetRefferer($ARefferer)
  {
    global $TBSession;
    $TBSession->SetRefferer($this->ProfileName.$this->ID, $ARefferer);
  }



  function InitDoc($ReRead = false)
  {
    global $TBSession;

    // Ищем документ в пуле
    $this->Doc = $TBSession->GetEditedDocument($this->ProfileName.$this->ID);

    if (empty($this->Doc) || ($ReRead && !$this->Doc->Modified)) 
    {
      // Документ еще не редактируется - запрашиваем с сервера
      $this->Doc = new TBDocument($this->ID);
      $this->Doc->DocClass = $this->DocClass;
      $this->Doc->Reload($this->LoadedFields);

      // Обновляем документ в пуле
      $TBSession->SetEditedDocument($this->ProfileName.$this->ID, $this->Doc);

      if (!$this->DocID)
      {
        $this->Doc->Edit();
        $this->NewDoc();
      }
      else
      {
        $this->ReadDoc();
      }
    }
  }


  
  function NewDoc()
  {
    // Установка начальных значений
  }


  function ReadDoc()
  {
    // Запрос дополнительных значений
  }


  function GetPost()
  {
    global $TBSession;

    $this->Doc->Edit();
    $Doc = &$this->Doc->Data;

    foreach ($_POST as $Field => $Value)
    {
      $Value = stripslashes($Value);

//    echo "{$Field}: {$Value} -> {$_POST[$Field]}<br>\n";
      $Field1 = strtok($Field, "-"); //#
      $FldInfo = &$this->DocInfo[$Field1];

      if (isset($FldInfo)) 
      {
        if (is_array($FldInfo))
        {
          $TabField = strtok("-"); //#
          $TabIndex = strtok("-"); //#

          $TabFldInfo = &$FldInfo[$TabField];
          if (!isset($TabFldInfo))
            { throw new Exception("Field {$Field1}.{$TabField} not found"); };


          if (is_array($TabFldInfo))
          {
            // Подтаблицы второго уровня не поддерживаются...
            Sorry();
          }
          else
          {
            $Subtab = &$Doc[$Field1];
            $Struct = &$Subtab[$TabIndex];

            $this->AcceptValue($Struct, $TabField, $Field, $TabFldInfo, $Value);
          }
        }
        else 
        {
          $this->AcceptValue($Doc, $Field, $Field, $FldInfo, $Value);
        }
      }
    };
                            
    $this->Doc->SetModified();
  }



  function AcceptValue(&$AStruct, $AField, $AFullFieldName, $AFldInfo, $AValue)
  {
    $OldValue = $AStruct[$AField];

    if (($AValue != "") && ($AFldInfo[0] == "{"))
    {
      // Ссылочное поле, редактируется разыменованное значение

      // {DocName:DocId}DocDescr
      $NewRef = $_POST["id_".$AFullFieldName];  

      if ($NewRef)
      {
        if (!TrySplitDocRef($NewRef, $NewKey, $Descr))
          { return; }
      }

      if ($Descr != $AValue)
      {
        // Был ручной ввод - новый DocKey неизвестен.
        $AValue = "{}".$AValue;
      }
      else
      {
        $AValue = $NewRef;
      }
    }

    if ($OldValue != $AValue)
    {
//    echo "{$AField}: {$OldValue} -> {$AValue}<br>\n";   
      $AStruct[$AField] = $AValue;
    }
  }



  function AddTableRow($ATabName, $AIndex = -1)
  {
    global $TBSession;

    $TabInfo = $this->DocInfo[$ATabName];
    if (!$TabInfo || !is_array($TabInfo))
      { throw new Exception("Subtable {$ATabName} not found"); };

    $Doc = &$this->Doc->Data;

    $Subtab = &$Doc[$ATabName];
    if (!is_array($Subtab))
      { $Subtab = array();}

    if ($this->BeforeInsertRow($ATabName, $AIndex))
    {
      if ($AIndex == -1)
      {
        $AIndex = count($Subtab);
        $Subtab[$AIndex] = array();
      }
      else
      {
        array_splice($Subtab, $AIndex, 0, array(array()));
      }

      $this->AfterInsertRow($ATabName, $AIndex, $Subtab[$AIndex]);

      $this->Doc->SetModified();
    }
  }


  function BeforeInsertRow($ATabName, $AIndex)
  {
    return true;
  }


  function AfterInsertRow($ATabName, $AIndex, &$AStruct)
  {
  }
  

  function DelTableRow($ATabName, $AIndex)
  {
    global $TBSession;

    $TabInfo = $this->DocInfo[$ATabName];
    if (!$TabInfo || !is_array($TabInfo))
      { throw new Exception("Subtable {$ATabName} not found"); };

    $Doc = &$this->Doc->Data;

    $Subtab = &$Doc[$ATabName];
    if (!is_array($Subtab))
      { return; }

    if ($this->BeforeDeleteRow($ATabName, $AIndex, $Subtab[$AIndex]))
    {
      array_splice($Subtab, $AIndex, 1);

      $this->AfterDeleteRow($ATabName, $AIndex);

      $this->Doc->SetModified();
    }
  }


  function BeforeDeleteRow($ATabName, $AIndex, &$AStruct)
  {
    return true;
  }


  function AfterDeleteRow($ATabName, $AIndex)
  {
  }


  function EndModifySubtable($IsAdd)
  {
    global $PageTemplate;

    $this->MakeForm();

    if ($this->DefField)
    {
      $this->FormSetDefault($this->DefField);
    };

    $PageTemplate = "none";
    header('Location:'.$_SERVER['REQUEST_URI']);
  }


  function Save()
  {
    $this->Doc->Save($this->LoadedFields);
  }


  function EndEdit()
  {
    global $TBSession, $PageTemplate;

    $TBSession->FreeEditedDocument($this->ProfileName.$this->ID);

    $Refferer = $this->RecallRefferer(true);

    if ($Refferer)
    {
//    print "Сохранено<br>\n";
//    print "<a href='{$Refferer}'>Назад</a><br>\n";
//    redirect_timeout($Refferer, 1000); 

      $PageTemplate = "none";
      header('Location:'.$Refferer);
    }
    else
    {
      $this->InitDoc();
      $this->MakeForm();
    }
  }


  function Cancel()
  {
    global $TBSession, $PageTemplate;

//  echo "Cancel...<br>";
    $TBSession->FreeEditedDocument($this->ProfileName.$this->ID);

    $Refferer = $this->RecallRefferer(true);

    if ($Refferer)
    {
//    print "Отменено<br>\n";
//    print "<a href='{$Refferer}'>Назад</a><br>\n";
//    redirect_timeout($Refferer, 1000);

      $PageTemplate = "none";
      header('Location:'.$Refferer);
    }
    else
    {
      $this->InitDoc();
      $this->MakeForm();
    }
  }


  function Delete()
  {
    global $TBSession, $PageTemplate;

    try 
    {
      $TBSession->FreeEditedDocument($this->ProfileName.$this->ID);

      // Собственно удаление...
      $TBSession->DeleteDocument($this->ID);

      $Refferer = $this->RecallRefferer(true);

      if ($Refferer)
      {
//      print "Удалено<br>\n";
//      print "<a href='{$Refferer}'>Назад</a><br>\n";
//      redirect_timeout($Refferer, 1000);

        $PageTemplate = "none";
        header('Location:'.$Refferer);
      }

    }
    catch (Exception $e) 
    {
      $this->ShowErrors(array($e->getMessage()));

      $this->InitDoc();
      $this->MakeForm();
    }
  }



//-----------------------------------------------------------------------------
// Создание форм

/*
  function FormBegin($Class = "", $Name = "", $Method = "post", $EncType = "")
  {
    print "<script type='text/javascript'>\n";
    print "  var DocProfile='{$this->ProfileName}';\n";
    print "  var DocID='{$this->ID}';\n";
    print "</script>\n\n";

    TBForm::FormBegin($Class, $Name, $Method, $EncType);
  }
*/


  function FormAddFieldEx($AName, $AInfo = null, $AValue = null)
  {
    if (empty($AInfo))
      { $AInfo = array(); }

    $Name1 = strtok($AName, ".");

    if ($AValue === null)
      { $AValue = $this->Doc->Data[$Name1]; }

    $FldInfo = &$this->DocInfo[$Name1];
    if (isset($FldInfo) && ($FldInfo[0] == '{')) 
      { $AInfo["DocRef"] = 1; }

    TBForm::FormAddFieldEx($AName, $AInfo, $AValue);
  }


//-----------------------------------------------------------------------------
// Подтаблицы

  protected $CurTab;       // Имя текущей подтаблицы
  protected $TabInfo;      // Метаданные о полях текущей подтаблицы
  protected $TabColumns;   // Описания столбцов текущей подтаблицы
  protected $TabValue;     // Данные текущей подтаблицы (массив структр)
  protected $TabOptions;   // Опции текущей подтаблицы


  // Возможные значения опций:
  //   ShowCaption - 0/1 Показывать заголовок
  //   CanAdd      - 0/1 разрешено ручное добавление строк в подтаблицу (в конец)
  //   CanInsert   - 0/1 разрешена ручная вставка строк в подтаблицу (в середину)
  //   CanDelete   - 0/1 Разрешено ручное удаление строк из подтаблицы  

  function FormBeginTable($ATabName, $ACaption, &$ATabValue, $ATabOptions = null)
  {
    $TabInfo = &$this->DocInfo[$ATabName];
    if (!$TabInfo || !is_array($TabInfo))
      { throw new Exception("Subtable {$ATabName} not found"); };

    print "<dl class='Subtable'>\n";
    if ($ACaption)
    {
      print " <dt><label>{$ACaption}</label></dt>\n";
    };
    print "<table>\n";

    $this->CurTab = $ATabName;
    $this->TabInfo = &$TabInfo;
    $this->TabValue = &$ATabValue;
    $this->TabColumns = array();

    if (!is_array($ATabOptions))
      { $ATabOptions = array("CanAdd"=>1, "CanInsert"=>1, "CanDelete"=>1); }

    $this->TabOptions = $ATabOptions;
  }


  function FormAddTabColumn($AName, $ACaption = null, $AWidth = null)
  {
    $this->FormAddTabColumnEx($AName, array("Caption"=>$ACaption, "Width"=>$AWidth));
  }

  
  function FormAddTabColumnEx($AName, $AColInfo = null)
  {
    if (!$this->CurTab)
      { throw new Exception("Table not opened"); };

    $Name1 = strtok($AName, ".");
    $Deref = strtok(".");

    if ($AColInfo === null)
      { $AColInfo = array(); }

    $AColInfo["Name"]  = $Name1;
    $AColInfo["Deref"] = $Deref;

    $FldInfo = &$this->TabInfo[$Name1];
//  if (!$FldInfo)
//    { throw new Exception("Field {$this->CurTab}.{$Name1} not found"); };
    if (isset($FldInfo) && ($FldInfo[0] == '{')) 
      { $AColInfo["DocRef"] = 1; }

    $this->TabColumns[] = $AColInfo;
  }


  function FormEndTable()
  {
    $TabName = $this->CurTab;

    $Options =  $this->TabOptions;
    $CanControl = $Options["CanAdd"] || $Options["CanInsert"] || $Options["CanDelete"];

    //Colgroup
    $ColCount = 0;
    print "<colgroup>\n";
    foreach ($this->TabColumns as $ColIdx => $ColInfo)
    {
      $Width = $ColInfo["Width"];
//      print " <col".($Width > 0 ? " width={$Width}" : "").">\n";
      print " <col".(isset($Width) ? " width={$Width}" : "").">\n";
      $ColCount++;
    }
    if ($CanControl)
    {
      print " <col width=".($Options["CanInsert"] && $Options["CanDelete"] ? 60 : 30)."px>\n";
    }
    print "</colgroup>\n";


    if ($Options["ShowCaption"] || !isset($Options["ShowCaption"]))
    {
      //Строка заголовока
      print "<tr class='Header'>\n";
      foreach ($this->TabColumns as $ColIdx => $ColInfo)
      {
        $Style = "";
        $Align = $ColInfo["Align"];
        if ($Align)
          { $Style .= "text-align:$Align;"; };

        $Caption = $ColInfo["Caption"];
        if (!isset($Caption))
          { $Caption = $ColInfo["Name"]; }

        print " <td class='Header'".($Style ? " style='$Style'" : "").">";
        print $Caption;
        print " </td>\n";
      }
      if ($CanControl)
      {
        print " <td class='Control'>&nbsp</td>\n";
      }
      print "</tr>\n";
    };


    if (is_array($this->TabValue))
    {
      //Строки по позициям
      foreach ($this->TabValue as $StructIdx => $Struct)
      {
        print "<tr>\n";

        // Столбцы
        foreach ($this->TabColumns as $ColIdx => $ColInfo)
        {
          $Name = $ColInfo["Name"];
          $Value = $Struct[$Name];

          $NameEx = "{$TabName}-{$Name}-{$StructIdx}"; //#

          print " <td><dl>\n";
//        $this->AddField($NameEx, $Value, $ColInfo);
          $this->MakeTableField($StructIdx, $ColIdx, $NameEx, $Value, $ColInfo);
          print " </dl></td>\n";
        }

        if ($CanControl)
        {
          // Управляющий столбец
          print " <td class='Control'>\n";
          $this->MakeTableButtons($StructIdx, $TabName.'_'.$StructIdx, $Struct);
          print " </td>"."\n";
        }

        print "</tr>"."\n";
      }
    }

    if ($this->TabOptions["CanAdd"])
    {
      print "<tr class='New'>\n";
      print " <td colspan={$ColCount}></td>\n";
      print " <td class='Control'>\n";
      print "   <button type='submit' class='Add' name='AddTab' value='{$TabName}'>+</button>\n";
      print " </td>\n";
      print "</tr>\n";
    }

    print "</table>\n";
    print "</dl>\n";

    unset($this->CurTab);
    unset($this->TabInfo);
    unset($this->TabColumns);
    unset($this->TabValue);
  }


  function MakeTableButtons($AStructIdx, $AName, &$AStruct)
  {
    if ($this->TabOptions["CanInsert"])
    {
      print "  <button type='submit' class='Add' name='AddTab' value='{$AName}'>+</button>\n";
    }
    if ($this->TabOptions["CanDelete"])
    {
      print "  <button type='submit' class='Del' name='DelTab' value='{$AName}'>X</button>\n";
    }
  }


  function MakeTableField($AStructIdx, $AColIdx, $ANameEx, $AValue, $AColInfo)
  {
    // Перекрывайте этот метод, если поля в подтаблице должны иметь разные свойства в разных строках
    // Доступ к структуре: $this->TabValue[$AStructIdx]
    $this->AddField($ANameEx, $AValue, $AColInfo);
  }


}


?>