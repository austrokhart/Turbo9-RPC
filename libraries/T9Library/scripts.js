//------------------------------------------------------------------------------

  function Trim(d)
  {  
    if (typeof d != "string")
      { return d }
    var c=d;
    var b="";
    b=c.substring(0,1);
    while(b==" ")
    {
      c=c.substring(1,c.length);
      b=c.substring(0,1)
    }
    b=c.substring(c.length-1,c.length);
    while(b==" ")
    {
      c=c.substring(0,c.length-1);
      b=c.substring(c.length-1,c.length)
    }
    return c
  }


  function GetByClassName(ATag, AClassName)
  {
    if (document.getElementsByClassName)  
    {
      return document.getElementsByClassName(AClassName);
    }
    else
    {
      All = document.getElementsByTagName(ATag);
      Arr = [];
      for (var i = 0; i < All.length; i++)
      {
        if (All[i].className == AClassName)
          { Arr.push(All[i]) }
      }
      return Arr;
    }
  }

    
  function GetCookie(Name)
  {
    var s = "T9_" + Name;
    var b = document.cookie.split(";");
    for (var i = 0; i < b.length; i++)
    {
      var e = b[i].split("=");
      if (Trim(e[0]) == s)
        { return(e[1]) };
    }
    return "";
  }

  
  function SetCookie(Name, Value)
  {
    var s = "T9_" + Name;
    var t = new Date();
    t.setTime(t.getTime() + (365*24*60*60*1000));
    document.cookie = s + "=" + Value + "; expires=" + t.toUTCString() + ";"
  }


  function SubmitForm(AForm, AName, AValue)
  {
    But = document.createElement('input');
    But.type = "hidden";
    But.name = AName;
    But.value = AValue;
    AForm.appendChild(But);
    AForm.submit();
  }


  function addEvent(obj, evType, fn)
  {
    if (obj.addEventListener)
    {
      obj.addEventListener(evType, fn, false);
      return true;
    } 
    else if (obj.attachEvent)
    {
      var r = obj.attachEvent("on"+evType, fn);
      return r;
    } 
    else 
    {
      return false;
    }
  }


  function removeEvent(obj, evType, fn, useCapture)
  {
    if (obj.removeEventListener)
    {
      obj.removeEventListener(evType, fn, useCapture);
      return true;
    } 
    else if (obj.detachEvent)
    {
      var r = obj.detachEvent("on"+evType, fn);
      return r;
    } 
    else 
    {
      alert("Handler could not be removed");
    }
  }


//------------------------------------------------------------------------------

  // Открываем картотеку в отдельном окне для квазимодального выбора
  function LookupClick(url, Id) 
  {
    var Inp1 = document.getElementById(Id);
    var Inp2 = document.getElementById('id_' + Id);

    if (Inp1 && Inp2)
    {
      var Str, Key;
      Str = Inp2.value;
      Key = Str.slice(0, Str.indexOf('}') + 1);
      Str = Str.slice(Key.length);
//    alert(Key + ' - ' + Str);
     
      if (Inp1.value != Str)
      {
        if (Inp1.value != "")
          { url += '&mask=' + Inp1.value; }
      }
      else
      {
        url += Key;
      }

      url += '&back=' + Id;
//    alert(url);
//    encodeURIComponent()
      window.open(url);
    }
    else
    {
      alert("not found: " + Id);
    }
  }


  // Выбор в квазимодальной картотеке, вставляем ссылку в вызвавшее окно
  function BackRef(InputID, DocID, Descr) 
  {
    if (window.opener)
    {
      var Inp = window.opener.document.getElementById(InputID);

      if (Inp)
      {
        Inp.value = Descr;
      }
      else
      {
        alert("not found: " + InputID);
      }

      Inp = window.opener.document.getElementById("id_" + InputID);
      if (Inp)
      {
        Inp.value = DocID + Descr;
      }
      else
      {
        alert("not found: " + "id_" + InputID);
      }

      window.close();
    }
    else
    {
      //alert("Alredy closed")
    }
  }


//------------------------------------------------------------------------------ 
// Сворачивающиеся секции


  function CollapseSec(Id, Close)
  {
    var Sec;
    Sec = document.getElementById('CCap_' + Id);
    if (Sec)
      { Sec.style.display = (Close ? "" : "none"); }

    Sec = document.getElementById('OCap_' + Id);
    if (Sec)
      { Sec.style.display = (Close ? "none" : ""); }

    Sec = document.getElementById('In_' + Id);
    if (Sec)
      { Sec.style.display = (Close ? "none" : ""); }
  }


  function ToggleSec(Id)
  {
    var Sec = document.getElementById('In_' + Id);
    if (Sec)
    {
      var Value = (Sec.style.display == "none" ? 0 : 1);
      CollapseSec(Id, Value);
      SetCookie("Collapse_" + Id, Value);
    }
    else
    {
      alert("not found: " + Id);
    }
  }


  function ToggleVisible(Id)
  {
    var Sec = document.getElementById(Id);
    if (Sec)
    {
      var Value = (Sec.style.display == "none" ? 0 : 1);
      Sec.style.display = (Value ? "none" : "")
    }
    else
    {
      alert("not found: " + Id);
    }
  }


  function InitSections()
  {
    var Arr = GetByClassName("div", "Section");
    for(var i = 0; i < Arr.length; i++)
    {
      var Sec = Arr[i];
      var Val = GetCookie("Collapse_" + Sec.id);
      if (Val == "") 
        { Val = Sec.getAttribute("Collapse"); }
      if (Val == "1")
        { CollapseSec(Sec.id, Val == "1") }
    }
  }


//------------------------------------------------------------------------------ 
// Textarea с автоматическим изменением высоты

/*
  function CalcHeigth(Edit)
  {
    NewEdit = Edit.cloneNode(true);
    NewEdit.style.cssText = "position:absolute; top:9999; left:9999; height:0px; width:"+Edit.clientWidth+"px";
    NewEdit.value = Edit.value + "\n1";
    Edit.parentNode.insertBefore(NewEdit, Edit);

    NewEdit.scrollTop = 10000;
    H = NewEdit.scrollTop;

    Edit.parentNode.removeChild(NewEdit);
    return H;
  }
*/

  function CalcHeigth(Edit)
  {
    var NewEdit = Edit.cloneNode(true);
//  alert(Edit.clientWidth);
    NewEdit.style.cssText = "position:absolute; top:-99; left:0; height:0px; width:"+Edit.clientWidth+"px";
    NewEdit.value = Edit.value + "\n1";
    document.body.insertBefore(NewEdit, null);

    NewEdit.scrollTop = 10000;
    var H = NewEdit.scrollTop;

    document.body.removeChild(NewEdit);
    return H;
  }

  
  function ResizeTextarea(Edit, Animate) 
  {
//  Edit.rows = Edit.value.split('\n').length;
    var H = CalcHeigth(Edit);
    if (H != Edit.clientHeight)
    {
      if (Animate)
      {
        Edit.style.height = H + 'px';
      }
      else
      {
        Edit.style.height = H + 'px';
      }
    }
  }


  function TextareaKeyUp(Edit) 
  {
    ResizeTextarea(Edit, 1);
  }


  function InitTextareas()
  {
    var Arr = document.getElementsByTagName("textarea");
    for(var i = 0; i < Arr.length; i++)
    {
      if (Arr[i].onkeyup)
      {
        ResizeTextarea(Arr[i], 0);
      }
    }
  }


//------------------------------------------------------------------------------ 
// Поддержка редактирования

  function CancelEdit(URL)
  {
    if (DocID != "")
    {
      URL += "&id=" + DocID;
    }
    window.location.href = "CancelDoc?backref=" + URL;
  }


//------------------------------------------------------------------------------ 

  window.onload = function() 
  {
    InitTextareas();
    InitSections();
  }
