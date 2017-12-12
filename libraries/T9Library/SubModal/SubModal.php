<?
//  print "<script src='{$libroot}SubModal/SubModal.js' type='text/javascript' charset='UTF-8'></script>\n";
?>

<script src="<?=$libroot;?>SubModal/SubModal.js" type="text/javascript" charset="UTF-8"></script>

<div id="popupMask">
</div>

<div id="popupContainer">
  <div id="popupInner">
    <div id="popupTitleBar">
      <div id="popupTitle"></div>
      <div id="popupControls">
        <img id="popCloseBox" src="<?=$libroot;?>SubModal/close.gif" onclick="hidePopWin(false);"/>
      </div>
    </div>
    <iframe id="popupFrame" name="popupFrame" src="" style="width:100%;height:100%;" scrolling="auto" width="100%" height="100%" allowtransparency="true" frameborder="0"></iframe>
  </div>
</div>

