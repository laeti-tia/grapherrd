<?PHP
/******************************************************************************
 * config.php
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
 * Class:	config	
 * This class is used for the configuration parameters
 * and to load the necessary information from the mrtg files.
 * 
 * Function Listing:
 * 		Public methods :
 * 			config()
 *                      buildRequestString()
 *                      readMRTGCfgFile()
 * 		Private methods :
 ******************************************************************************/ 

class config 
{
  //---Constants
  var $version = "0.9.8-0.snap (09/2014)";

  //---Properties
  // Configuration parameters
  var $rrdpath;
  var $backurl;
  var $graphurl;
  var $iconpath;
  var $defaultpage;
  var $graphstyle;
  var $maxima;
  var $unscaled;
  var $percentile;
  var $uselastupdate;

  var $files;
  var $targets;

  var $css;
  var $js;
  var $title = "statistics";
	
  /**********************************************************************
   * config($grapherrd_cfg, $graph_cfg)
   * 
   * Descr:	constructor
   * Type:     	public
   * Arguments:	$grapherrd_cfg	grapherrd configuration file to read
   *		$graph_cfg	graph configuration file to read
   * Returns:	the object
   **********************************************************************/ 
  function config($grapherrd_cfg, $graph_cfg) {
    // -- read grapherrd configuration file (main configuration file)
    if ($fh = fopen ($grapherrd_cfg, "r")) {
      while (!feof($fh)) {
	$line = fgets($fh, 4096);
	trim($line);
	if (preg_match("@^((#|//).*|)$@", $line)) {
	  // --- skip comments
	  continue;
	}
	$parameters = preg_split("/\s+=\s/", $line);
	if (preg_match("/\.cfg$/", $parameters[0])) {
	  $this->files[$parameters[0]] = trim($parameters[1]);
	} elseif ($parameters[0] == "rrdtool_path") {
	  $this->rrdpath = trim($parameters[1]);
	} elseif ($parameters[0] == "cfgfiles_path") {
	  $parameters = preg_split("/\s+/", $parameters[1]);
	  for ($i=0;$i<count($parameters)-1;$i++) {
	    $this->paths[$i] = trim($parameters[$i]);
	  }
	} elseif ($parameters[0] == "css") {
	  $parameters = preg_split("/\s+/", $parameters[1]);
	  for ($i=0;$i<count($parameters)-1;$i++) {
	    $this->css[$i] = trim($parameters[$i]);
	  }
	} elseif ($parameters[0] == "js") {
	  $parameters = preg_split("/\s+/", $parameters[1]);
	  for ($i=0;$i<count($parameters)-1;$i++) {
	    $this->js[$i] = trim($parameters[$i]);
	  }
	} else {
	  $this->$parameters[0] = trim($parameters[1]);
	}
      }
    }

    // read graphs configuration file
    if ($fh = fopen ($graph_cfg, "r")) {
      while (!feof($fh)) {
	$line = fgets($fh, 4096);
	trim($line);
	if (preg_match("@^((#|//).*|)$@", $line)) {
	  // --- skip comments
	  continue;
	}
	$parameters = preg_split("/\s+=\s/", $line);
	if ($parameters[1] != "") {
	  // --- graph type definition, replace some special chars, add shell escape chars
	  // --- the order in which these substitutions are done does matter

	  $par_graph = preg_split("/~/", $parameters[0]);
	  // --- delete new line character at the end
	  $parameters[1] = str_replace ("\n", "", $parameters[1]);
	  // --- replace all ':' by '\:'
	  $parameters[1] = str_replace (":", "\\:", $parameters[1]);
	  $this->{$par_graph[0]}[$par_graph[1]] = $parameters[1];
	}
	//	print $parameters[0];
      }
    }
		
  }
  /**********************************************************************
   * buildRequestString($type, $val, $Var_Array)
   * 
   * Descr:	build the arguments string needed for a HREF tag
   * Type:     	public
   * Args:     	$type		type of the fixed argument
   *	       	$val		value of the fixed argument
   *	       	$Var_Array	_GET array
   * Returns:  	the argument string
   **********************************************************************/ 
  function buildRequestString($type, $val, $Vars_Array) {
    $request_string = "";
    if ($type == "page") {
      $request_string .= "page=$val&amp;";
    } elseif (!empty($Vars_Array["page"]) && ($type != "menu")) {
      $request_string .= "page=".$Vars_Array["page"]."&amp;";
    }
    if ($type == "target") {
      $request_string .= "target=$val&amp;";
    } elseif (!empty($Vars_Array["target"]) && ($type != "menu")) {
      $request_string .= "target=".$Vars_Array["target"]."&amp;";
    }
    if ($type == "graph") {
      $request_string .= "graph=$val&amp;";
    } elseif (!empty($Vars_Array["graph"])) {
      $request_string .= "graph=".$Vars_Array["graph"]."&amp;";
    }
    if ($type == "style") {
      $request_string .= "style=$val&amp;";
    } elseif (!empty($Vars_Array["style"])) {
      $request_string .= "style=".$Vars_Array["style"]."&amp;";
    }

    $request_string = substr($request_string, 0, strlen($request_string)-5);
    return $request_string;
  }
  /**********************************************************************
   * readMRTGCfgFile($cfg_file, $private)
   * 
   * Descr:	read the useful information of a MRTG configuration file
   * Type:	public
   * Args:	$cfg_file	filename
   *		$private	whether we are in a private section
   * Returns:	true if no errors
   **********************************************************************/ 
  function readMRTGCfgFile($cfg_file, $private = false) {
    $current_target = "";
    $default['dir'] = "";
    $i = 0;
    while (!(file_exists($this->paths[$i].$cfg_file)) && ($i < count($this->paths)-1)) {
      $i++;
    }
		
    // --- Open and read MRTG config file
    if (file_exists($this->paths[$i].$cfg_file)) {
      if ($fh = fopen($this->paths[$i].$cfg_file, "r")) {
	while (!feof($fh)) {
	  $line = fgets($fh, 4096);
	  trim($line);
	  if (preg_match("@^((#|//).*|)$@", $line)) {
	    continue;
	  }

	  // --- WorkDir
	  $parameters = preg_split("/^WorkDir:\s*/",$line);
	  if ($parameters[1] != "") {
	    $workdir = trim($parameters[1]);
	    if (strrpos($workdir, "/") != strlen($workdir)-1) {
	      $workdir .= "/";
	    }
	    continue;
	  }

	  // --- target
	  $start = strpos($line, "[")+1;
	  $end = strpos($line, "]");
	  if (($end - $start) > 0) {
	    $previous_target = $current_target;
	    $current_target = substr($line, $start, $end-$start);
	  } else {
	    continue;
	  }

	  $parameters = preg_split("/\]\:\s*/", $line);

	  if ("^" == $current_target) {
	    // --- append values
	    continue;
	  }
	  if ("_" == $current_target) {
	    // --- default values
	    if (preg_match("/^Directory\[.+\]:/", $line)) {
	      // --- Directory
	      $default['dir'] = $workdir.trim($parameters[1]);
	      if (strrpos($default['dir'], "/") != strlen($default['dir'])-1) {
		$default['dir'] .= "/";
	      }
	    } elseif (preg_match("/^MaxBytes\[.+\]:/", $line)) {
	      // --- Directory
	      $default['maxbytes'] = $parameters[1];
	    }
	    continue;
	  }

	  if (preg_match("/^Target\[.+\]:/", $line)) {
	    // --- line == Target
	    if (preg_match("/(\S+)\~(\S+)$/", $current_target, $matches)) {
	      // -- sub targets (~)
	      if ($matches[1] != $previous_target) {
		// - missing main target, create a fake one
		$previous_target = $matches[1];
		$this->targets[$previous_target]['name'] = $matches[1];
	      }
	      $other = $matches[2];
	      $this->targets[$previous_target]['rrd'][$other] = $default['dir'].$current_target.".rrd";
	      if (!empty($default['maxbytes'])) {
		$this->targets[$previous_target]['maxbytes'][$other] = $default['maxbytes'];
	      }
	    } else {
	      // -- main target
	      $this->targets[$current_target]['name'] = $current_target;
	      $this->targets[$current_target]['rrd']['default'] = $default['dir'].$current_target.".rrd";
	      if (!empty($default['maxbytes'])) {
		$this->targets[$current_target]['maxbytes']['default'] = $default['maxbytes'];
	      }
	      preg_match_all("/([0-9\.]+):(\S+)@([\w\.\-]+)/", $parameters[1], $addresses);
	      $this->targets[$current_target]['interfaces'] = $addresses[1];
	      $this->targets[$current_target]['addresses'] = $addresses[3];
	    }

	  } elseif (preg_match("/^Title\[.+\]:/", $line)) {
	    // --- line == Title
	    if (preg_match("/~(\S+)$/", $current_target, $matches)) {
	      // -- sub targets (~)
	      $this->targets[$previous_target]['title'] = $parameters[1];
	    } else {
	      // -- main target
	      $this->targets[$current_target]['title'] = $parameters[1];
	    }

	  } elseif (preg_match("/^MaxBytes\[.+\]:/", $line)) {
	    // --- line == MaxBytes
	    if (preg_match("/~(\S+)$/", $current_target, $matches)) {
	      // -- sub targets (~)
	      $other = $matches[1];
	      $this->targets[$previous_target]['maxbytes'][$other] = $parameters[1];
	    } else {
	      // -- main target
	      $this->targets[$current_target]['maxbytes']['default'] = $parameters[1];
	    }

	  } elseif (preg_match("/^SetEnv\[.+\]:/", $line)) {
	    // --- line == SetEnv
	    if (preg_match("/~(\S+)$/", $current_target, $matches)) {
	      // -- subtargets (~)
	      $other = $matches[1];
	      preg_match_all("/(\S+)=\"([^\"]*)\"/", $parameters[1], $env, PREG_SET_ORDER);
	      foreach ($env as $key => $value) {
		$this->targets[$previous_target]['env'][$other][$value[1]] = $value[2];
	      }
	    } else {
	      // -- main target
	      preg_match_all("/(\S+)=\"([^\"]*)\"/", $parameters[1], $env, PREG_SET_ORDER);
	      foreach ($env as $key => $value) {
		$this->targets[$current_target]['env']['default'][$value[1]] = $value[2];
		if (!$private && (preg_match("/^PRIVATE/", $value[1]))) {
		  // --- delete PRIVATE targets
		  unset($this->targets[$current_target]['rrd'][$value[2]]);
		}
	      }
	    }
	  }
	  if (preg_match("/\~(\S+)$/", $current_target)) {
	    $current_target = $previous_target;
	  }
	}
	return true;
      } else {
	return false;
      }
    } else {
      return false;
    }
  }
}
?>
