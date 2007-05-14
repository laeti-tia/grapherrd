#!/usr/bin/perl

my $files_path = "/var/www/rrd/inn/";
my $art_in_suf = "~articles-incoming.rrd";
my $art_out_suf = "~articles-outgoing.rrd";
my $bandwidth_suf = ".rrd";
my $host = $ARGV[0];

use RRDs;

chdir $files_path || die "ERROR while chdir, please specify a correct rrd directory";

my ($art_null, $art_in) = get_data ($host.$art_in_suf);
my ($art_null, $art_out) = get_data ($host.$art_out_suf);
my ($bytes_out, $bytes_in) = get_data ($host.$bandwidth_suf);

if ($art_in != 0) {
  $yield_art = $art_out/$art_in*100
} else {
  die "ERROR art_in is null";
}

if ($bytes_in != 0) {
  $yield_bytes = $bytes_out/$bytes_in*100
} else {
  die "ERROR bytes_in is null";
}

printf "%d\n%d\n0\n%s\n", $yield_art, $yield_bytes, $host;


sub get_data ($) {
  my $t=time;
  my ($start,$step,$names,$data) = RRDs::fetch(
					       $_[0],
					       "AVERAGE",
					       "--start", "now-300"
					      );

  my $ERR=RRDs::error;
  die "ERROR while fetching from $file : $ERR\n" if $ERR;

  if ($t - $start < 900) {
    $line = @$data[0];
    ($val1, $val2) = @$line;
    #		printf "%12d , %12d\n", $in, $out;
  } else {
    print stderr "ERROR $file too old ($start now $t)\n";
    ($val1, $val2) = (-1, -1);
  }
return ($val1, $val2);
}
