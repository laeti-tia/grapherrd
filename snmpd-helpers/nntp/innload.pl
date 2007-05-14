#!/usr/bin/perl
######
# This script calculates some INN load stats
# All the information is extracted from news.notice file
#
# Location of the news.notice file should be configured
#
# You can either ask for
# -a		article writing load
# -h		history writing load
# -o		overview writing load
# (nothing)	inn cpu load
#
# Copyright Antoine Delvaux 2002-2007, 2001 and before Marc Roger
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
# 
#

use Getopt::Long;

GetOptions("-a","-h","-o");

open(N, '/usr/local/news/log/news.notice');
while(<N>) {
	next unless (/innd:.* ME time/);
	s/\s+/ /g;
	if (/^(\S+)\s+(\d+)\s+(\d+):(\d+):(\d+) .* ME time (\d+) hishave (\d+)\(.* hiswrite (\d+)\(.* hissync (\d+)\(.* idle (\d+)\(.* artwrite (\d+)\((\d+).*overv (\d+)\((\d+)/) {

		($month,$day,$hour,$min,$sec,$time,$hishave,$hiswrite,$hissync,$idle,$artwrite,$nart,$overv,$noverv)=($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14);

		if ($time > 1) {
			$innload= ($time-$idle)/$time;
			if ($nart > 0) {
				$writetime=$artwrite/$nart/1000;
			}
			if ($noverv > 0) {
				$overvtime=$overv/$noverv/1000;
			}
			if ($hiswrite > 0) {
				$historytime=$hiswrite;
			}
		} else {
			$r=0;
			$writetime=0;
			$perltime=0;
		}
	}
}

if ($opt_a) {
	printf "%f\n", $writetime;
} elsif ($opt_h) {
	printf "%f\n", $historytime;
} elsif ($opt_o) {
	printf "%f\n", $overvtime;
} else {
	printf "%f\n", $innload;
}

