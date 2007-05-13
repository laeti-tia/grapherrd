#!/usr/bin/perl
######
# This script calculates some apache live stats
# All the information is extracted from the server-status page of
# the running apache webserver.  So apache must be configured to use
# mod_status and this page should be available from the localhost
# through the use of the 'apachectl status' command.
#
# Script could easily be adapted to access remotely to this page though.
#
# $APACHECTL should be configured to suit apache installation
#
# You can either ask for (first arg)
# - requests-sec	number of requests per second
# - bytes-sec		number of bytes per second
# - bytes-req		number of bytes per request
# - cpuload		CPU load
# - requests		number of concurrent requests
# - idle		number of idle apache processes
# - all			output of all values, one per line
#
# Copyright Antoine Delvaux 2002-2007
#
#  * This program is free software; you can redistribute it and/or
#  * modify it under the terms of the GNU General Public License
#  * as published by the Free Software Foundation; either version 2
#  * of the License, or (at your option) any later version.
#  *
#  * This program is distributed in the hope that it will be useful,
#  * but WITHOUT ANY WARRANTY; without even the implied warranty of
#  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  * GNU General Public License for more details.
#  *
#  * You should have received a copy of the GNU General Public License
#  * along with this program; if not, write to the Free Software
#  * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA,
#  * or go to http://www.gnu.org/copyleft/gpl.html
#  


my $APACHECTL = '/usr/sbin/apachectl';
my $APACHESTATUS = $APACHECTL.' status';
my @val, $out, $outall;

if (-x $APACHECTL) {

    open(STATUS_OUT, "$APACHESTATUS|");

    while (<STATUS_OUT>) {

	if (($ARGV[0] eq "cpuload" || $ARGV[0] eq "all") && /CPU Usage/) {
		# grep the CPU load value
		@val = split (' ');
		if ($val[7]) {
			$out = $val[7];
		} else {
			# in case the % of CPU load cannot be computed because very low
			$out = 0;
		}
		$out =~ s/%//;
		$out =~ s/^\./0\./;
		$outall = $out;
	}

	if (($ARGV[0] eq "requests-sec" || $ARGV[0] eq "all") && /requests\/sec/) {
		# grep the number of requests per second
		@val = split (' ');
		$out = $val[0];
		$out =~ s/^\./0\./;
		$outall .= "\n".$out;
	}

	if (($ARGV[0] eq "bytes-sec" || $ARGV[0] eq "all") && /B\/second/) {
		# grep the number of bytes transmited per second
		@val = split (' ');
		if ($val[4] eq "kB/second") {
		    $out = $val[3] * 1000;
		} elsif ($val[4] eq "MB/second") {
		    $out = $val[3] * 1000000;
		} else {
		    $out = $val[3];
		}
		$out =~ s/^\./0\./;
		$outall .= "\n".$out;
	}

	if (($ARGV[0] eq "bytes-req" || $ARGV[0] eq "all") && /B\/request/) {
		# grep the number of bytes transmited per request
		@val = split (' ');
		if ($val[7] eq "kB/request") {
		    $out = $val[6] * 1000;
		} elsif ($val[7] eq "MB/request") {
		    $out = $val[6] * 1000000;
		} else {
		    $out = $val[6];
		}
		$out =~ s/^\./0\./;
		$outall .= "\n".$out;
	}

	if (($ARGV[0] eq "requests" || $ARGV[0] eq "all") && /requests currently/) {
		# grep the number of concurent requests
		@val = split (' ');
		$out = $val[0];
		$out =~ s/^\./0\./;
		$outall .= "\n".$out;
	}

	if (($ARGV[0] eq "idle" || $ARGV[0] eq "all") && /requests currently/) {
		# grep the number of concurent requests
		@val = split (' ');
		$out = $val[5];
		$out =~ s/^\./0\./;
		$outall .= "\n".$out;
	}
    }

    close(STATUS_OUT);

    if ($out && $ARGV[0] ne "all" ) {
        print $out."\n";
    } elsif ($outall) {
        print $outall."\n";
    } else {
	print "No value found in the output of apachectl !\n";
	print "Usage: $0 cpuload|requests-sec|bytes-sec|bytes-req|requests|idle|all\n";
    }

} else {
    print "apachectl command not found !\n";
}

