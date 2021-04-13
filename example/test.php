<?php 
require '../op/parser.php';

//Parsing with format output in USCS / USC

$parser = new \WMO\parser();
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

$parser1 = new \WMO\parser(true);

$data = file_get_contents(__DIR__.'/data/277850-99999-2007.op');

var_dump($parser1->parse($data));

//Parsing dataset in original format (without formatting - false)

$parser2 = new \WMO\parser(false,false);

var_dump($parser2->parse($data)->data);

?>
