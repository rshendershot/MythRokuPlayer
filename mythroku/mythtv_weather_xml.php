<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Weather'])) {
	$select = rawurldecode($_GET['Weather']);	

	//build feed for this specific group	
	error_log("selecting Weather: $select", 0);

	// Location in the form of 'City Name, State Abbreviation, Country Abreviation'  
	$weatherType = 'mode=xml&units=imperial&cnt='.$UpcomingListLimit;
	$weatherSvc = "http://api.openweathermap.org/data/2.5/$select&$weatherType";
	$weatherList = new SimpleXMLElement($weatherSvc, NULL, TRUE);
	//print $weatherList->asXML(); return;
	
	if(!empty($weatherList)){
		$items = array();
		foreach($weatherList->xpath('//current') as $value) {
			$nameEl = $value->xpath('//city/@name');  
			$tempEl = $value->xpath('//temperature/@value');
			$iconEl = $value->xpath('//weather/@icon');
			$conditionsEl = $value->xpath('//weather/@value');
			$windspeadEl = $value->xpath('//wind/speed/@name');
			$winddirectionEl = $value->xpath('//wind/direction/@code');
			$cloudsEl = $value->xpath('//clouds/@name');
			
			$asofEl = $value->xpath('//lastupdate/@value');
			
			$temp = round((float)$tempEl[0]);
			
			$weatherTpl = new SimpleXMLElement('<Weather/>');
			$weatherTpl->addChild('Location', (string)$nameEl[0]);
			$weatherTpl->addChild('Temperature', $temp.' F.');
			$weatherTpl->addChild('Icon', (string)$iconEl[0]);
			$weatherTpl->addChild('Conditions', (string)$conditionsEl[0]);
			$weatherTpl->addChild('WindSpeed', (string)$windspeadEl[0]);
			$weatherTpl->addChild('WindDirection', (string)$winddirectionEl[0]);
			$weatherTpl->addChild('Clouds', (string)$cloudsEl[0]);
			$weatherTpl->addChild('AsOf', (string)$asofEl[0]);
			$weatherTpl->addChild('Source', 'Provided by http://openweathermap.org/');
			
			$current = new Weather($weatherTpl);
			
			$items[] = new item($current);
		}
		foreach($weatherList->xpath('//forecast/time') as $value) {
			$nameEl = $value->xpath('//location/name');  
			$tempMaxEl = $value->xpath('.//temperature/@max');
			$tempMinEl = $value->xpath('.//temperature/@min');
			$iconEl = $value->xpath('.//symbol/@var');
			$conditionsEl = $value->xpath('.//precipitation/@type');
			$windspeadEl = $value->xpath('.//windSpeed/@name');
			$winddirectionEl = $value->xpath('.//windDirection/@code');
			$cloudsEl = $value->xpath('.//clouds/@value');
			
			$asofEl = $value->xpath('.//@day');
			
			$tempMax = round((float)$tempMaxEl[0]);
			$tempMin = round((float)$tempMinEl[0]);
			
			$conditions = empty($conditionsEl[0]) ? '':(string)$conditionsEl[0];
			$precip = (empty($conditions) ? 'No Precipitation' : $conditions);
			
			$weatherTpl = new SimpleXMLElement('<Weather/>');
			$weatherTpl->addChild('Location', (string)$nameEl[0]);
			$weatherTpl->addChild('Temperature', "$tempMin...$tempMax F.");
			$weatherTpl->addChild('Icon', (string)$iconEl[0]);
			$weatherTpl->addChild('Conditions', ucwords($precip));
			$weatherTpl->addChild('WindSpeed', (string)$windspeadEl[0]);
			$weatherTpl->addChild('WindDirection', (string)$winddirectionEl[0]);
			$weatherTpl->addChild('Clouds', (string)$cloudsEl[0]);
			$weatherTpl->addChild('AsOf', (string)$asofEl[0]);
			$weatherTpl->addChild('Source', 'Provided by http://openweathermap.org/');
			
			$current = new Weather($weatherTpl);
			
			$items[] = new item($current);		
		}
		usort($items, 'items_date_compare');

		$feed = new feed(
			array(
				'resultLength'=>new resultLength(array('content'=>count($items)))
				, 'endIndex'=>new endIndex(array('content'=>count($items)))
				, 'item'=>$items
			)
		);	
	}else{
		$feed = new feed(
			array(
				'resultLength'=>new resultLength(array('content'=>count($items)))
				, 'endIndex'=>new endIndex(array('content'=>count($items)))
				, 'item'=>array(new item(new ProgramTpl(new SimpleXMLElement(ProgramTpl::rsEMPTY))))
			)		
		);				
	}

	print $feed;
	
}else{
//build category from available groups	

	$weather = new category(
		array(XmlEmitter::ATR.'title'=>'Weather'
			, XmlEmitter::ATR.'description'=>'AS-IS - a proof-of-concept with no warranty'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/weather.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/weather.png"
			, 'categoryLeaf'=>array()
		)
	);
	
	$menu = array();
	$results = array('weather', 'forecast');	
	
	foreach ( $results as $value ) {
		$resource = ($value=='forecast' ? 'forecast/daily' : $value);
		$parms = array('Weather'=>rawurlencode("$resource?q=$City,$State,$Country"));
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>ucwords($value)
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_weather_xml.php?".http_build_query($parms))  
    	);   
	}
	
	$weather->categoryLeaf = $menu;

	return $weather;	
	
}	

?>
