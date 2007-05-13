#!/bin/sh
######
# This script query the mysql deamon about status
# All the information is extracted from the mysqladmin command output
# A special MySQL user is needed to access this command.
#
# You can either ask for (first arg)
# - questions		number of questions asked to the mysql deamon
# - slow		number of slow queries (>10 seconds)
# - connections		number of open connections to MySQL
# - opentables		number of open tables
# - all			output of all values, one per line
#
# Copyright Antoine Delvaux 2002 - 2007
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



PATH=/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin
MYSQLADMIN_OUT=`mysqladmin -u mrtg -pmrtg status`

case "$1" in
    questions)
		# number of questions asked to the mysql deamon
		echo $MYSQLADMIN_OUT | awk '{print $6}'
		;;
		
    slow)
		# number of slow queries (>10 seconds)
		echo $MYSQLADMIN_OUT | awk '{print $9}'
		;;

    connections)
		# number of opened connectinos (or mysql threads)
		mysqladmin -u mrtg -pmrtg status | awk '{print $4}'
		#mysql -u mrtg -pmrtg -N -B -e "show status like 'Connections'" | awk '{print $2}'
		#mysqladmin -u mrtg -pmrtg extended-status | awk '/.*Threads_connected.*/ {print $4}'
		;;

    opentables)
		# number of mysql processes
		echo $MYSQLADMIN_OUT | awk '{print $17}'
		;;

    all)
    		# all data
		echo $MYSQLADMIN_OUT | awk '{print $6}'
		echo $MYSQLADMIN_OUT | awk '{print $9}'
		echo $MYSQLADMIN_OUT | awk '{print $4}'
		echo $MYSQLADMIN_OUT | awk '{print $17}'
		;;
	
    *)
		# default
		echo 'usage : mysqladmin-status questions|slow|connections|opentables'
		;;

esac

exit 0

