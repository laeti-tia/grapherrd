#!/usr/bin/perl

# mailstats.pl
#
# Copyright Craig Sanders 1999
#
# this script is licensed under the terms of the GNU GPL.

use DB_File;

$|=1;

$stats_file = '/tmp/stats.db' ;

tie(%foo, "DB_File", "$stats_file", O_RDONLY, 0666, $DB_HASH) || die ("Cannot open $stats_file");

foreach (sort keys %foo) {
	print "$_ $foo{$_}\n" ;
} ;
untie %foo;
