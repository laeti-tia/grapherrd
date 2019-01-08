This software is now unmaintained, use at your own risk.

GrapheRRD
=========
GrapheRRD is a PHP web frontend to MRTG  and  RRD  data  files.   It  draws
custom graphics from MRTG data and  allows  easy  navigation  amongst  data
sources.   Graphs  colors,  sizes  and   time   scale   are   customizable.

Requirements
------------
[MRTG][mrtg], [RRD][rrd] and [PHP][php].

Installation
------------
Installation should be  quite  straightforward.   The  grapherrd  directory
contains all the needed PHP scripts.  You should copy it  to  any  of  your
HTTP PHP enabled server.

### snmpd-helpers
The `snmpd-helpers` directory contains bash scripts to be run from snmpd to
collect services statistics and make them available to MRTG.   Scripts  are
provided for the following services:
- DB (mysql)
- DNS (bind and NSD)
- FTP (apache and proftpd)
- HTTP (apache)
- NNTP (INN)
- SMTP (postfix)
- and various other.

See also the provided `examples/snmpd.conf` file to see how  to  use  those
scripts.

This directory is actually a git submodule,  the  original  is  located  at
https://github.com/tonin/snmpd-helpers

Configuration
-------------
Configuration is done in  3  different  places:  MRTG,  `graperrd.cfg`  and
`graph.cfg`

### MRTG
Regular MRTG configuration files should be set so to use the  RRD  backend.
If you want multiple graphs grouped on a single HTML page, for  example  to
display all the interfaces of a single routers or all the system parameters
of a host, you should  use  'subtargets'.   Subtargets  are  defined  by  a
convention to use inside MRTG configuration files. When you add a `~suffix`
to a target name, you make it a subtarget.

For these subtargets you can define SubTitles.  The SubTitles will be  used
by grapherrd and added on top of the graphs.  These SubTitles  are  defined
by setting the SUBTITLE environment var inside the MRTG config file.

The main target should always be defined first in  the  MRTG  config  file.
Therefore a main target should always be a network interface, or  at  least
it  will  be  graphed  as  such.   If  no  main  target  is  defined,   the
characteristics of the first subtarget are used as if it where the main.

See the provided `mrtg/mrtg.conf` file for how to use these settings.

MRTG configuration files and RRD data files  should  be  readable  by  your
web servers or PHP engine.

### grapherrd.cfg
The grapherrd.cfg file contains all the settings you can  change  to  adapt
the output and rendering of the GrapheRRD web pages.  You should also  tell
GrapheRRD where to look for MRTG configuration files, RRD  data  files  and
where to output graphic files produced.  See comments enclosed in the  file
for more information.

### graph.cfg
It's the subtarget suffix which tells GrapheRRD which graph  definition  to
use. These various definitions are inside `graph.cfg`

This file  contains  all  the  graphs  parameters:  data  sources,  colors,
legends.  There is already quite a lot of different  graph  types  provided
and adding new ones should be quite easy.  See  comments  enclosed  in  the
file.

Acknowledgements
----------------
Thanks to [BELNET][belnet] for having made this work  opensource software.

Copyright and License
---------------------
© 2001-2014 — Antoine Delvaux — All rights reserved.

See enclosed [LICENSE][license] file.

[belnet]: http://www.belnet.be
[license]: https://github.com/tonin/grapherrd/blob/master/LICENSE
[mrtg]: http://mrtg.org
[rrd]: http://rrdtool.org
[php]: http://php.net


/* vim: set expandtab textwidth=75: */
