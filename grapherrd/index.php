<?PHP
print "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<?PHP
// to compute running time
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime;

require("config.php");
$cfg = new config("grapherrd.cfg", "graph.cfg");

print "<title>".$cfg->title."</title>\n";
foreach ($cfg->css as $key => $css) {
  print "<link href=\"".$css."\" rel=\"stylesheet\" type=\"text/css\" />\n";
}
foreach ($cfg->js as $key => $js) {
  print "<script type=\"text/javascript\" src=\"".$js."\"></script>\n";
}
print "</head>\n";

print "<body>\n";

// include the header
print "<div class=\"header\">\n";
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

// add footer
print "<div class=\"footer\">\n";
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
print "<a href=\"http://validator.w3.org/check/referer\"><img style=\"border:0;float:left;\" src=\"images/valid-xhtml10.png\" alt=\"Valid XHTML 1.0!\" /></a>\n";
print "<a href=\"http://jigsaw.w3.org/css-validator/check/referer\"><img style=\"border:0;float:right;\" src=\"images/vcss.png\" alt=\"Valid CSS!\" /></a>\n";
printf ("<p>%s<br />\nPage created in %.3f seconds by <em>grapherrd</em> %s.</p>\n", $cfg->footer, $totaltime, $cfg->version);
print "</div>";

?>

</body>
</html>
