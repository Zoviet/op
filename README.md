# FEDERAL CLIMATE COMPLEX GLOBAL SURFACE SUMMARY OF DAY DATA FILES (.op) PARSER 

***eSignal OptionsPlus (.op) weather file parser for NCDC data***

Global surface summary of day product produced by the National Climatic Data Center (NCDC) in Asheville, NC.  

The input data used in building these daily summaries are the Integrated Surface Data (ISD), which includes global data obtained from the USAF Climatology Center, located in the Federal Climate Complex with NCDC. 

The data summaries provided here are based on data exchanged under the World Meteorological Organization (WMO) World Weather Watch Program according to WMO Resolution 40 (Cg-XII).  This allows WMO member countries to place restrictions on the use or re-export of their data for commercial purposes outside of the receiving country. 

However, for non-U.S. locations' data, the data or any derived product shall not be provided to other users or be used for the re-export of commercial services. 

The data are available via: 

FTP -- [ftp://ftp.ncdc.noaa.gov/pub/data/gsod](ftp://ftp.ncdc.noaa.gov/pub/data/gsod) 

KML file with weather's stations locations - [http://gis.ncdc.noaa.gov/kml/gsod.kmz](http://gis.ncdc.noaa.gov/kml/gsod.kmz)

You can open them with Google Earth and other GIS software supported KMZ format or use online tool: [http://kmlviewer.nsspot.net](http://kmlviewer.nsspot.net)

## FTP format

Each weather station from the kml file has an ID in the format XXXXXXX99999. The directory structure of the ftp server has the format /[year]/XXXXXXX-99999-[year].op.gz, where XXXXXXX is the ID of the corresponding station. See. parser.sh for an example of parsing data using wget.

Each qzip qzip archive contains an eSignal OptionsPlus (.op) file. 

## OP dataset

The daily elements included in the dataset (as available from each
station) are:

- Mean temperature (.1 Fahrenheit)
- Mean dew point (.1 Fahrenheit)
- Mean sea level pressure (.1 mb)
- Mean station pressure (.1 mb)
- Mean visibility (.1 miles)
- Mean wind speed (.1 knots)
- Maximum sustained wind speed (.1 knots)
- Maximum wind gust (.1 knots)
- Maximum temperature (.1 Fahrenheit)
- Minimum temperature (.1 Fahrenheit)
- Precipitation amount (.01 inches)
- Snow depth (.1 inches)
- Indicator for occurrence of:  Fog
                              Rain or Drizzle
                              Snow or Ice Pellets
                              Hail
                              Thunder
                              Tornado/Funnel Cloud
                              
## Installation

```
composer require zoviet/op

```

## Usage

```php
	
//Parsing with format output in USCS / USC

$parser = new \op\parser();
if ($handle = opendir('data')) {
    while (false !== ($entry = readdir($handle))) {
        if (strpos($entry,'.op')!==false) {
            $parser->parse(__DIR__.'/data/'.$entry);
            //var_dump($parser);
        }
    }
    closedir($handle);
}

var_dump($parser->data);

//Examples:

//average temp for 04[day].01[month].2007 for station 277850

$temp = $parser->data[277850][2007][1][4]->temp->average->data;

echo $temp;

//inaccuracy of wind speed for 08[day].02[month].2007 for station 277850

$in =  $parser->data[277850][2007][2][8]->wind->speed->inaccuracy;

//Parsing data (short format) in metric system (SI)

$parser1 = new \op\parser(true);

$data = file_get_contents(__DIR__.'/data/277850-99999-2007.op');

var_dump($parser1->parse($data));

//Parsing dataset in original format (without formatting - false)

$parser2 = new \op\parser(false,false);

var_dump($parser2->parse($data)->data); //or same:  var_dump($parser2->parse($data));


```

## Metric system

Default metric system is USCS / USC

For use SI: 

```
$parser = new \op\parser(true);

```
when: Fahrenheit to Celsius, miles to km, knots to m/s 

## Output formats

### 1. custom object standart (format = true) - default:

```
[station ID][year][month][day]->object

new \op\parser(); // or

new \op\parser(false,true);

```	

#### Fields

- ***->temp*** Temperature dataset 

- ***->pressure*** Pressure dataset

- ***->wind*** Wind dataset

- ***->precipitation*** Precipitation and anomal dataset

All sets cont. inaccuracy - statistic inaccuracy of the value +_ 

### 2. object standart (format = false):

```
[station ID][year][month][day]->object

new \op\parser(false,false);

```
#### Fields

- ***TEMP (float)*** Mean temperature for the day in degrees Fahrenheit to tenths;

- ***CountTEMP*** Number of observations used in calculating mean temperature;

- ***DEWP (float)*** Mean dew point for the day in degrees Fahrenheit to tenths;                         

- ***CountDEWP*** Number of observations used in calculating mean dew point;

- ***SLP (float)*** Mean sea level pressure for the day in millibars to tenths;

- ***CountSLP*** Number of observations used in calculating mean sea level pressure;

- ***STP (float)*** Mean station pressure for the day in millibars to tenths;

- ***CountSTP*** Number of observations used in calculating mean station pressure;

- ***VISIB (float)*** Mean visibility for the day in miles to tenths;

- ***CountVISIB*** Number of observations used in calculating mean visibility;

- ***WDSP (float)*** Mean wind speed for the day in knots to tenths;                        

- ***CountWDSP*** Number of observations used in calculating mean wind speed;

- ***MXSPD (float)*** Maximum sustained wind speed reported for the day in knots to tenths;

- ***GUST (float)*** Maximum wind gust reported for the day in knots to tenths;

- ***MAX (float)*** Maximum temperature reported during the day in Fahrenheit to tenths--time of max temp report varies by country and region, so this will sometimes not be the max for the calendar day;

- ***FlagMAX*** Blank indicates max temp was taken from the explicit max temp report and not from the hourly data.  * indicates max temp was derived from the hourly data (i.e., highest hourly or synoptic-reported temperature);

- ***MIN (float)*** Minimum temperature reported during the day in Fahrenheit to tenths--time of min temp report varies by country and region, so this will sometimes not be the min for the calendar day;                        

- ***FlagMIN*** Blank indicates min temp was taken from the explicit min temp report and not from the hourly data.  * indicates min temp was derived from the hourly data (i.e., lowest hourly or synoptic-reported temperature);

- ***PRCP (float)*** Total precipitation (rain and/or melted snow) reported during the day in inches and hundredths; will usually not end  with the midnight observation--i.e., may include latter part of previous day..00 indicates no measurable precipitation (includes a trace).  Many stations do not report 0 on days with no precipitation--therefore Also, for example, a station may only report a 6-hour amount for the period during which rain fell;

- ***FlagPRCP (string)*** A = 1 report of 6-hour precipitation amount. B = Summation of 2 reports of 6-hour precipitation amount. C = Summation of 3 reports of 6-hour precipitation amount. D = Summation of 4 reports of 6-hour precipitation amount. E = 1 report of 12-hour precipitation amount.    F = Summation of 2 reports of 12-hour precipitation amount. G = 1 report of 24-hour precipitation amount.  H = Station reported 0 as the amount for the day (eg, from 6-hour reports), but also reported at least one  occurrence of precipitation in hourly  observations--this could indicate a       trace occurred, but should be considered as incomplete data for the day. I = Station did not report any precip data for the day and did not report any occurrences of precipitation in its hourly observations--its still possible that precip occurred but was not reported;

- ***SNDP (float)*** Snow depth in inches to tenths--last report for the day if reported more than once.  Missing = 999.9 Note:  Most stations do not report 0 on days with no snow on the ground--therefore, 999.9 will often appear on these days;

- ***FRSHTT (string)*** Indicators (1 = yes, 0 = no/not reported) for the occurrence during the day of: Fog (F - 1st digit). Rain or Drizzle (R - 2nd digit). Snow or Ice Pellets (S - 3rd digit). Hail (H - 4th digit). Thunder (T - 5th digit).Tornado or Funnel Cloud (T - 6th digit);


