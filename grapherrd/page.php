<?PHP
/******************************************************************************
 * page.php
 * Copyright Antoine Delvaux
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA,
 * or go to http://www.gnu.org/copyleft/gpl.html
 ******************************************************************************/
/******************************************************************************
 * This file control the main div containing the RRD generated graphs.
 * 
 * It's building the HTML layout and creating RRD objects and graphs from the
 * configuration object. 
 ******************************************************************************/
require("graph.php");

if (!empty($_GET["page"])) {
  $target_cfg_file = $_GET["page"];
  $matches = preg_split("/\./", $target_cfg_file);
  $target_directory = $matches[0];
} 

if (!empty($_GET["debug"])) {
  $debug = true;
} else {
  $debug = false;
}

if (!empty($_GET["target"])) {
  $target = $_GET["target"];
  // --- style parameter, defines the graphs div width
  if (!empty($_GET["style"])) {
    $style = $_GET["style"];
  } else {
    $style = $cfg->graphstyle;
  }
  if ($style == 'long') {
      $width = 800;
  } else {
      $width = 1000;
  }

  if ($target != "summary") {
    // --- unique target graph

    // --- print the navigation index
    if (!empty($cfg->targets[$target]["rrd"])) {
      print "<ul id=\"pageindex\">";
      print "<li><a href=\"#top\">To page top</a><br/>&nbsp;</li>";
      foreach ($cfg->targets[$target]["rrd"] as $type => $rrd) {
	foreach ($cfg->t_name as $ct => $name) {
	  if (preg_match("/^".$ct."/", $type)) {
	    $cfg_type = $ct;
	  }
	}
	print "<li><a href=\"#".$type."\">".htmlspecialchars(str_replace("\\","",$cfg->t_name[$cfg_type]))."</a></li>";
      }
      print "</ul>\n";
    }

    // --- print the graphs title
    print "<div id=\"page\">";
    print "<div id=\"graphs\" style=\"width: ".$width."px;\">\n";
    print "<a name=\"top\"></a><h2>".$cfg->targets[$target]["title"]." graphs</h2>\n";

    // --- data from MRTG cfg file
    if ($debug) {
      print "<h2>Debug mode !</h2>\n";
      print "<ul>\n";
      print "<li>URI : ".htmlspecialchars($REQUEST_URI)."</li>\n";
      print "<li>Page : ".$target_cfg_file."</li>\n";
      print "<li>Name : ".$cfg->targets[$target]["name"]."</li>\n";
      print "<li>Title : ".$cfg->targets[$target]["title"]."</li>\n";
      print "<li>Address : ";
      foreach ($cfg->targets[$target]["addresses"] as $key => $addr) {
	print $addr."  ";
      }
      print "</li>\n";
      print "<li>Interface : ";
      foreach ($cfg->targets[$target]["interfaces"] as $key => $int) {
	print $int."  ";
      }
      print "</li>\n";
      print "<li>RRD files and MaxBytes : \n<ul>\n";
      if (!empty($cfg->targets[$target]["rrd"])) {
	foreach ($cfg->targets[$target]["rrd"] as $key => $rrd) {
	  print "<li>".$key." : ".$rrd." - Max : ".$cfg->targets[$target]["maxbytes"][$key]."</li>\n";
	}
      }
      print "</ul>\n</li>\n";
      print "<li>SetEnv : \n<ul>\n";
      foreach ($cfg->targets[$target]["env"] as $trgt => $tv) {
	print "<li>".$trgt."\n<ul>\n";
	foreach ($cfg->targets[$target]["env"][$trgt] as $key => $value) {
	  print "<li>".$key." : ".$value."</li>\n";
	}
	print "</ul>\n</li>\n";
      }
      print "</ul>\n</li>\n";
      print "</ul>\n";
    }

    // --- draw target graphs
    foreach ($cfg->targets[$target]["rrd"] as $type => $rrd) {
      if ($debug) {
	print "<hr />\n";
      }
      $gr[$type] = new graph($cfg,
			     $target,
			     $debug,
			     $target_directory,
			     $type,
			     $style
			     );
      if (!empty($_GET[$type])) {
	$gr[$type]->limit = $_GET[$type];
      }
      if (!empty($_GET["nopk".$type])) {
	$gr[$type]->nopeaks = $_GET["nopk".$type];
      }

      // --- graph parameter
      if (!empty($_GET["graph"])) {
	$gr[$type]->set_period($_GET["graph"]);
      }

      print "<a name=\"".$type."\"></a>\n";
      print $gr[$type]->draw();
      print "\n";
    }
  } else {
    // --- summary graphs

    print "<h2>Summary graphs</h2>\n";
    print "<table class=\"graph\">\n";

    foreach ($cfg->targets as $key => $target) {

      // --- build graph objects
      foreach ($target["rrd"] as $type => $rrd) {
	$gr[$type] = new graph($cfg,
			       $key,
			       $debug,
			       $target_directory,
			       $type,
			       $style
			       );
	$gr[$type]->set_summary(true);

	// --- graph parameter
	if (!empty($_GET["graph"])) {
	  $gr[$type]->set_period($_GET["graph"]);
	}
      }

      // --- draw first graph (output will not be used, but draw call is needed to compute the values printed in the table)
      print "<tr>\n";
      foreach ($target["rrd"] as $type => $rrd) {
	$first = $type;
	$gr[$first]->css_tiny = $cfg->css_tiny;
	$gr[$first]->draw();
	break;
      }

      // --- insert some useful data (table)
      print "<td>\n";
      $reqstr = $cfg->buildRequestString("target", $target["name"], $_GET);
      if ( !empty($target["envtitle"]["SUBTITLE"]) ) {
	print "<h3><a href=\"grapherrd.php?$reqstr\">".$target["env"][$first]["SUBTITLE"]."</a></h3>\n";
      } else {
	print "<h3><a href=\"grapherrd.php?$reqstr\">".$target["title"]."</a></h3>\n";
      }
      print "<table class=\"summary\">\n";
      print "<tr><th>Last&nbsp;update</th> <td colspan=\"3\">".$gr[$first]->get_lastupdate()."</td></tr>\n";
      print "<tr><th>Types</th> <td colspan=\"3\">";
      $ft = true;
      foreach ($target["rrd"] as $type => $rrd) {
	if ($ft) {
	  $ft = false;
	} else {
	  print ", ";
	}
	print $gr[$type]->type;
      }
      print "</td></tr>\n";
      print "<tr><th>Limits</th> <td colspan=\"3\">";
      $ft = true;
      foreach ($target["rrd"] as $type => $rrd) {
	if ($ft) {
	  $ft = false;
	} else {
	  print ", ";
	}
	print $gr[$type]->get_bandwidth();
      }
      print "</td></tr>\n";
      print "<tr><td></td> <th class=\"top\">Max</th> <th class=\"top\">Avg</th> <th class=\"top\">Last</th> </tr>\n";
      print "<tr><th>".str_replace("\\","",$cfg->t_val_str_1[$first])."</th><td colspan=\"3\"><p class=\"summary\">".$gr[$first]->last_val_str_1."</p></td></tr>\n";
      if ($cfg->t_nb_val[$type] > 1) {
        print "<tr><th>".str_replace("\\","",$cfg->t_val_str_2[$first])."</th><td colspan=\"3\"><p class=\"summary\">".$gr[$first]->last_val_str_2."</p></td></tr>\n";
      }
      print "</table>\n";
      print "</td>\n";

      // --- really draw all graphs
      foreach ($target["rrd"] as $type => $rrd) {
	print "<td>";
	print $gr[$type]->draw();
	print "</td>\n";
      }

      print "</tr>\n";
    }
    print "</table>\n";
  }
} elseif (!empty($_GET["page"])) {
  $target_cfg_file = $_GET["page"];
  print "<h2>".$cfg->files[$target_cfg_file]." graphs</h2>";
}

// add footer
print "<div id=\"pagefooter\" class=\"footer\">\n";
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
print "<a href=\"http://validator.w3.org/check/referer\"><img style=\"border:0;float:left;\" src=\"images/valid-xhtml10.png\" alt=\"Valid XHTML 1.0!\" /></a>\n";
print "<a href=\"http://jigsaw.w3.org/css-validator/check/referer\"><img style=\"border:0;float:right;\" src=\"images/vcss.png\" alt=\"Valid CSS!\" /></a>\n";
printf ("<p>%s<br />Page created in %.3f seconds by <em>grapherrd</em> %s<br />on %s.</p>\n", $cfg->footer, $totaltime, $cfg->version, date('l jS \of F Y H:i:s'));
print "</div>\n";

// --- end of #page div
print "</div></div>\n";

?>
