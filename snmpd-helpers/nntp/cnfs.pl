#!/usr/bin/perl
######
# This script calculates some CNFS buffer stats of INN
#
# You can either ask for (first arg)
# - the retention time
# - the latest newest article time
# - the buffer usage
# with the second arg you can specify the buffer class to report
# (if no class is specified, the mean usage of all class is computed)
#
# Copyright Antoine Delvaux 2003-2007
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


my $CNFSSTAT = '/usr/local/news/bin/cnfsstat';
my $CNFSARGS = '';

# The second arg is the name of the buffer class to look at

if ($ARGV[1]) {
    $CNFSARGS = $CNFSARGS.' -c '.$ARGV[1];
}

if ($ARGV[0] eq "retention-time") {

    my $cnfs_total = 0;
    my $time_total = 0;
    my $time = 0;
    $CNFSARGS = $CNFSARGS.' -a';
    open(CNFSSTAT_OUT, "$CNFSSTAT $CNFSARGS|");
    while (<CNFSSTAT_OUT>) {
    	if (/Oldest/) {
	    # Loop on the lines with 'Oldest' and grep the retention time of this buffer
	    $cnfs_total++;
	    @val = split (',');
	    # extract the number of days
	    @val1 = split (' ',$val[1]);
	    @val2 = split (' ',$val[2]);
	    @val2 = split (':',$val2[0]);
	    $time_total += $val1[0]*24+$val2[0]+$val2[1]/60+$val2[2]/3600;
	}
    }
    close(CNFSSTAT_OUT);
    if ($cnfs_total != 0) {
	$time = $time_total / $cnfs_total;
    } else {
	$time = 0;
    }
    print $time."\n";

} elsif ($ARGV[0] eq "newest") {

    my $time_best = -1;
    my $time = 0;
    open(CNFSSTAT_OUT, "$CNFSSTAT $CNFSARGS|");
    while (<CNFSSTAT_OUT>) {
    	if (/Newest/) {
	    # Loop on the lines with 'Newest' and grep the time of the newest article in this buffer
	    $cnfs_total++;
	    @val = split (',');
	    # extract the number of days
	    @val1 = split (' ',$val[1]);
	    @val2 = split (' ',$val[2]);
	    @val2 = split (':',$val2[0]);
	    $time = $val1[0]*24+$val2[0]+$val2[1]/60+$val2[2]/3600;
	    if ($time < $time_best || $time_best eq -1) {
	    	$time_best = $time;
	    }
	}
    }
    close(CNFSSTAT_OUT);
    print $time_best."\n";

} elsif ($ARGV[0] eq "used-space") {

    # Ask for the total size of the buffer, ask for the current position
    # and for the number of cycles
    # If the number of cycles if less than 1, return current position
    # else return the total size (rollover has already occured, buffer is full)
    # We assume total cnfs buffer size is in GBytes
    # and we return the value in MBytes

    #$CNFSSTAT $CNFSARGS | grep Buffer | tail -1 | awk '{if ($9 >= 1) used=$4*1000; else if ($8 == "MBytes") used=$7; else used=$7*1000; print used}'
    my $cnfs_total = 0;
    my $space_total = 0;
    my $space = 0;
    $CNFSARGS = $CNFSARGS.' -a';
    open(CNFSSTAT_OUT, "$CNFSSTAT $CNFSARGS|");
    while (<CNFSSTAT_OUT>) {
    	if (/Buffer/) {
	    # Loop on the lines with 'Buffer' and grep the writing position in this buffer
	    $cnfs_total++;
	    @val = split (',');
	    @val1 = split (':',$val[1]);
	    @val1 = split (' ',$val1[1]);
	    @val2 = split (':',$val[2]);
	    @val2 = split (' ',$val2[1]);
	    if ($val2[2] < 1 && $val2[2] > 0) {
	    	# We have less than one cycle
	    	if ($val2[1] eq "GBytes") {
		    $space = $val2[0]*1000;
		} elsif ($val2[1] eq "MBytes") {
		    $space = $val2[0];
		} else {
		    $space = 0;
		}
	    } elsif ($val2[2] >= 1) {
	    	# We have rolled over
	    	if ($val1[1] eq "GBytes") {
		    $space = $val1[0]*1000;
		} elsif ($val1[1] eq "MBytes") {
		    $space = $val1[0];
		} else {
		    $space = 0;
		}
	    } else {
		$space = 0;
	    }
	    $space_total += $space;
	}
    }
    close(CNFSSTAT_OUT);
    print $space_total."\n";

} else {
    print "Usage: $0 retention-time|used-space [class]\n"
}
