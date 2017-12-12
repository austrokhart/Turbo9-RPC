<?
include_once 'func_rpc.php';
include_once 'func_doc.php';


//$ProxyService  = "TXWebProxy";
$ProxyService  = "T9WebProxy";
$ProxyGUID     = "{9B4F96CB-39A1-4EA7-B3BB-052203517FD9}";

//$ProxyService  = "TXProc";
//$ProxyGUID     = "{DB5AE8B5-02CA-415B-90BB-5F19BCC28A94}";


class TBSession
{
  public $SesIdx;
  public $Sock;


  function __construct() 
  {
    $_SESSION["SessionIdx"] = $_SESSION["SessionIdx"] + 1;
    $this->SesIdx = $_SESSION["SessionIdx"];
    $this->Sock = 0;
//  echo "construct T9Session {$this->SesIdx}<br>";
  }


  function __destruct() 
  {
//  echo "<br>destruct T9Session {$this->SesIdx}";
    $this->StandbySession();
    $this->SesIdx = 0;
  }


  function GetDocumentList()
  {
    return Explode(",", $this->ProcCall("Web.Docs", "GetDocClasses"));
  }


  function GetDocument($ADocID, $AFields = "")
  {
    return StrDocToArrDoc( $this->ProcCall("Web.Docs", "GetDocument", $ADocID."|".$AFields) );
  }


  function SaveDocument($ADocID, $AValues, $AFields = "")
  {
    return $this->ProcCall("Web.Docs", "SaveDocument", $ADocID.'|'.$AFields."|".ArrDocToStrDoc($AValues));
  }


  function SaveDocumentEx($ADocID, $AValues, $AParams = null)
  {
    if (!is_array($AParams))
      { $AParams = array(); }
    $AParams["DocID"] = $ADocID;
    return $this->ProcCall("Web.Docs", "SaveDocumentEx", ArrDocToStrDoc($AParams).ArrDocToStrDoc($AValues));
  }


  function DeleteDocument($ADocID, $ACheckRef = true)
  {
    $this->ProcCall("Web.Docs", "DeleteDocument", $ADocID.'|'.($ACheckRef ? "1" : "0"));
  }


  function Query($Params)
  {
    $ParamAsStr = ArrDocToStrDoc($Params);
    return StrQueryToArray( $this->ProcCall("Web.Docs", "QueryDocumentsEx", $ParamAsStr) );
  }


//-----------------------------------------------------------------------------
// Кэш метаданных - описания документов

  function GetDocumentInfo($ADocName)
  {
    $AllDocInfo = &$_SESSION["AllDocInfo"];

    if (!$AllDocInfo)
      { $AllDocInfo = array(); }

    $DocInfo = &$AllDocInfo[$ADocName];

    if (!$DocInfo) 
    {
      $vStr = $this->ProcCall("Web.Docs", "GetDocClassInfo", $ADocName);
//    echo "Get info for {$ADocName}: {$vStr}<br>\n";
      $DocInfo = StrDocInfoToArray($vStr);
    }

    return $DocInfo;
  }


//-----------------------------------------------------------------------------
// Pool редактируемых документов

  function GetEditedDocument($ADocID)
  {
    $EdtDocPool = &$_SESSION["EdtDocPool"];
    if (!$EdtDocPool)
      { return False; }

    if (!isset($EdtDocPool[$ADocID]))
      { return False; }

    return $EdtDocPool[$ADocID];
  }


  function SetEditedDocument($ADocID, $ADoc)
  {
    $EdtDocPool = &$_SESSION["EdtDocPool"];
    if (!$EdtDocPool)
      { $EdtDocPool = array(); }

    foreach ($EdtDocPool as $Key => $Doc)
    {
      if (is_object($Doc) && (!$Doc->Modified))
      {
        //!!!
        unset($EdtDocPool[$Key]);
      };
    };

    $ADoc->PoolTime = time();
    $EdtDocPool[$ADocID] = $ADoc;
  }


  function FreeEditedDocument($ADocID)
  {
    $EdtDocPool = &$_SESSION["EdtDocPool"];
    if (!$EdtDocPool)
      { return; }

    unset($EdtDocPool[$ADocID]);
  }

//-----------------------------------------------------------------------------
// Поддержка возвратов

  function SetRefferer($AKey, $ARefferer)
  {
    $AllRefferer = &$_SESSION["AllRefferer"];
    if (!$AllRefferer)
      { $AllRefferer = array(); }

//  print "Set refferer: {$AKey}, {$ARefferer}<br>\n";
    if ($ARefferer)
    {
      $AllRefferer[$AKey] = $ARefferer;
    }
    else
    {
      unset($AllRefferer[$AKey]);
    };
  }


  function GetRefferer($AKey)
  {
    $AllRefferer = &$_SESSION["AllRefferer"];
    if (!$AllRefferer)
      { return False; }

//  print "Get refferer: {$AKey}, {$AllRefferer[$AKey]}<br>\n";
    return $AllRefferer[$AKey];
  }


//-----------------------------------------------------------------------------
// Сетевое взаимодействие

  function InitSession()
  {
    global $ProxyServer, $ProxyPort, $ProxyService, $ProxyGUID;

    if ($this->Sock != 0)
      { throw new Exception("TBSession already opened"); };

    $this->Sock = rpc_connect($ProxyServer, $ProxyPort, $ProxyService, $ProxyGUID, $Info);
//  print "info: {$Info[0]} : {$Info[1]}, {$Info[2]}, {$Info[3]}<br>\n";
    $_SESSION["ConnectionInfo"] = $Info;

    $_SESSION["CustomData"] = array();
  }


  function RestoreSession()
  {
    global $ProxyServer, $ProxyPort, $ProxyService, $ProxyGUID;

    if ($this->Sock == 0)
    {
      $Info = $_SESSION["ConnectionInfo"];
//    print "info: {$Info[0]} : {$Info[1]}, {$Info[2]}, {$Info[3]}<br>\n";
      $this->Sock = rpc_reconnect($ProxyServer, $ProxyPort, $Info); 
    }
  }


  function StandbySession()
  {
    if ($this->Sock != 0)
    {
      rpc_standby($this->Sock);
      $this->Sock = 0;
    }
  }


  function ProcConnect($ProcServer, $DataServer, $InfobaseName, $UserName, $Password, $Role)
  {
    $this->InitSession();
    try
    {
      rpc_call($this->Sock, 0, $ProcServer."|".$DataServer."|".$InfobaseName."|".$UserName."|".$Password."|".$Role);
    }
    catch (Exception $e)
    {
      rpc_disconnect($this->Sock);
      $this->Sock = 0;
      throw $e;
    }
  }


  function ProcCall($AClsName, $AProcName, $Args = "")
  {
    $this->RestoreSession();
//  print ">>> $AClsName, $AProcName<br>\n";
    return rpc_call($this->Sock, 1, $AClsName."|".$AProcName."|".$Args);
//  return rpc_call_ex($this->Sock, 3, $AClsName, $AProcName, array($Args));
//  return rpc_call_ex($this->Sock, 3, $AClsName, $AProcName, $Args);
  }


  function ProcCall1($AClsName, $AProcName)
  {
    $this->RestoreSession();
    $n = func_num_args();
//  print ">>> $AClsName, $AProcName, Args=$n<br>\n";
    $Args = null;
    if ($n > 2) 
    {
      if ($n == 3)
      {
        // 3-й аргумент может быть простым или массивом
        $Args = func_get_arg(2);
      }
      else
      {
        // Если аргументов > 3 - упакуем в массив
        $Args = array();
        for ($i = 2; $i < $n; $i++)
        {
          $Args[] =  func_get_arg($i);
        }
      }
    }
    return rpc_call_ex($this->Sock, 3, $AClsName, $AProcName, $Args);
  }


  function ProcDisconnect()
  {
    try
    {
      $this->RestoreSession();
      rpc_disconnect($this->Sock);
    }
    catch (Exception $e)
    {
    }
    $this->Sock = 0;
    session_unset();
  }

}
?>