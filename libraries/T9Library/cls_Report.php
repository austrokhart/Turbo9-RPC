<?
include_once 'cls_Form.php';


abstract class TBReport extends TBForm
{
  public $RepName;      // Имя отчета
  public $XSLName;      // Имя XSL файла
  public $Options;      // Опции отчета
  public $Params;       // Входные параметры (передаются на сервер)
  public $Data;         // HTML представление отформатированного отчета
  public $Errors;       // 


  function TBReport($ARepName, $AXSLName = null)
  {
    global $TBSession, $T9Lib;
//  echo "construct TBReport({$ARepName})<br>\n";

    if (!$AXSLName)
    {
      $AXSLName = "{$T9Lib}Report.xsl";
    };

    $this->RepName = $ARepName;
    $this->XSLName = $AXSLName;

    $this->Options = array("AutoBuild"=>1);
    $this->Params = array("Name"=>$ARepName);
  }


  function Show()
  {
    $this->InitForm();

    if (($this->Options["AutoBuild"]) || (isset($_GET["Update"])))
    {
      $this->BuildReport();
    };

    $this->MakeForm();
  }


  function InitForm()
  {
    // Abstract
  }


  function BuildReport()
  {
    global $TBSession;

    $this->Error = null;

    try 
    {
//    echo "BuildReport {$this->RepName}<br>\n";

      // Инициализируем параметры отчета
      $this->GetParams();
//    print_r($this->Params);

      // Строим отчет и получаем данные в виде XML-строки
      $rep = $TBSession->ProcCall("Web.Reps", "Build", ArrDocToStrDoc($this->Params));
//    SaveToFile("tmp\\Rep.xml", $rep);

      $xml = new DOMDocument();
      $xml->loadXML($rep);

      $xsl = new DOMDocument();
      $xsl->load($this->XSLName);
                  
      $proc = new XSLTProcessor;
      $proc->importStyleSheet($xsl); // attach the xsl rules

      ob_start(); 
      $proc->transformToURI($xml, 'php://output');
      $res = ob_get_contents();
      ob_end_clean();

      $this->Data = $res;
    } 
    catch (Exception $e) 
    {
      $this->Errors = array($e->getMessage());
    }
  }


  function GetParams()
  {
    // Abstract
  }



//-----------------------------------------------------------------------------
// Формирование страницы

  function MakeForm()
  {
    print "<div class='Report'>\n\n";

    $this->MakeTitle();
    $this->MakeParamForm();
    $this->MakeReport();

    print "</div> <!-- Report -->\n";
  }


  function MakeTitle()
  {
    // Abstract
  }


  function MakeParamForm()
  {
    // Abstract
  }


  function MakeReport()
  {
    if (!$this->Errors)
    {
      print $this->Data;
    }
    else
    {
      $this->ShowErrors($this->Errors);
    };
  }


  function ShowErrors($Errors)
  {
    PrintErrors($Errors);
  }


}


?>