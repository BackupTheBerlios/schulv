#!/usr/bin/perl -w
use strict;
use Data::Dumper;

use constant FIRSTLINE => "Lehrbef�higung,Klartext,SchulForm\n";

my $ifn = shift || &usage();

open(IF, "<$ifn") || die "Failed to open input file\n";

my $line = <IF>;
die "Inputfile incorrect..." if ($line ne FIRSTLINE);

while($line=<IF>) {
	next unless ($line =~ /^\w+,/);
	chomp $line;

	$line =~ s/�/oe/gi;
	$line =~ s/�/ae/gi;
	$line =~ s/�/ue/gi;
	$line =~ s/�/sz/gi;
	$line =~ s/�/Oe/gi;
	$line =~ s/�/Ae/gi;
	$line =~ s/�/Ue/gi;


	my ($k, $desc) = (-1, "");
	($k, $desc) = $line =~ /^([^,]+),"([^"]*)"/;

	print <<EOF;
dn: kuerzel=$k,ou=lehrbefaehigungen,ou=verwaltung,o=schule.edeal.de
objectClass: top
objectClass: schulvLehrbefaehigung
kuerzel: $k
description: $desc
EOF

	print "\n";
}
close(IF);



sub usage {
	print "Usage: lehramt.pl <Lehramt-input>\n";
	exit(1);
}

