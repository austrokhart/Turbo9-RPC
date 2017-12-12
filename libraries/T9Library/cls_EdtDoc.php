<?
include_once 'cls_Editor.php';


class EdtDoc extends TBEditor
{

  function MakeForm()
  {
    global $TBSession;

    $Doc = &$this->Doc->Data;

    $this->FormBegin("editor");

    foreach ($this->DocInfo as $Field => $Type)
    {
      $Value = $Doc[$Field];

      if (is_array($Type))
      {
        $this->FormAddSubtable($Field, $Type, $Value);
      }
      elseif ($Value[0] != '{')
      {
        $this->FormAddField($Field, $Field, $Value, 100);
      }
      else
      {
        $this->FormAddRefField($Field, $Field, $Value, 100, "doc?id=".strtok($Value, "{}"));
      } 
    };
    
//  $this->FormAddButtons("Сохранить");
    $this->FormAddButtons(array("Save"=>"Сохранить", "Cancel"=>"Отменить", "Delete"=>"Удалить"));
    $this->FormEnd();
  }


  function FormAddRefField($Name, $Caption, $Value, $Size = 50, $Ref)
  {
?>
  <dl>
    <dt><label for="<?=$Name;?>"><?=$Caption;?></label></dt>
    <dd><input type="text" size="<?=$Size;?>" name="<?=$Name;?>" id="<?=$Name;?>" value="<?=MaskQoute($Value);?>"/></dd>
    <a href="<?=$Ref;?>">Перейти</a>
  </dl>
<?
  }


  function FormAddSubtable($TabName, $TabInfo, $Subtable)
  {
    $this->FormBeginTable($TabName, $TabName, $Subtable);
    foreach ($TabInfo as $Field => $Type)
    {
      $this->FormAddTabColumn($Field, $Field);
    }
    $this->FormEndTable();
  }


}

?>