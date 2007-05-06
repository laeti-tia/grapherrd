#!/usr/bin/perl
#####
# This script get some statistics about the running named or nsd process.
# See enclosed help (--help for details about the stats available)
# 
# This script should work fine with BIND 8, BIND 9 and NSD as long as it
# can access ndc, rndc or send a SIGUSR1 to NSD.  NSD output BIND 8 style
# statistics.  BIND 8 direct statistics are a bit over-evaluated compared
# to the BIND 9 ones, see remarks below.
#
# For information on the description of the stat output of BIND 9:
# bind-src-dir/doc/arm/Bv9ARM.html section 6.2.14.15
# and note that 'recursion' count is a subcount from the other
# counters so it shouldn't be take into the total account.
# See: http://marc.theaimsgroup.com/?l=bind9-users&m=103238038914431
#
# History:
#	11/05/2005	1.4	Antoine Delvaux
#		- added all option to get all result in one run
#	21/10/2004	1.3	Antoine Delvaux
#		- added compatibility with NSD
#	26/01/2004	1.2	Antoine Delvaux
#		- added autodetection of named version
#	19/01/2004	1.1	Antoine Delvaux
#		- added BIND 8 support
#       12/01/2004	1.0	Antoine Delvaux
#		- first version
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



use strict;
use Getopt::Long;

#
## Under this line, nothing needs to be changed
#

sub usage
{
	print "usage: named-stats.pl [*options*] stats-type\n\n";
	print "  -h, --help         display this help and exit\n";
	print "  -d, --debug        display debugging information\n";
	print "  -f, --statfile f   where to find statistic output file\n";
	print "  -n, --ndc n        where to find ndc or rndc\n";
	print "      --nsd          we are monitoring NSD\n";
	print "\n";
	print "  stats-type being one of the followings:\n";
	print "      total          total DNS answers\n";
	print "      success        successfull answers\n";
	print "      direct         direct answers (without recursion), sum of authoritative and cached answers\n";
	print "      recursive      recursive answers\n";
	print "      failure        failed answers\n";
	print "      running        running xfers\n";
	print "      deferred       deferred xfers\n";
	print "      all            all of the above values\n";

	exit;
}

#
## Option parsing
#
my %opt = ();
Getopt::Long::Configure('no_ignore_case');
GetOptions(\%opt, 'help|h', 'debug|d', 'statfile|f=s', 'ndc|n=s', 'nsd'
	) or exit(1);
usage if $opt{help};

#
## Check existence and version of NDC (RNDC)
## or a running instance of NSD
#
my $named_version;
my $nsd_pid = 0;
my $ndc = defined $opt{ndc} ? $opt{ndc} : 'ndc';
my $line = "";

# first check for a running NSD process
open (PS, "ps -e | grep nsd | sort |");
$line = <PS>;
print "First line output of ps -e is\n$line\n" if $opt{debug};
if ($line =~ s/^\s*([0-9]+) .* nsd$/$1/ ) {
	$nsd_pid = $line;
}
close (PS);

if ($nsd_pid != 0 || defined $opt{nsd}) {
	if ($nsd_pid == 0) {
		print "No running instance of NSD found although the --nsd option was given.\n";
		print "Please be sure NSD is running when using this option.\n\n";
		usage;
		exit(1);
	} else {
	print "PID of nsd is $nsd_pid\n" if $opt{debug};
	}
} else {
	while ( !defined $named_version ) {
		open (NDC, "$ndc status|");
		$line = <NDC>;
		print "First line output of $ndc is\n$line\n" if $opt{debug};
		if ($line =~ /^named/) {
			$named_version = "8";
		} elsif ($line =~ /^number/) {
			$named_version = "9";
		} elsif ($ndc eq "rndc") {
			print "No valid ndc|rndc executable found.\n";
			print "Please use the -n option to specify it.\n\n";
			usage;
			exit(1);
		} else {
			$ndc = "rndc";
		}
		close (NDC);
	}
}


my $action = $ARGV[0];
my $total_answers = 0;
my $result = 0;
my $all_result = "";
my %stats;
$_ = $action;

if (!(/^total/ || /^success/ || /^direct/ || /^recursive/ || /^failure/ || /^running/ || /^deferred/ || /^all/)) {
	usage;
	exit(1);
}

if (/^total/ || /^success/ || /^direct/ || /^recursive/ || /^failure/ || /^all/) {

	my $type = "";
	my $stat_file;
	my @D_STATS;

	#
	## First remove previous stat file, if we run named
	#
	if ( $nsd_pid == 0 ) {
		# default stat file for BIND is set as /tmp/named.stats
		$stat_file = defined $opt{statfile} ? $opt{statfile} : '/tmp/named.stats';
		system("rm -f $stat_file") == 0 
			or die "Couldn't call rm";
	} else {
		# default stat file for NSD is set as /var/log/daemon.log (Debian package standard)
		$stat_file = defined $opt{statfile} ? $opt{statfile} : '/var/log/daemon.log';
		$stat_file = "tail -1000 /var/log/daemon.log | grep nsd | tail -2 |";
	}

	#
	## Then open and parse stat file
	#
	if ($named_version eq "9" ) {
		#
		## Bind v9
		#
		system($ndc." stats") == 0
			or die "Couldn't tell named to dump statistics.\nWas ndc correctly recognised?  Is named running?\n";
		open (STAT_OUT, "head -7 $stat_file|") 
			or die "Couldn't open $stat_file for reading.\nCheck the -f option and read access to this file.\n";
		while ($line = <STAT_OUT>) {
			if ( $line =~ s/^(\w+) (\d+)$/$2/ ) { 
				$type = $1;
				chomp $line;
				chomp $type;
				print "There have been $line $type answers\n" if $opt{debug};
				$total_answers += $line;
				$stats{$type} = $line;
			}
		}
	} elsif ( $nsd_pid > 0 ) {
		#
		## NSD
		#
		# send a SIGUSR1 to NSD so it dumps statistics out
		system("kill -USR1 ".$nsd_pid) == 0
			or die "Error while sending SIGUSR1 to nsd (pid ".$nsd_pid.")\nCheck that this process is actually running and accepting SIGUSR1.\n";
		open (STAT_OUT, "$stat_file") 
			or die "Couldn't open $stat_file for reading.\nCheck the -f option and read access to this file.\n";
		while ($line = <STAT_OUT>) {
			if ( $line =~ s/^.*]: NSTATS \d+ \d+ (\w+=.*)$/$1/ ) {
				my $type = "";
				my $answer = 0;
				my $rr;
				$total_answers = 0;
				print $line."\n" if $opt{debug};
				foreach $rr (split(/\s+/, $line)) {
					($type, $answer) = split(/=/, $rr);
					$total_answers += $answer;
					$stats{$type} = $answer;
				}
			}
			if ( $line =~ s/^.*]: XSTATS .* SAns=(\d+) .* SErr=(\d+) .* SFwdR=(\d+) SFail=(\d+) SFErr=(\d+) .* SNXD=(\d+) .*$/$1 $2 $3 $4 $5 $6/ ) {
				@D_STATS = split(/\s+/,$line);
				print $line."\n" if $opt{debug};
				# success = SAns - SNXD
				$stats{'success'} = $D_STATS[0] - $D_STATS[5];
				# BIND 8 stats doesn't tell about the followings
				$stats{'referral'} = 0;
				$stats{'nxrrset'} = 0;
				# nxdomain = SNXD
				$stats{'nxdomain'} = $D_STATS[5];
				# failure = SErr + SFail + SFErr
				$stats{'failure'} = $D_STATS[1] + $D_STATS[3] + $D_STATS[4];
				# recursion = SFwdR
				$stats{'recursion'} = $D_STATS[2];
			}
		}
	} else {
		#
		## Any other (supposed to be Bind v8)
		#
		system($ndc." -q stats") == 0
			or die "Couldn't tell named to dump statistics.\nWas ndc correctly recognised?  Is named running?\n";
		open (STAT_OUT, "$stat_file") 
			or die "Couldn't open $stat_file for reading.\nCheck the -f option and read access to this file.\n";
		while ($line = <STAT_OUT>) {
			if ( $line =~ s/^(\d+)\s+(\w+) queries$/$1/ ) { 
				$type = $2;
				chomp $line;
				chomp $type;
				print "There have been $line $type queries\n" if $opt{debug};
				$total_answers += $line;
				$stats{$type} = $line;
			} elsif ( $line =~ /^(\s+\d+){29}$/ ) {
				@D_STATS = split(/\s+/,$line);
				print @D_STATS if $opt{debug};
				# success = SAns - SNXD
				$stats{'success'} = $D_STATS[12] - $D_STATS[25];
				# BIND 8 stats doesn't tell about the followings
				$stats{'referral'} = 0;
				$stats{'nxrrset'} = 0;
				# nxdomain = SNXD
				$stats{'nxdomain'} = $D_STATS[25];
				# failure = SErr + SFail + SFErr
				$stats{'failure'} = $D_STATS[15] + $D_STATS[22] + $D_STATS[23];
				# recursion = SFwdR
				$stats{'recursion'} = $D_STATS[21];
			}
		}
	}
	close (STAT_OUT);

	#
	## Print output
	#
	if (/^total/) {
		$result = $total_answers - $stats{'recursion'};
	} elsif (/^success/) {
		# Successful and meaningful answers
		$result = $stats{'success'} + $stats{'referral'} + $stats{'nxrrset'} + $stats{'nxdomain'};
	} elsif (/^direct/) {
		# Answers from authority or from cache
		# with a small imprecision for BIND 8 as 'success' is not defined the same way (see above)
		# -> BIND 8 direct answers are a bit surevaluated compaired to the ones from BIND 9
		$result = $stats{'success'} + $stats{'referral'} + $stats{'nxrrset'} + $stats{'nxdomain'} + $stats{'failure'} - $stats{'recursion'};
	} elsif (/^recursive/) {
		# Answers relayed
		$result = $stats{'recursion'};
	} elsif (/^failure/) {
		# Errored or meaningless answers
		$result = $stats{'failure'};
	}
}

if (/^running/ || /^deferred/ || /^all/) {
	if ( $nsd_pid > 0 ) {
		if ( !/^all/ ) {
		print "These stats are not available for NSD, sorry.\n";
		print "BTW, NSD (<3.x) does not automatically do inbound AXFR.\n";
		exit(1);
		} else {
			#$all_result .= "\n".$stats{'AXFR'}."\n0";
			$all_result .= "\n0\n0";
		}
	} else {
		open (NDC, "$ndc status|")
			or die "Couldn't get $ndc status command results: $?";
		while($line = <NDC>) {
			if ( $line =~ s/^xfers running:\s+(\d+)$/$1/ && !/^deferred/ ) { 
				$result = $line;
				chomp $result;
				print "There is $result running xfers\n" if $opt{debug};
				$all_result = "\n" . $result;
			}
			if ( $line =~ s/^xfers deferred:\s+(\d+)$/$1/ && !/^running/ ) { 
				$result = $line;
				chomp $result;
				print "There is $result deferred xfers\n" if $opt{debug};
				$all_result .= "\n" . $result;
			}
		}
		close (NDC);
	}
}

if (/^all/) {
	# total
	$result = $total_answers - $stats{'recursion'};
	# success
	$result .= "\n" . ($stats{'success'} + $stats{'referral'} + $stats{'nxrrset'} + $stats{'nxdomain'});
	# direct
	$result .= "\n" . ($stats{'success'} + $stats{'referral'} + $stats{'nxrrset'} + $stats{'nxdomain'} + $stats{'failure'} - $stats{'recursion'});
	# recursive
	$result .= "\n" . $stats{'recursion'};
	# failure
	$result .= "\n" . $stats{'failure'};
	# running - deferred
	$result .= $all_result;
}

print $result."\n";

