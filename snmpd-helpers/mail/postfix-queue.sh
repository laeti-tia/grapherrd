#! /bin/sh
#####
# This script get some statistics about the current postfix mail queue
# It works with postfix v2
#
# The Postfix queues queried are active, deferred and incoming
# 
# You can either ask for (first arg)
# - requests		number of messages in the queue
# - kbytes		number of kbytes used by the queue
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




case "$1" in
  requests)
    qshape -s active deferred incoming | grep TOTAL | awk '{print $2}'
    ;;
  kbytes)
    du -ks /var/spool/postfix/active/ /var/spool/postfix/deferred/ /var/spool/postfix/incoming/ | awk 'BEGIN {total=0} {total += $1} END {print total}'
    ;;
  all)
    qshape -s active deferred incoming | grep TOTAL | awk '{print $2}'
    du -ks /var/spool/postfix/active/ /var/spool/postfix/deferred/ /var/spool/postfix/incoming/ | awk 'BEGIN {total=0} {total += $1} END {print total}'
    ;;
  *)
    echo "Usage: postfix-queue {requests|kbytes}"
    exit 1
esac

exit 0
