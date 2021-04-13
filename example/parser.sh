#!/bin/bash
base='ftp://ftp.ncdc.noaa.gov/pub/data/gsod/';
station1='279621-99999'; #ульяновск
station2='277850-99999'; #восточный
for ((year=1929; year < 2022; year++))
do
url1=$base$year'/'$station1'-'$year'.op.gz';
echo $url1;
url2=$base$year'/'$station2'-'$year'.op.gz';
echo $url2;
wget --directory-prefix='station1' $url1;
wget --directory-prefix='station2' $url2;
done
gunzip station1/*.gz
gunzip station2/*.gz

