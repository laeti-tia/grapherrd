#!/usr/bin/perl
#
# This script prints out statistics about messages handled by postfix.
# The statistics are collected by the update-mailstats.pl daemon
# running in the background and stored in a Berkeley DB file
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


use DB_File;

# The various statistics available
my @counter_list = (
	'RECEIVED:local',
	'RECEIVED:smtp',
	'SENT:local',
	'SENT:virtual',
	'SENT:smtp',
	'BOUNCED:local',
	'BOUNCED:virtual',
	'BOUNCED:smtp',
	'BOUNCED:none',
	'REJECTED:local',
	'REJECTED:virtual',
	'REJECTED:smtp',
	'REJECTED:none',
	'PASSED:amavis',
	'INFECTED:amavis'
	);

# The Berkeley DB file
my $stats_file = '/tmp/stats.db' ;

tie(%foo, "DB_File", "$stats_file", O_RDONLY, 0666, $DB_HASH) || die ("Cannot open $stats_file");

if ($ARGV[0] eq "all") {
    foreach my $counter (@counter_list) {
	print (defined($foo{$counter}) ? $foo{$counter} : "0");
	print "\n";
    }
} elsif ($foo{$ARGV[0]}) {
    print "$foo{$ARGV[0]}\n";
} else {
    print "0\n";
}

untie %foo;
