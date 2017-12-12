<?
include_once 'func_doc.php';

//$TypeNames = array("Unknown", "String", "Integer", "Numeric", "Logical", "Date");

class TBDocument 
{
  public $ID;             // ID документа в формате "{КлассЗаписи:DocID}"
  public $Data;           // Массив значений полей
  public $Orig;           // Исходные значения полей
  public $Modified;       // Признак модифицированности 
  public $PoolTime;       // Время занесения в pool
  public $DocClass;       // Класс-обработчик событий документа


  function TBDocument($AID)
  {
    $this->ID = $AID;
  }


  function Reload($ALoadedFields)
  {
    global $TBSession;
//  echo "Document.Reload: {$this->ID}<br>\n";
    $this->Data = $TBSession->GetDocument($this->ID, $ALoadedFields);
//  print_r($this->Data);
    $this->Modified = 0;
  }


  function Save($ALoadedFields)
  {
    global $TBSession;

//  echo "Document.Save: {$this->ID}<br>\n";
    $Values = $this->GetDiff($ALoadedFields);
//  print_r($Values);

    // При создании нового документа возвращается присвоенный DocID
//  $this->ID = $TBSession->SaveDocument($this->ID, $Values, $ALoadedFields);
    $this->ID = $TBSession->SaveDocumentEx($this->ID, $Values, array("Fields"=>$ALoadedFields, "Class"=>$this->DocClass));
  }


  function Edit()
  {
    if (!is_array($this->Orig))
    {
      $this->Orig = $this->Data;
    }
  }


  function GetDiff()
  {
//  echo "Document.GetDiff: {$this->ID}<br>\n";
//  echo "Data: "; print_r($this->Data); echo "<br>\n";
//  echo "Orig: "; print_r($this->Orig); echo "<br>\n";

    $Diff = array();
    if (is_array($this->Orig))
    {
      foreach ($this->Data as $Field => $Value)
      {
//      echo "{$Field}: {$Value}, Old: {$this->Orig[$Field]}<br>\n";
        if ($Value != $this->Orig[$Field])
        {
          $Diff[$Field] = $Value;
        }
      }
    }
    return $Diff;
  }


  function SetModified($AField = null)
  {
    $this->Modified = 1;
  }

}

?>