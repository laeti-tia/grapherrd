<?PHP
/******************************************************************************
 * menu.php
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
 * This file control the vertical navigation menu.
 * 
 * It's building the HTML menu layout and creating links from the configuration
 * object. 
 ******************************************************************************/

print "<div id=\"menu\">\n";

// -- Are we in a private page ?
if (preg_match("/^\/private\//", $REQUEST_URI)) {
  $private = true;
} else {
  $private = false;
}

// --- Title
if (empty($HTTP_GET_VARS["page"])) {
  print "<h1>".$cfg->title."</h1>\n";
} else {
  $reqstr = $cfg->buildRequestString("menu", "", $HTTP_GET_VARS);
  print "<h1><a href=\"grapherrd.php?$reqstr\">".$cfg->title."</a></h1>\n";
  $read_cfg = $cfg->readMRTGCfgFile($HTTP_GET_VARS["page"], $private);
}

$target = $HTTP_GET_VARS["target"];


// ---  Time scale
print "<h2>Time scale</h2>\n";
print "<ul>\n";
$links = array (
		"daily" => "Daily",
		"weekly" => "Weekly",
		"monthly" => "Monthly",
		"yearly" => "Yearly"
		);
foreach ($links as $key => $link_name) {
  $reqstr = $cfg->buildRequestString("graph", $key, $HTTP_GET_VARS);
  if (!empty($cfg->targets[$target]["rrd"])) {
    foreach ($cfg->targets[$target]["rrd"] as $type => $rrd) {
      if (!empty($HTTP_GET_VARS[$type])) {
	$reqstr .= "&amp;$type=".$HTTP_GET_VARS[$type];
      }
      if (!empty($HTTP_GET_VARS["nopk".$type])) {
	$reqstr .= "&amp;nopk$type=".$HTTP_GET_VARS["nopk".$type];
      }
    }
  }
  print "<li><a href=\"grapherrd.php?$reqstr\">$link_name</a></li>\n";
}
print "</ul>\n";


// --- MRTG Files or Targets inside an MRTG file
if (empty($HTTP_GET_VARS["page"])) {
  // -- No MRTG file is selected
  print "<h2>Pages</h2>\n";
  print "<ul>\n";
  foreach ($cfg->files as $key => $file) {
    $reqstr = $cfg->buildRequestString("page", $key, $HTTP_GET_VARS);
    print "<li><a href=\"grapherrd.php?$reqstr\">$file</a></li>\n";
  }
  print "</ul>\n";
} else {
  // -- An MRTG file is selected
  $reqstr = $cfg->buildRequestString("menu", "", $HTTP_GET_VARS);
  print "<h2><a href=\"grapherrd.php?$reqstr\">".$cfg->files[$HTTP_GET_VARS["page"]]."</a></h2>\n";
  if ($read_cfg) {
    print "<ul>\n";
    foreach ($cfg->targets as $key => $target) {
      $reqstr = $cfg->buildRequestString("target", $key, $HTTP_GET_VARS);
      if (!empty($cfg->targets[$key]["env"])) {
	if (!empty($cfg->targets[$key]["env"]["default"]["SUBTITLE"])) {
	  $subtitle = $cfg->targets[$key]["env"]["default"]["SUBTITLE"];
	} else {
	  $subtitle = $cfg->targets[$key]["title"];
	  foreach ($cfg->targets[$key]["env"] as $trgt => $tv) {
	    $subtitle = $cfg->targets[$key]["env"][$trgt]["SUBTITLE"];
	    break;
	  }
	  foreach ($cfg->targets[$key]["rrd"] as $trgt => $rrd) {
	    if (empty($cfg->targets[$key]["env"][$trgt]["SUBTITLE"])) {
	      $cfg->targets[$key]["env"][$trgt]["SUBTITLE"] = $subtitle;
	    }
	  }
	}
	print "<li><a href=\"grapherrd.php?$reqstr\">".$subtitle."</a></li>\n";
      } else {
	print "<li><a href=\"grapherrd.php?$reqstr\">".$cfg->targets[$key]["title"]."</a></li>\n";
      }
    }
    
    $reqstr = $cfg->buildRequestString("target", "summary", $HTTP_GET_VARS);
    print "<li id=\"summary\"><a href=\"grapherrd.php?$reqstr\">Summary</a></li>\n";
    print "</ul>\n";
  } else {
    print "Error reading MRTG configuration file for page ".$HTTP_GET_VARS["page"];
  }
}

$target = $HTTP_GET_VARS["target"];

// --- Sizes
print "<h2>Sizes</h2>\n";
print "<ul>\n";
$links = array (
		"normal"	=> "Normal&nbsp;(640x480)",
		"tall"		=> "Tall",
		"long"		=> "Long",
		"big"		=> "Big&nbsp;(800x600)",
		"extralong"	=> "Extralong",
		"huge"		=> "Huge&nbsp;(1280x1024)"
		);
foreach ($links as $key => $link_name) {
  $reqstr = $cfg->buildRequestString("style", $key, $HTTP_GET_VARS);
  if (!empty($cfg->targets[$target]["rrd"])) {
    foreach ($cfg->targets[$target]["rrd"] as $type => $rrd) {
      if (!empty($HTTP_GET_VARS[$type])) {
	$reqstr .= "&amp;$type=".$HTTP_GET_VARS[$type];
      }
      if (!empty($HTTP_GET_VARS["nopk".$type])) {
	$reqstr .= "&amp;nopk$type=".$HTTP_GET_VARS["nopk".$type];
      }
    }
  }
  print "<li><a href=\"grapherrd.php?$reqstr\">$link_name</a></li>\n";
}
print "</ul>\n";

// --- Peaks & Limits
if (!empty($HTTP_GET_VARS["target"])) {
  if ($HTTP_GET_VARS["target"]!="summary") {
    print "<h2>Peaks &amp;<br />Vertical Scales</h2>\n";
    print "<form action=\"grapherrd.php\" method=\"get\">\n";
    print "<div>";
    foreach ($HTTP_GET_VARS as $key => $value) {
      if (!empty($cfg->targets[$target]["rrd"])) {
	if (empty($cfg->targets[$target]["rrd"][$key]) && (!preg_match("/^nopk/", $key))) {
	  print "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
	}
      }
    }
    print "</div>";
    print "<table class=\"menu\">\n";
    print "<tr><th class=\"top\">graph</th><th class=\"top\">scale</th><th class=\"top\">no pk</th></tr>\n";
    if (!empty($cfg->targets[$target]["rrd"])) {
      foreach ($cfg->targets[$target]["rrd"] as $type => $rrd) {
	// look for a reduced match on the begining of the string type
	$cfg_type = "default";
	foreach ($cfg->t_name as $ct => $name) {
	  if (preg_match("/^".$ct."/", $type)) {
	    $cfg_type = $ct;
	  }
	}
	print "<tr><td class=\"first\">".htmlspecialchars(str_replace("\\","",$cfg->t_name[$cfg_type]))."</td>";
	print "<td><input name=\"".$type."\" type=\"text\" size=\"10\" value=\"".$HTTP_GET_VARS[$type]."\" /></td>";
	print "<td><input type=\"checkbox\" name=\"nopk".$type."\" value=\"true\"";
	if ($HTTP_GET_VARS["nopk".$type]) {
	  print " checked";
	}
	print " /></td></tr>\n";
      }
    }
    print "<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" value=\"draw graphs\" /></td></tr>";
    print "</table></form>\n";
  }
}

print "<h2>Preferences</h2>\n";
print "<p>If you want to have your browser remembering the graph and size parameters, click on the wanted links and fill the various fields above. Then bookmark the resulting page.</p>\n";

print $cfg->menu_footer;
print "\n</div>\n";
?>

