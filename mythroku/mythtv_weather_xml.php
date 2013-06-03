<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Weather'])) {
	$select = rawurldecode($_GET['Weather']);	

	//build feed for this specific group	
	error_log("selecting Weather: $select", 0);

	// Location in the form of 'City Name, State Abbreviation, Country Abreviation'  
	$weatherSvc = "http://api.openweathermap.org/data/2.5/weather?mode=xml&units=imperial&q=$select";
	$weatherList = new SimpleXMLElement($weatherSvc, NULL, TRUE);
	
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
		
		$temp = (string)$tempEl[0];
		
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

	usort($items, 'items_date_compare');
	
	$feed = new feed(
		array(
			'resultLength'=>new resultLength(array('content'=>count($items)))
			, 'endIndex'=>new endIndex(array('content'=>count($items)))
			, 'item'=>$items
		)
	);	
	
	print $feed;
	
}else{
//build category from available groups	

	$weather = new category(
		array(XmlEmitter::ATR.'title'=>'Weather'
			, XmlEmitter::ATR.'description'=>'Local Conditions'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/weather.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/weather.png"
			, 'categoryLeaf'=>array()
		)
	);
	
	$menu = array();
	$results = array('Current');	
	
	foreach ( $results as $value ) {
		$parms = array('Weather'=>rawurlencode("$City,$State,$Country"));
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>$City
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_weather_xml.php?".http_build_query($parms))  
    	);   
	}
	
	$weather->categoryLeaf = $menu;

	return $weather;	
	
}	

?>
