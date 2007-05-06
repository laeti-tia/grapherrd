#!/usr/bin/perl
#
# This script collect statistics about messages handled by postfix.
# The stats are stored in a Berkeley DB file
#
# Copyright Craig Sanders 1999
# Copyrignt Antoine Delvaux 2002-2007
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
use File::Tail ;
$debug = 0;

$mail_log = '/var/log/mail.log' ;
$stats_file = '/tmp/stats.db' ;

$db = tie(%stats, "DB_File", "$stats_file", O_CREAT|O_RDWR, 0666, $DB_HASH) 
	|| die ("Cannot open $stats_file");

#my $logref=tie(*LOG,"File::Tail",(name=>$mail_log,tail=>-1,debug=>$debug));
my $logref=tie(*LOG,"File::Tail",(name=>$mail_log,debug=>$debug));

while (<LOG>) {
    if (/ postfix\//) {
	if (/status=sent/) {
		# count sent messages
		if (/relay=([^,]+)/o) {
			$relay = $1 ;
			#print "$relay..." ;
		}
		if ($relay !~ /\[/o ) {
			$stats{"SENT:$relay"} += 1;
			#print "$relay\n" ;
		} elsif ($relay !~ /localhost\[127\.0\.0\.1\]/) {
			# count sent smtp messages but don't count relay through localhost (amavis)
			$stats{"SENT:smtp"} +=1 ;
			#print "smtp\n" ;
		}
	} elsif (/status=bounced/) {
		# count bounced sent messages
		if (/relay=([^,]+)/o) {
			$relay = $1 ;
			#print "$relay..." ;
		}
		if ($relay !~ /\[/o && $relay !~ /none/o) {
			$stats{"BOUNCED:$relay"} += 1;
			#print "$relay\n" ;
		} elsif ($relay =~ /localhost/o) {
		# les messages adressésà une mailbox redirigée à partir d'un domaine virtuel sont comptés 2 fois :
		#  - une fois dans REJECT:smtp
		#  - une fois dans BOUNCED:local
			$stats{"BOUNCED:local"} += 1;
			#print "$relay\n" ;
		} else {
			$stats{"BOUNCED:smtp"} +=1 ;
			#print "smtp\n" ;
		}
	} elsif (/postfix\/smtpd\[[0-9]+\]: [[:alnum:]]+: client=/) {
		# count received smtp messages but don't count relay through localhost (amavis)
		if (!/client=localhost\[127\.0\.0\.1\]/) {
			$stats{"RECEIVED:smtp"} += 1;
		}
	} elsif (/postfix\/smtpd\[[0-9]+\]: NOQUEUE: reject:/) {
		# count rejected smtp messages but not if message is only deferred (ex: greylisting), this means 4xy code
		if (!/NOQUEUE: reject: RCPT from [^[:space:]]+(\]|\[[^[:space:]]+\]): 4[0-9][0-9]/) {
			$stats{"REJECTED:smtp"} += 1;
		} elsif (/NOQUEUE: reject: RCPT from [^[:space:]]+(\]|\[[^[:space:]]+\]): 4[0-9][0-9]/) {
			$stats{"DELAYED:smtp"} += 1;
		}
	} elsif (/postfix\/pickup\[[0-9]+\]: [[:alnum:]]+: (sender|uid)=/) {
		# count received local messages
		$stats{"RECEIVED:local"} += 1;
	}
    } elsif (/ amavis\[/) {
  	if (/ Passed, /) {
		$stats{"PASSED:amavis"} += 1;
	} elsif (/ INFECTED /) {
		$stats{"INFECTED:amavis"} += 1;
	} else {
		next;
	}
    } else {
  	next;
    }
    $db->sync;
}
;

untie $logref ;
untie %stats;

