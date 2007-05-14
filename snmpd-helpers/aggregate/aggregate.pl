#!/usr/bin/perl
#####
# This script aggregate multiple RRDtool data file in a single one,
# making the some of all data sources.
# Obviously, the different RRD file should have the same definition.
#
# Only RRD file ending in .rrd will be considered and RRD files containing
# the keyword 'total' will be ignored.  This makes possible to store the
# resulting aggregated file in the same directory.  This script is supposed
# to be run on the same host as the MRG collecting daemon.
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

use RRDs;

# Set this dir to where the RRD file being aggregated are located
chdir "/dir/with/rrdfile/to/aggregate/" || die;
opendir D, ".";
my $t=time;
my ($a1, $a2);
while($file=readdir(D)) {
	next unless ($file =~ /.rrd$/);
	next if ($file =~ /total/);
	next if ($file =~ /\~/);

#	print "$file, $t ";
	my ($start,$step,$names,$data) = RRDs::fetch(
		$file,
		"AVERAGE",
		"--start", "now-300"
		);

	my $ERR=RRDs::error;
	die "ERROR while fetching from $file : $ERR\n" if $ERR;

	if ($t - $start < 900) {
		$line = @$data[0];
		($in, $out) = @$line;
#		printf "%12d , %12d\n", $in, $out;
	} else {
		print stderr "$file too old ($start now $t)\n";
		next;
	}

	$a1+=$in;
	$a2+=$out;
}
closedir(D);
printf "%d\n%d\n0\naggregated\n", $a1, $a2;

