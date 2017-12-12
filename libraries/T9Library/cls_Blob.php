<?
include_once 'func_rpc.php';

class TBBlob 
{
  public $Data;             // Строка данных


  function TBBlob($Data)
  {
    $this->Data = $Data;
  }


  function GetClassName()
  {
    return 'BinaryObject';
  }


  function SerializeToStr()
  {
    return rpc_int_ex_serialize(strlen($this->Data)).$this->Data;
  }


  function UnSerializeFromStr(&$str)
  {
    $len = rpc_int_ex_unserialize($str);
    $this->Data = rpc_unserialize($str, $len);
  }
}
?>