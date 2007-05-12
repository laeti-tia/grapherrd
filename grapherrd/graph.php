<?PHP
/******************************************************************************
 * graph.php
 *
 * This class is used to create the graph from MRTG parameters
 * 
 * Versions :
 *  - 04/06/29	1.0.3	Antoine Delvaux <antoine(at)belnet.be>
 *			- XHTML 1.0 compliance
 *  - 04/01/23	1.0.2	Antoine Delvaux <antoine(at)belnet.be>
 *			check against availability of '--force-rules-legend' option of rrdtool graph
 *  - 05/09/02	1.0.1	Antoine Delvaux <antoine(at)belnet.be>
 *			moved configuration options to grapherrd.cfg
 *			deleted redundant access methods
 *			added pourcent display
 *  - 26/11/01  1.0.0   Antoine Delvaux <antoine(at)belnet.be>
 *                      created
 ******************************************************************************/
/******************************************************************************
 * Class:       graph  
 * Function Listing:
 *              Public methods :
 *                      graph()
 *			config()
 *			set_title($t)
 *			add_path($p)	** should be moved to a private method and path be given with constructor
 *			set_type($t)
 *			set_max($m)
 *			set_summary($m)
 *			set_style($s)
 *			set_period($p)
 *                      draw()
 *			get_bandwidth()
 *			get_lastupdate()
 *              Private methods :
 *			set_ouput_path($o)
 *			nb_format($val)
 ******************************************************************************/

class graph
{
  //---Public properties (can be changed from outside the class)
  var $rrdpath = "rrdtool";
  var $debug;
  var $name;
  var $title;
  var $limit;
  var $css_tiny;
  var $nopeaks;

  //---Private properties (should not be changed from outside the class, should use an access function)
  var $file;
  var $type;
  var $max;
  var $summary;
  var $style;
  var $period;
  var $nb_val;
  var $root_path;
  var $url_path;

  //---Private properties (cannot be changed directly)
  var $width;
  var $height;
  var $start;
  var $val_mult_1;
  var $val_mult_2;
  var $val_str_1;
  var $val_str_2;
  var $val_fmt_1;
  var $val_fmt_2;
  var $pc_str_1;
  var $pc_str_2;
  var $leg_str_1;
  var $leg_str_2;
  var $leg_str_1s;
  var $leg_str_2s;
  var $leg_str_1p;
  var $leg_str_2p;
  var $leg_str_1ps;
  var $leg_str_2ps;
  var $leg_ver;
  var $max_str1;
  var $max_str2;
  var $max_cc;
  var $last_val_str_1 = "";
  var $last_val_str_2 = "";
  var $calc_pc_1 = false;
  var $calc_pc_2 = false;
  var $val_col_1 = "";
  var $val_col_2 = "";
  var $val_col_3 = "";
  var $val_col_1p = "";
  var $val_col_2p = "";
  var $val_col_3p = "";
  var $documentation = "";

  var $output_file;
  var $output_path;

  /**********************************************************************
   * graph()
   * 
   * Description:	constructor
   * Type:		public
   * Arguments:		$cfg		the configuration object from grapherrd
   *			$trgt		the MRTG target
   *			$d		debug flag
   *			$trgt_dir	the target directory
   *			$type		graph type
   *			$style		graph style
   *			$period		graph period
   *			$pk		peaks flag
   * Returns:		the object
   **********************************************************************/ 

  function graph($cfg, $trgt, $debug = false, $trgt_dir = "", $type = "default", $style = "normal", $period = "daily", $pk = false) {

    // --- set global vars
    $this->rrdpath = $cfg->rrdpath;

    // --- then set the parameters given
    $this->name = $cfg->targets[$trgt]["name"];
    $this->file = $cfg->targets[$trgt]["rrd"][$type];
    if (!empty($cfg->targets[$trgt]["maxbytes"][$type])) {
      $this->set_max($cfg->targets[$trgt]["maxbytes"][$type]);
    }
    if (!empty($cfg->targets[$trgt]["env"][$type]["SUBTITLE"])) {
      $this->title = $cfg->targets[$trgt]["env"][$type]["SUBTITLE"];
    } elseif (!empty($cfg->targets[$trgt]["env"]["default"]["SUBTITLE"])) {
      $this->title = $cfg->targets[$trgt]["env"]["default"]["SUBTITLE"];
    }
    $this->debug = $debug;
    $this->root_path = str_replace("//", "/", $cfg->graph_root_path."/");
    $up = $cfg->graph_url_path."/".$trgt_dir."/";
    $this->url_path = str_replace("//", "/", $up);
    $this->set_type($type, $cfg);
    $this->set_style($style);
    $this->set_period($period);
    $this->nopeaks = $pk;

    $this->set_output_path($this->root_path.$this->url_path);

    // -- finally set default values
    $this->limit = 0;
    $this->set_summary(false);

    return($this);
  }

  /**********************************************************************
   * set_type($t)
   * 
   * Descr:	set type of the graph
   * Type:	public
   * Arguments: $t		the type (suffix found after the ~ in the MRTG Target entry)
   *		$cfg		the configuration object
   * Returns:	true if succeed
   **********************************************************************/ 

  function set_type($t, $cfg) {
    $this->type = "";

    // look for a reduced match on the begining of the string type
    // last match is kept
    foreach ($cfg->t_name as $type => $name) {
      if (preg_match("/^".$type."/", $t)) {
	$this->type = $type;
      }
    }
    if (($this->type == "") && (!empty($cfg->t_name["default"]))) {
      $this->type = "default";
    }

    if ($this->debug) {
      print "<p>type given : '".$t."'<br /> type found : '".$this->type;
    }

    if ($this->type != "") {
      if ($this->debug) {
	print "' from config file</p>\n";
      }
      $this->name = $this->name."~".$t;
      // title doesn't use escapes '\'
      $this->title = $this->title." ".str_replace("\\","",$cfg->t_name[$this->type]);
      $this->nb_val = $cfg->t_nb_val[$this->type];
      $this->var_mult_1 = $cfg->t_var_mult_1[$this->type];
      $this->val_str_1 = $cfg->t_val_str_1[$this->type];
      $this->val_str_3 = $cfg->t_val_str_3[$this->type];
      $this->val_fmt_1 = $cfg->t_val_fmt_1[$this->type];
      $this->val_fmt_3 = $cfg->t_val_fmt_3[$this->type];
      $this->pc_str_1 = $cfg->t_pc_str_1[$this->type];
      $this->leg_str_1 = $cfg->t_leg_str_1[$this->type];
      $this->leg_str_1s = $cfg->t_leg_str_1s[$this->type];
      $this->leg_str_1p = $cfg->t_leg_str_1p[$this->type];
      $this->leg_str_1ps = $cfg->t_leg_str_1ps[$this->type];
      // vertical legend doesn't use escapes '\'
      $this->leg_ver = str_replace("\\","",$cfg->t_leg_ver[$this->type]);
      // the leg_tab is used in HTML code, not as argument for RRD
      $this->leg_tab = str_replace("\\","",$cfg->t_leg_tab[$this->type]);
      $this->max_str1 = $cfg->t_max_str1[$this->type];
      $this->max_str2 = $cfg->t_max_str2[$this->type];
      $this->val_col_1 = $cfg->t_val_col_1[$this->type];
      $this->val_col_1p = $cfg->t_val_col_1p[$this->type];
      $this->max_cc = $cfg->t_max_cc[$this->type];
      if ($this->max_cc) {
	// if max_cc is set we must define _3 parameters
	$this->leg_str_3 = $cfg->t_leg_str_3[$this->type];
	$this->leg_str_3s = $cfg->t_leg_str_3s[$this->type];
	$this->leg_str_3p = $cfg->t_leg_str_3p[$this->type];
	$this->leg_str_3ps = $cfg->t_leg_str_3ps[$this->type];
	$this->val_col_3 = $cfg->t_val_col_3[$this->type];
	$this->val_col_3p = $cfg->t_val_col_3p[$this->type];
      }
      $this->documentation = $cfg->t_documentation[$this->type];
      if ($this->nb_val == 2) {
      // if the parameteres for the second values are empty, we copy the parameters for the first values
	if (empty($cfg->t_var_mult_2[$this->type])) {
	$this->var_mult_2 = $cfg->t_var_mult_1[$this->type];
	} else {
	  $this->var_mult_2 = $cfg->t_var_mult_2[$this->type];
	}
	if (empty($cfg->t_val_str_2[$this->type])) {
	  $this->val_str_2 = $cfg->t_val_str_1[$this->type];
	} else {
	  $this->val_str_2 = $cfg->t_val_str_2[$this->type];
	}
	if (empty($cfg->t_val_fmt_2[$this->type])) {
	  $this->val_fmt_2 = $cfg->t_val_fmt_1[$this->type];
	} else {
	  $this->val_fmt_2 = $cfg->t_val_fmt_2[$this->type];
	}
	if (empty($cfg->t_pc_str_2[$this->type])) {
	  $this->pc_str_2 = $cfg->t_pc_str_1[$this->type];
	} else {
	  $this->pc_str_2 = $cfg->t_pc_str_2[$this->type];
	}
	if (empty($cfg->t_leg_str_2[$this->type])) {
	  $this->leg_str_2 = $cfg->t_leg_str_1[$this->type];
	} else {
	  $this->leg_str_2 = $cfg->t_leg_str_2[$this->type];
	}
	if (empty($cfg->t_leg_str_2s[$this->type])) {
	  $this->leg_str_2s = $cfg->t_leg_str_1s[$this->type];
	} else {
	  $this->leg_str_2s = $cfg->t_leg_str_2s[$this->type];
	}
	if (empty($cfg->t_leg_str_2p[$this->type])) {
	  $this->leg_str_2p = $cfg->t_leg_str_1p[$this->type];
	} else {
	  $this->leg_str_2p = $cfg->t_leg_str_2p[$this->type];
	}
	if (empty($cfg->t_leg_str_2ps[$this->type])) {
	  $this->leg_str_2ps = $cfg->t_leg_str_1ps[$this->type];
	} else {
	  $this->leg_str_2ps = $cfg->t_leg_str_2ps[$this->type];
	}
	if (empty($cfg->t_val_col_2[$this->type])) {
	  $this->val_col_2 = $cfg->t_val_col_1[$this->type];
	} else {
	  $this->val_col_2 = $cfg->t_val_col_2[$this->type];
	}
	if (empty($cfg->t_val_col_2p[$this->type])) {
	  $this->val_col_2p = $cfg->t_val_col_1p[$this->type];
	} else {
	  $this->val_col_2p = $cfg->t_val_col_2p[$this->type];
	}
      } else {
	$this->var_mult_2 = $this->var_mult_1;
      }
    } else {
      $this->type = "default";
      $this->title = $this->name;
      // --- default legends
      if ($this->debug) {
	print $this->type."' from script</p>\n";
      }
      $this->nb_val = 2;
      $this->var_mult_1 = 8;
      $this->var_mult_2 = 8;
      $this->val_str_1 = "In\ \ \ \\\:\ \ ";
      $this->val_str_2 = "Out\ \ \\\:\ \ ";
      $this->val_fmt_1 = "%5.1lf\ %s";
      $this->val_fmt_2 = "%5.1lf\ %s";
      $this->pc_str_1 = "%3.0lf%%";
      $this->pc_str_2 = "%3.0lf%%";
      $this->leg_str_1 = "Inbound\ traffic";
      $this->leg_str_2 = "Outbound\ traffic";
      $this->leg_str_1s = "In";
      $this->leg_str_2s = "Out";
      $this->leg_str_1p = "Peak\ Inbound\ \ \ ";
      $this->leg_str_2p = "Peak\ Outbound";
      $this->leg_str_1ps = "pk\ In";
      $this->leg_str_2ps = "pk\ Out";
      $this->leg_ver = "bits/s";
      $this->leg_tab = "bps";
      $this->max_str1 = "100%\ Bandwidth\ \(";
      $this->max_str2 = "bps\)";
      $this->val_col_1 = "00cc00";
      $this->val_col_2 = "0000ff";
      $this->val_col_1p = "006600";
      $this->val_col_2p = "ff77ff";
      $this->max_cc = "0";
      $this->documentation = "This graph draws the traffic going in and out of the router's interface.";
      $this->documentation_1 = "Traffic going into the interface.";
      $this->documentation_2 = "Traffic going out of the interface.";
      $this->documentation_limit = "Upper limit of the traffic";
      
    }

    $this->set_max($this->max);

    return true;
  }

  /**********************************************************************
   * set_max($m)
   * 
   * Description: set maximum line of the graph (red line)
   * Type:        public
   * Arguments:   the max value
   * Returns:     true if succeed
   **********************************************************************/ 

  function set_max($max) {
    if ($this->var_mult_1 != 0) {
      $this->max = $max * $this->var_mult_1;
    } else  {
      $this->max = $max;
    }
    // max value not null and correct percentage string definition
    // we must calculate and print a percentage value
    if ($this->max != 0 && preg_match("/%.*l[ef]/", $this->pc_str_1)) {
      $this->calc_pc_1 = true;
    } else {
      $this->calc_pc_1 = false;
    }
     if ($this->max != 0 && preg_match("/%.*l[ef]/", $this->pc_str_2)) {
      $this->calc_pc_2 = true;
    } else {
      $this->calc_pc_2 = false;
    }
    return true;
  }

  /**********************************************************************
   * set_summary($v, $t)
   * 
   * Description: set summary flag
   * Type:        public
   * Arguments:   a boolean value
   * Returns:     true if succeed
   **********************************************************************/ 

  function set_summary($fl) {
    $this->summary = $fl;
    $this->set_style($this->style);
    if($this->summary) {
      $this->title="";
    }
    return true;
  }

  /**********************************************************************
   * set_style($s)
   * 
   * Description: set style of the graph
   * Type:        public
   * Arguments:   the style
   * Returns:     true if succeed
   **********************************************************************/ 

  function set_style($s = "normal") {
    $this->style = $s;

    if ($this->style == "normal") {
      $this->width = 400;
      $this->height = 100;
    } elseif ($this->style == "tall") {
      $this->width = 400;
      $this->height = 200;
    } elseif ($this->style == "long") {
      $this->width = 530;
      $this->height = 100;
    } elseif ($this->style == "big") {
      $this->width = 530;
      $this->height = 200;
    } elseif ($this->style == "extralong") {
      $this->width = 840;
      $this->height = 100;
    } elseif ($this->style == "huge") {
      $this->width = 840;
      $this->height = 200;
    }

    if ($this->summary) {
      $this->width = $this->width / 3;
      $this->height = 70;
    }

    return true;
  }

  /**********************************************************************
   * set_period($p)
   * 
   * Description: set period of the graph
   * Type:        public
   * Arguments:   the period
   * Returns:     true if succeed
   **********************************************************************/ 

  function set_period($p = "daily") {
    $this->period = $p;

    if ($this->period == "daily") {
      $this->start = "end-36h";
    } elseif ($this->period == "weekly") {
      $this->start = "end-10d";
    } elseif ($this->period == "monthly") {
      $this->start = "end-6w";
    } elseif ($this->period == "yearly") {
      $this->start = "end-18mon";
    }

    return true;
  }

  /**********************************************************************
   * draw()
   * 
   * Description: invoke RRDTool to draw the graph
   * Type:        public
   * Arguments:	$debug : debugging flag   
   * Returns:     the IMG tag if succeed
   **********************************************************************/ 

  function draw () {
    $output_file = $this->name."-".$this->style."-".$this->width."-".$this->height."-".$this->start.".png";

    $rrd_cmd = $this->rrdpath." graph ".$this->output_path.$output_file." -v \"".$this->leg_ver."\" -a PNG -t \"".$this->title."\"";
    $rrd_cmd .= " -w ".$this->width." -h ".$this->height." -s ".$this->start;

    // check how to draw HRULE
    exec($this->rrdpath." graph", $output, $return_status);
    foreach ($output as $line) {
	if (preg_match("/--force-rules-legend/", $line)) {
	    $rrd_cmd .= " --force-rules-legend";
	}
    }

    // --- limit value
    if ($this->limit > 0) {
      $rrd_cmd .= " -u ".$this->limit." -r";
    }
    // to be improved.... ?
    //    elseif (!$this->debug && !$this->nopeaks) {
    //      $rrd_cmd .= " --lazy";
    //    }

    // --- Average values
    $rrd_cmd .= " DEF:in=".$this->file.":ds0:AVERAGE";
    if ($this->nb_val == 2) {
      $rrd_cmd .= " DEF:out=".$this->file.":ds1:AVERAGE";
    }
    $rrd_cmd .= " CDEF:fin=in,".$this->var_mult_1.",*";
    if ($this->nb_val == 2) {
      $rrd_cmd .= " CDEF:fout=out,".$this->var_mult_2.",*";
    }
    if ($this->max_cc) {
      $rrd_cmd .= " CDEF:finlim=fin,".$this->max.",MIN";
      if ($this->nb_val == 2) {
	$rrd_cmd .= " CDEF:foutlim=fout,".$this->max.",MIN";
      }
    }

    // --- Max values
    $rrd_cmd .= " DEF:min=".$this->file.":ds0:MAX";
    if ($this->nb_val == 2) {
      $rrd_cmd .= " DEF:mout=".$this->file.":ds1:MAX";
    }
    $rrd_cmd .= " CDEF:fmin=min,".$this->var_mult_1.",*";
    if ($this->nb_val == 2) {
      $rrd_cmd .= " CDEF:fmout=mout,".$this->var_mult_2.",*";
    }
    if ($this->max_cc) {
      $rrd_cmd .= " CDEF:fminlim=fmin,".$this->max.",MIN";
      if ($this->nb_val == 2) {
	$rrd_cmd .= " CDEF:fmoutlim=fmout,".$this->max.",MIN";
      }
    }

    // --- drawing commands : normal
    if ($this->max_cc) {
      // --- change color when over limit
      $rrd_cmd .= " AREA:fin#".$this->val_col_3.":";
      if (!$this->summary) {
	$rrd_cmd .= $this->leg_str_3;
      } else {
	$rrd_cmd .= $this->leg_str_3s;
      }
      // then draw only limited traffic
      $rrd_cmd .= " AREA:finlim#".$this->val_col_1.":";
    } else {
      $rrd_cmd .= " AREA:fin#".$this->val_col_1.":";
    }
    if (!$this->summary) {
      $rrd_cmd .= $this->leg_str_1;
    } else {
      $rrd_cmd .= $this->leg_str_1s;
    }
    if ($this->nb_val == 2) {
      $rrd_cmd .= " LINE1:fout#".$this->val_col_2.":";
      if (!$this->summary) {
	$rrd_cmd .= $this->leg_str_2;
      } else {
	$rrd_cmd .= $this->leg_str_2s;
      }
    }

    // --- drawing commands : max
    if (($this->max != "") && ($this->max_str1 != "")) {
      $rrd_cmd .= " HRULE:".$this->max."#ff0000:";
      if (!$this->summary) {
	$rrd_cmd .= $this->max_str1;
	if ($this->max_str2 != "") {
	  $rrd_cmd .= $this->nb_format($this->max).$this->max_str2;
	}
      }
    }
    if (!$this->summary) {
      $rrd_cmd .= "\\\l";
    }

    // --- drawing commands : peaks
    if (!$this->nopeaks && ($this->period != "daily")) {
      // --- change color when over limit
      if ($this->max_cc) {
	$rrd_cmd .= " LINE1:fmin#".$this->val_col_3p.":";
	if (!$this->summary) {
	  $rrd_cmd .= $this->leg_str_3p;
	} else {
	  $rrd_cmd .= $this->leg_str_3ps;
	}
	// then draw only limited traffic
	$rrd_cmd .= " LINE1:fminlim#".$this->val_col_1p.":";
      } else {
	$rrd_cmd .= " LINE1:fmin#".$this->val_col_1p.":";
      }
      if (!$this->summary) {
	$rrd_cmd .= $this->leg_str_1p;
      } else {
	$rrd_cmd .= $this->leg_str_1ps;
      }
      if ($this->nb_val == 2) {
	$rrd_cmd .= " LINE1:fmout#".$this->val_col_2p.":";
	if (!$this->summary) {
	  $rrd_cmd .= $this->leg_str_2p;
	} else {
	  $rrd_cmd .= $this->leg_str_2ps;
	}
      }
      if (!$this->summary) {
	$rrd_cmd .="\\\l";
      }
    }

    // --- value lines writing
    if (!$this->summary) {
      if ($this->max_cc) {
	// -- over values
       	$rrd_cmd .= " CDEF:finover=fin,finlim,-";
       	$rrd_cmd .= " CDEF:fminover=fmin,fminlim,-";
	$rrd_cmd .= " GPRINT:fminover:MAX:".$this->val_str_3."\ Max\ ".$this->val_fmt_3;
	$rrd_cmd .= " GPRINT:finover:AVERAGE:-\ \ Avg\ ".$this->val_fmt_3;
	$rrd_cmd .= " GPRINT:finover:LAST:-\ \ Last\ ".$this->val_fmt_3;
	$rrd_cmd .= "\\\l";

	// -- normal values
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " CDEF:pcin=finlim,100,*,".$this->max.",/";
	  $rrd_cmd .= " CDEF:mpcin=fminlim,100,*,".$this->max.",/";
	}
	$rrd_cmd .= " GPRINT:fminlim:MAX:".$this->val_str_1."\ Max\ ".$this->val_fmt_1;
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " GPRINT:mpcin:MAX:".$this->pc_str_1;
	} elseif ($this->pc_str_1 != "") {
	  $rrd_cmd .= $this->pc_str_1;
	}
	$rrd_cmd .= " GPRINT:finlim:AVERAGE:-\ \ Avg\ ".$this->val_fmt_1;
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " GPRINT:mpcin:AVERAGE:".$this->pc_str_1;
	} elseif ($this->pc_str_1 != "") {
	  $rrd_cmd .= $this->pc_str_1;
	}
	$rrd_cmd .= " GPRINT:finlim:LAST:-\ \ Last\ ".$this->val_fmt_1;
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " GPRINT:mpcin:LAST:".$this->pc_str_1;
	} elseif ($this->pc_str_1 != "") {
	  $rrd_cmd .= $this->pc_str_1;
	}
      } else {
	// -- normal values
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " CDEF:pcin=fin,100,*,".$this->max.",/";
	  $rrd_cmd .= " CDEF:mpcin=fmin,100,*,".$this->max.",/";
	}
	$rrd_cmd .= " GPRINT:fmin:MAX:".$this->val_str_1."\ Max\ ".$this->val_fmt_1;
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " GPRINT:mpcin:MAX:".$this->pc_str_1;
	} elseif ($this->pc_str_1 != "") {
	  $rrd_cmd .= $this->pc_str_1;
	}
	$rrd_cmd .= " GPRINT:fin:AVERAGE:-\ \ Avg\ ".$this->val_fmt_1;
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " GPRINT:mpcin:AVERAGE:".$this->pc_str_1;
	} elseif ($this->pc_str_1 != "") {
	  $rrd_cmd .= $this->pc_str_1;
	}
	$rrd_cmd .= " GPRINT:fin:LAST:-\ \ Last\ ".$this->val_fmt_1;
	if ($this->calc_pc_1) {
	  $rrd_cmd .= " GPRINT:mpcin:LAST:".$this->pc_str_1;
	} elseif ($this->pc_str_1 != "") {
	  $rrd_cmd .= $this->pc_str_1;
	}
      }

      // -- second graph values
      if ($this->nb_val == 2) {
	$rrd_cmd .= "\\\l";
	if ($this->calc_pc_2) {
	  $rrd_cmd .= " CDEF:pcout=fout,100,*,".$this->max.",/";
	  $rrd_cmd .= " CDEF:mpcout=fmout,100,*,".$this->max.",/";
	}
	$rrd_cmd .= " GPRINT:fmout:MAX:".$this->val_str_2."\ Max\ ".$this->val_fmt_2;
	if ($this->calc_pc_2) {
	  $rrd_cmd .= " GPRINT:mpcout:MAX:".$this->pc_str_2;
	} elseif ($this->pc_str_2 != "") {
	  $rrd_cmd .= $this->pc_str_2;
	}
	$rrd_cmd .= " GPRINT:fout:AVERAGE:-\ \ Avg\ ".$this->val_fmt_2;
	if ($this->calc_pc_2) {
	  $rrd_cmd .= " GPRINT:pcout:AVERAGE:".$this->pc_str_2;
	} elseif ($this->pc_str_2 != "") {
	  $rrd_cmd .= $this->pc_str_2;
	}
	$rrd_cmd .= " GPRINT:fout:LAST:-\ \ Last\ ".$this->val_fmt_2;
	if ($this->calc_pc_2) {
	  $rrd_cmd .= " GPRINT:pcout:LAST:".$this->pc_str_2;
	} elseif ($this->pc_str_2 != "") {
	  $rrd_cmd .= $this->pc_str_2;
	}
      }
    } else {
      $rrd_cmd .= " PRINT:fmin:MAX:%5.1lf\%s";
      $rrd_cmd .= " PRINT:fin:AVERAGE:%6.1lf\%s";
      $rrd_cmd .= " PRINT:fin:LAST:%6.1lf\%s";
      if ($this->nb_val == 2) {
	$rrd_cmd .= " PRINT:fmout:MAX:%5.1lf\%s";
	$rrd_cmd .= " PRINT:fout:AVERAGE:%6.1lf\%s";
	$rrd_cmd .= " PRINT:fout:LAST:%6.1lf\%s";
      }
    }


    // --- drawing commands : data not available
    $rrd_cmd .= " CDEF:down=in,UN,INF,0,IF";
    if (!$this->debug) {
      $rrd_cmd .= " AREA:down#e0e0e0";
    } else {
      print "<p><em>Command</em> : ".htmlspecialchars($rrd_cmd)."</p>\n";
    }
    unset($output);
    exec($rrd_cmd, $output, $return_status);
    if ($this->debug) {
      print "<p><em>Return</em> : ".$return_status."</p>\n";
    }
    for ($i=1; $i<=6; $i++) {
      if (substr($output[$i], -1) == ";") {
	$output[$i] .= "&nbsp;";
      }
    }
    $this->last_val_str_1 = $output[1]." ".$output[2]." ".$output[3];
    $this->last_val_str_2 = $output[4]." ".$output[5]." ".$output[6];
    $tag = "<img alt=\"".htmlspecialchars($this->title)."\" src=\"".$this->url_path.$output_file."\" />";

    return ($tag);
  }

  /**********************************************************************
   * set_output_path($o)
   * 
   * Description: set output path of the graph drawn and create directory if needed
   * Type:        private
   * Arguments:   the path
   * Returns:     true if succeed
   **********************************************************************/ 

  function set_output_path($o) {
    $this->output_path = str_replace("//", "/", $o);
    if (!$this->output_path=="") {
      if (!file_exists($this->output_path)) {
	$oldumask = umask(0);
	mkdir($this->output_path, 0777);
	umask($oldumask);
      }
    }
    return true;
  }

  /**********************************************************************
   * nb_format($val, $fl_graph)
   * 
   * Descr:	format number string using k, M or G suffix
   * Type:	private
   * Arguments: the value to be formated
   *		a flag set if the string must be used inside a graph
   * Returns:   the formated string
   **********************************************************************/ 

  function nb_format($val, $fl_graph = true) {
    $sufx = "";

    if ($val == 0) return $val;

    if ($fl_graph) {
      $esc = "\ ";
    } else {
      $esc = "&nbsp;";
    }

    if ($val > 1000000000) {
      $val /= 1000000000;
      $sufx = $esc."G";
    } elseif ($val > 1000000) {
      $val /= 1000000;
      $sufx = $esc."M";
    } elseif ($val > 1000) {
      $val /= 1000;
      $sufx = $esc."k";
    } else {
      $sufx = $esc;
    }

    if (intval($val) == $val) {
      return sprintf ("%.0f%s",$val,$sufx);
    } else {
      return sprintf ("%.1f%s",$val,$sufx);
    }
  }

  /**********************************************************************
   * get_bandwidth()
   * 
   * Descr: build formated bandwidth string
   * Type:        public
   * Arguments:   
   * Returns:     the string if succeed
   **********************************************************************/ 

  function get_bandwidth () {
    if (($this->max == 0) || ($this->max_str1 == "")) {
      // --- no max
      $bandwidth_str = "-";
    } else {
      $bandwidth_str = $this->nb_format($this->max, false).$this->leg_tab;
    }
    return $bandwidth_str;
  }

  /**********************************************************************
   * get_lastupdate()
   * 
   * Descr: build formated lastupdate string from RRDTool
   * Type:        public
   * Arguments:   
   * Returns:     the string if succeed
   **********************************************************************/ 

  function get_lastupdate () {
    $output = exec($this->rrdpath." last ".$this->file);
    $lastupdate_str = strftime("%d/%m/%Y %H:%M:%S", $output);
    return $lastupdate_str;
  }

}
?>
