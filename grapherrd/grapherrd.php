<?PHP
/******************************************************************************
 * grapherrd.php
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
 * This file is the main.
 * It's building the HTML layout and calling other HTML subsections.
 ******************************************************************************/
header("Cache-Control: max-age=300, public");
header("Content-Type: text/html; charset=UTF-8");
print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<?PHP
// to compute running time, it is then used in the footer at the end of page.php
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;

require("config.php");
$cfg = new config("grapherrd.cfg", "graph.cfg");

print "<title>".$cfg->files[$_GET["page"]]." from ".$cfg->title." - "."</title>\n";
foreach ($cfg->css as $key => $css) {
  print "<link href=\"".$css."\" rel=\"stylesheet\" type=\"text/css\" />\n";
}
if ($cfg->js) {
  foreach ($cfg->js as $key => $js) {
    print "<script type=\"text/javascript\" src=\"".$js."\"></script>\n";
  }
}
print "</head>\n";

print "<body>\n";

// include the header
print "<div id=\"header\">\n";
if (preg_match("/^http:\/\//", $cfg->header)) {
	include($cfg->header);
} else {
	print $cfg->header;
	print "\n";
}
print "</div>\n";

// include the grapherrd needed elements
include("menu.php");
include("page.php");

?>

</body>
</html>
