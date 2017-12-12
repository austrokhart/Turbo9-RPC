<?

  function PrintArray($Array)
  {
    foreach ($Array as $Idx => $Val)
    {
      print "{$Idx}: {$Val} <br>\n";
    }
  }


  function PrintArrayAsTable(&$Array, &$N = 0)
  {
    print "<table class='Info'>\n";

    foreach ($Array as $Idx => $Val)
    {
      print "<tr>";

      if (is_array($Val))
      {
        $Count = count($Val);

        $N++;
        print "<td colspan='2'>\n";
        print "<a href='' onClick=\"ToggleVisible('Div{$N}'); return false;\">$Idx</a> ($Count)\n";
        print "<div id='Div{$N}' style='display:none'>\n";

        PrintArrayAsTable($Val, $N);

        print "</div></td>";
      }
      elseif (is_object($Val))
      {
        $N++;
        print "<td colspan='2'>";
        print "<a href='' onClick=\"ToggleVisible('Div{$N}'); return false;\">$Idx</a>";
        print "<div id='Div{$N}' style='display:none'>";

        print "<pre>";
        print_r($Val);
        print "</pre>";

        print "</div></td>";
      }
      else
      {
        print "<td>{$Idx}</td>";
        print "<td>";

        print $Val;

        print "</td>";
      }

      print "</tr>\n";
    }

    print "</table>\n";
  };


?>