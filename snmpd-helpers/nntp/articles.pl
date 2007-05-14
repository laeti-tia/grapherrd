#!/usr/bin/perl
#####
# This script counts the number of articles comming to INN.
# The first argument passed is the type of articles to count
# it can be either :
#	offered
#	accepted
# The second argument passed is the type of connections to count
#	incoming
#	outgoing
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

# parameters to be configured, the status files of INN :
@inn_html_status_file = (
		"/www/htdocs/stats/news/inn_status.html"
		);
@innfeed_html_status_file = (
		"/newslib/log/innfeed1.status",
		"/newslib/log/innfeed2.status",
		"/newslib/log/innfeed3.status"
		);

$type_art = $ARGV[0];
$_ = $ARGV[1];

$file = 0;
if (/incoming/) { @file = @inn_html_status_file; }
if (/outgoing/) { @file = @innfeed_html_status_file; }

$total = 0;

foreach (@file)  {
	if(open(A, $_)) {
		while(<A>) {
			if (/$type_art: (\d+)\s*/) {
				$articles=$1;
				last;
			}
		}
		close(A);
		$total += $articles;
		$articles = "";
	}
}
print "$total\n";
