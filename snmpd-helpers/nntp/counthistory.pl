#!/usr/bin/perl
#####
# This script counts the number of available articles in the history file of INN.
# The first argument passed is the file to open
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

$h=$n=0;
while(<>) {
	@l=split;
	$f=$#l;
#	print "$f $_";
	$n++;
	$h++ if ($f>1);
}
print "$h $n\n";

