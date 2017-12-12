
function OnEscPress(AEvent)
{ 
  if (AEvent.keyCode == 27) 
  {  
    window.top.hidePopWin(false)
  }
} 


function initPopUpTitle()
{
  Doc = window.parent.document;
  Title = Doc.getElementById("popupTitle");
  if (Title)
  {
//  alert(Title);
    Title.innerHTML = document.title;
  }
}

addEvent(window, "load", initPopUpTitle);
