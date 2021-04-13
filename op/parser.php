<?php 
 /**
   * eSignal OptionsPlus (.op) weather file parser for NCDC data    
   *
   */
namespace op;

class parser {	

	public static $fields = [
		'STN' =>[1,6, 'Station number (WMO/DATSAV3 number) for the location'],
		'WBAN' =>[8,12, 'WBAN number where applicable--this is the   historical Weather Bureau Air Force Navy number - with WBAN being the acronym'],
		'YEAR' =>[15,18, 'The year'],
		'MODA' =>[19,22, 'The month and day'],
		'TEMP' =>[25,30, 'Mean temperature for the day in degrees Fahrenheit to tenths.  Missing = 9999.9'],
		'CountTEMP' =>[32,33,'Number of observations used in calculating mean temperature'],
		'DEWP' =>[36,41,'Mean dew point for the day in degrees Fahrenheit to tenths.  Missing = 9999.9'],                         
		'CountDEWP' =>[43,44,'Number of observations used in calculating mean dew point'],
		'SLP' => [47,52, 'Mean sea level pressure for the day in millibars to tenths.  Missing = 9999.9'],
		'CountSLP' =>[54,55, 'Number of observations used in calculating mean sea level pressure'],
		'STP' => [58,63, 'Mean station pressure for the day in millibars to tenths.  Missing = 9999.9'],
		'CountSTP' =>[65,66, 'Number of observations used in calculating mean station pressure'],
		'VISIB' => [69,73, 'Mean visibility for the day in miles to tenths.  Missing = 999.9'],                         
		'CountVISIB' => [75,76, 'Number of observations used in calculating mean visibility'],
		'WDSP' =>  [79,83, 'Mean wind speed for the day in knots to tenths.  Missing = 999.9'],                        
		'CountWDSP' => [85,86, 'Number of observations used in calculating mean wind speed'],
		'MXSPD' => [89,93, 'Maximum sustained wind speed reported for the day in knots to tenths. Missing = 999.9'],
		'GUST' => [96,100, 'Maximum wind gust reported for the day in knots to tenths.  Missing = 999.9'],
		'MAX' =>  [103,108, 'Maximum temperature reported during the day in Fahrenheit to tenths--time of max temp report varies by country and region, so this will sometimes not be the max for the calendar day.  Missing = 9999.9'],
		'FlagMAX' => [109,109, 'Blank indicates max temp was taken from the explicit max temp report and not from the hourly data.  * indicates max temp was derived from the hourly data (i.e., highest hourly or synoptic-reported temperature)'],
		'MIN' => [111,116, 'Minimum temperature reported during the day in Fahrenheit to tenths--time of min temp report varies by country and region, so this will sometimes not be the min for the calendar day.  Missing = 9999.9'],                        
		'FlagMIN' => [117,117, 'Blank indicates min temp was taken from the explicit min temp report and not from the hourly data.  * indicates min temp was derived from the hourly data (i.e., lowest hourly or synoptic-reported temperature)'],
		'PRCP' => [119,123, 'Total precipitation (rain and/or melted snow) reported during the day in inches and hundredths; will usually not end  with the midnight observation--i.e., may include latter part of previous day..00 indicates no measurable precipitation (includes a trace). Missing = 99.99 Note:  Many stations do not report 0 on days with no precipitation--therefore,  99.99 will often appear on these days. Also, for example, a station may only report a 6-hour amount for the period during which rain fell. See Flag field for source of data'],
		'FlagPRCP' => [124,124, 'A = 1 report of 6-hour precipitation amount. B = Summation of 2 reports of 6-hour precipitation amount. C = Summation of 3 reports of 6-hour precipitation amount. D = Summation of 4 reports of 6-hour precipitation amount. E = 1 report of 12-hour precipitation amount.    F = Summation of 2 reports of 12-hour precipitation amount. G = 1 report of 24-hour precipitation amount.  H = Station reported 0 as the amount for the day (eg, from 6-hour reports), but also reported at least one  occurrence of precipitation in hourly  observations--this could indicate a       trace occurred, but should be considered as incomplete data for the day. I = Station did not report any precip data for the day and did not report any occurrences of precipitation in its hourly observations--its still possible that precip occurred but was not reported'],
		'SNDP' => [126,130, 'Snow depth in inches to tenths--last report for the day if reported more than once.  Missing = 999.9 Note:  Most stations do not report 0 on days with no snow on the ground--therefore, 999.9 will often appear on these days'],
		'FRSHTT' => [133,138, 'Indicators (1 = yes, 0 = no/not reported) for the occurrence during the day of: Fog (F - 1st digit). Rain or Drizzle (R - 2nd digit). Snow or Ice Pellets (S - 3rd digit). Hail (H - 4th digit). Thunder (T - 5th digit).Tornado or Funnel Cloud (T - 6th digit)'],
	];
	
	//root mean square of temp sensors (degrees) ()	
	public $mean = 1;

	//Fahrenheit to Celsius, miles in km, knots to m/s format (true|false) 
	//USCS / USC - false, SI - true
	public $metrix; 
	
	//current file content
	private $content = [];
	
	//data collection
	public $data=[];
	
	//output format: true - all data, false - formatted data
	public $format;
	
	public function __construct($metrix=false,$format=true) {	
		$this->metrix =  $metrix;
		$this->format = $format;
		$this->mean = ($this->metrix!==true) ? self::c_t_f($this->mean) : $this->mean;
	}
	
	public function parse($source) {		
		$this->content = [];
		try {
			if (file_exists($source)) $this->read_file($source); else $this->read_string($source);
			if (strlen($this->content[0])<138) throw new \Exception ('Error data format');
		} 
		catch (\Exception $e) {
			return NULL;
		}	
		$this->parseraw();			
		return $this->data;
	}
			
	private function parseraw() {				
		for ($i=1;$i<count($this->content);$i++) {
			$dayset = new \STDClass;
			foreach (self::$fields as $key=>$value) {						
				$dayset->$key = substr($this->content[$i], $value[0]-1, $value[1]-$value[0]+1);	
				$dayset->$key = (substr_count($dayset->$key,'9') >= 4 or $dayset->$key==' ') ? NULL : $dayset->$key;						
			}			
			$this->data[$dayset->STN][(int)$dayset->YEAR][(int)substr($dayset->MODA,0,2)][(int)substr($dayset->MODA,2,2)] = ($this->format) ? $this->shorter($dayset) : $this->clear($dayset);			
		}
	}
	
	protected function shorter($dayset) {
		$dayset = $this->clear($dayset);
		$short = new \STDClass;			
		$short->temp = (object) [
			'average' => (object) [
				'data' => $dayset->TEMP,
				'inaccuracy' => $this->inaccuracy($dayset->CountTEMP)],
			'dewpoint' => (object) [
				'data' => $dayset->DEWP,
				'inaccuracy' => $this->inaccuracy($dayset->CountDEWP)],				
			'min' => (object) [
				'data' => $dayset->MIN,
				'inaccuracy' => (empty($dayset->FlagMIN)) ? NULL : $this->inaccuracy($dayset->CountTEMP)],
			'max' => (object) [
				'data' => $dayset->MAX,
				'inaccuracy' => (empty($dayset->FlagMAX)) ? NULL : $this->inaccuracy($dayset->CountTEMP)],			 
		];
		$short->pressure = (object) [
			'sea' => (object) [
				'data' => $dayset->SLP,
				'inaccuracy' => $this->inaccuracy($dayset->CountSLP)],
			'station' => (object) [
				'data' => $dayset->STP,
				'inaccuracy' => $this->inaccuracy($dayset->CountSTP)],
		];
		$short->wind = (object) [
			'speed' => (object) [
				'data' => $dayset->WDSP,
				'inaccuracy' => $this->inaccuracy($dayset->CountWDSP)],
			'max' => (object) ['data' => $dayset->MXSPD],
			'gust' => (object) ['data' => $dayset->GUST],
		];
		$short->precipitation = (object) array_merge([
			'level' => (object) [
				'data' => $dayset->PRCP,
				'inaccuracy' => $this->FlagPRCP($dayset->FlagPRCP)],
			'snow' => (object) [
				'data' => $dayset->SNDP,
				'inaccuracy' => $this->FlagPRCP($dayset->FlagPRCP)],
			'visibility' => (object) ['data' => $dayset->VISIB],							
		], $this->FRSHTT($dayset->FRSHTT));
		return $short;
	}

	
	private function clear($dayset) {
		unset ($dayset->STN);
		unset ($dayset->YEAR);
		unset ($dayset->MODA);
		unset ($dayset->WBAN);	
		$dayset->TEMP = ($this->metrix===true) ? self::f_t_c($dayset->TEMP) : self::check($dayset->TEMP);
		$dayset->DEWP = ($this->metrix===true) ? self::f_t_c($dayset->DEWP) : self::check($dayset->DEWP);
		$dayset->VISIB = ($this->metrix===true) ? self::m_t_k($dayset->VISIB) : self::check($dayset->VISIB);
		$dayset->WDSP = ($this->metrix===true) ? self::k_t_m($dayset->WDSP) : self::check($dayset->WDSP);
		$dayset->MXSPD = ($this->metrix===true) ? self::k_t_m($dayset->MXSPD) : self::check($dayset->MXSPD);
		$dayset->GUST = ($this->metrix===true) ? self::k_t_m($dayset->GUST) : self::check($dayset->GUST);
		$dayset->MAX = ($this->metrix===true) ? self::f_t_c($dayset->MAX) : self::check($dayset->MAX);
		$dayset->MIN = ($this->metrix===true) ? self::f_t_c($dayset->MIN) : self::check($dayset->MIN);
		$dayset->PRCP = ($this->metrix===true) ? self::i_t_m($dayset->PRCP) : self::check($dayset->PRCP);
		$dayset->SNDP = ($this->metrix===true) ? self::i_t_m($dayset->SNDP) : self::check($dayset->SNDP);
		return $dayset;
	}
	
	protected function read_file($file) {		
		$handle = @fopen($file, "r");		
		if ($handle) {
			while (($buffer = fgets($handle, 4096)) !== false) {
				$this->content[] = $buffer;
			}
			if (!feof($handle)) {
				$this->content = [];
				throw new \Exception ('Can not open datafile');
			}
		fclose($handle);
		}	
	}
			
	protected function read_string($string) {		
		$array = explode("\n",$string);
		if (count($array)>2) {
			$this->content = $array;
		} else {
			throw new \Exception ('Data string format error');
		}
	}
	
	//Check NULL
	public static function check($var) {
		return ($var==NULL) ? NULL : (float) $var;		
	}
		
	//Fahrenheit to Celsius
	public static function f_t_c($temp) {			
		return ($temp==NULL) ? NULL : (float) round(($temp-32)*(5/9),1);
	}
	
	//Celsius to Fahrenheit
	public static function c_t_f($temp) {			
		return ($temp==NULL) ? NULL : (float) round(($temp*(9/5)+32),1);
	}
	
	//Miles in km	
	public static function m_t_k($miles) {	
		return ($miles==NULL) ? NULL : (float) round($miles*1,609344,2);		
	}
	
	//knots in m|s
	public static function k_t_m($knots) {	
		return ($knots==NULL) ? NULL : (float) round($knots*0.514444,2);	
	}
	
	//inches in mm
	public static function i_t_m($inches) {	
		return ($inches==NULL) ? NULL :  (float) round($inches*25.399999,1);	
	}
	
	//inaccuracy of serial data
	private function inaccuracy($number) {	
		return ($number>0) ? round($this->mean/pow($number,0.5),2) : 0;	
	}
	
	 /**
	  * inaccuracy of precipitation level
	  * A = 1 report of 6-hour precipitation amount. 
	  * B = Summation of 2 reports of 6-hour precipitation amount. 
	  * C = Summation of 3 reports of 6-hour precipitation amount. 
	  * D = Summation of 4 reports of 6-hour precipitation amount. 
	  * E = 1 report of 12-hour precipitation amount.    
	  * F = Summation of 2 reports of 12-hour precipitation amount. 
	  * G = 1 report of 24-hour precipitation amount.  
	  * H = Station reported 0 as the amount for the day (eg, from 6-hour reports), but also reported at least one  occurrence of precipitation in hourly  observations--this could indicate a trace occurred, but should be considered as incomplete data for the day. 
	  * I = Station did not report any precip data for the day and did not report any occurrences of precipitation in its hourly observations--its still possible that precip occurred but was not reported
	  */
	private function FlagPRCP($flag) {
		switch ($flag) {
			case 'A':
				return 4;
			break;
			case 'B':
				return $this->inaccuracy(2);
			break;
			case 'C':
				return $this->inaccuracy(3);
			break;
			case 'D':
				return $this->inaccuracy(4);
			break;
			case 'E':
				return 2;
			break;
			case 'F':
				return ($this->inaccuracy(2)+2)/2;
			break;
			case 'G':
				return 3;
			break;
			case 'H':
				return 0;
			break;
			case 'I':
				return NULL;
			break;
			default:
				return NULL;
		}
	}
	
	/**
	 * Indicators (1 = yes, 0 = no/not reported) for the occurrence during the day of: 
	 * Fog (F - 1st digit). 
	 * Rain or Drizzle (R - 2nd digit). 
	 * Snow or Ice Pellets (S - 3rd digit). 
	 * Hail (H - 4th digit). 
	 * Thunder (T - 5th digit).
	 * Tornado or Funnel Cloud (T - 6th digit)
	 */
	private function FRSHTT($string) {		
		$i=0;
		$return = ['fog' => FALSE, 'rain' => FALSE, 'snow' => FALSE, 'hail' => FALSE, 'thunder' => FALSE, 'tornado' => FALSE];
		foreach ($return as $key=>$value) {
			if (mb_substr($string, $i, 1)==1) $return[$key] = TRUE;
			$i++;
		}
		return $return;
	}
}
 ?>
