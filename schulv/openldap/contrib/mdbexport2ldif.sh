#!/bin/bash

test -d csv2ldif || { echo "Directory csv2ldif not found..."; exit 1; }

inputdir=$1
shift
outputdir=$1
shift

test -z "$inputdir" -o -z "$outputdir" && { echo "Usage: $0 <inputdir> <outputdir>"; exit 1; }

test -d "$inputdir" || { echo "Source directory $inputdir does not exist..."; exit 1; }
test -d "$outputdir" || { echo "Target directory $outputdir does not exist..."; exit 1; }

MAP="
Lehramt:lehramt.pl
Lehrbef:lehrbef.pl
"


for m in $MAP; do
	fi=$(echo $m | cut -d: -f1)
	sc=$(echo $m | cut -d: -f2)
	fo="${fi}.ldif"

	if  test ! -r "$inputdir/$fi"; then
		echo "   file $inputdir/$fi not found"
		continue
	fi

	if test ! -x "csv2ldif/$sc"; then
		echo "   cannot execute csv2ldif/$sc"
		continue
	fi

	if test -e "$outputdir/$fo"; then
		echo "   file $outputdir/$fo already exists. trying to overwrite"
	fi

	if  test -e "$outputdir/$fo" -a ! -w "$outputdir/$fo"; then
		echo "   cannot write to file $outputdir/$fo"
		continue
	fi

	echo " converting $fi to $outputdir/$fo"
	"csv2ldif/$sc" "$inputdir/$fi" > "$outputdir/$fo"

done

