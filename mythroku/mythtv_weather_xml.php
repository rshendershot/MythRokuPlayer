<?php
require_once 'settings.php';
include_once 'player_feed.php';

//const _DEBUG = 'true';

if(isset($_GET['Weather'])) {
	$select = rawurldecode($_GET['Weather']);	

	//build feed for this specific group	
	error_log("selecting Weather: $select", 0);

	$weatherType = '.xml';
	$pws = "pws:" . (int)$PWS;
	$resource = "conditions/forecast10day/$pws/q/$Country/$State/$City$weatherType"; 

	$weatherSvc = "http://api.wunderground.com/ap/$API/$resource";
	$weatherList = get_last_conditions_result($weatherSvc); 	
	
	$items = array();
	if(!empty($weatherList)){
		if(process_feed($weatherList)){
			foreach($weatherList->xpath('//response/current_observation') as $value) {
				if($select != "conditions")	continue;
	
				$nameEl = $value->xpath('.//observation_location/city');  
				$tempEl = $value->xpath('.//temp_f');
				$iconEl = $value->xpath('.//icon_url');
				
				$conditionsEl = $value->xpath('.//weather');
				$windspeadEl = $value->xpath('.//wind_mph');
				$winddirectionEl = $value->xpath('.//wind_dir');
				$cloudsEl = $value->xpath('.//visibility_mi');
				$humidityEl = $value->xpath('.//relative_humidity');
				
				$asofEl = $value->xpath('.//observation_time_rfc822');
							
				$temp = round((float)$tempEl[0]);
				
				$weatherTpl = new SimpleXMLElement('<Weather/>');
				$weatherTpl->addChild('Location', (string)$nameEl[0]);
				$weatherTpl->addChild('Temperature', $temp.' F.');
				$weatherTpl->addChild('Icon', (string)$iconEl[0]);
				$weatherTpl->addChild('Conditions', (string)$conditionsEl[0]);
				$weatherTpl->addChild('WindSpeed', (string)$windspeadEl[0]);
				$weatherTpl->addChild('WindDirection', (string)$winddirectionEl[0]);
				$weatherTpl->addChild('Clouds', (string)$cloudsEl[0]);
				$weatherTpl->addChild('Humidity', (string)$humidityEl[0]);
				$weatherTpl->addChild('AsOf', (string)$asofEl[0]);
				$weatherTpl->addChild('Source', 'Provided by www.wunderground.com');
				
				$current = new Weather($weatherTpl);
				
				$items[] = new item($current);
			}
			
			foreach($weatherList->xpath('//response/forecast/simpleforecast/forecastdays/forecastday') as $value) {
				if($select != "forecast") continue;
				
				$nameEl = $value->xpath('//observation_location/city');  
				$tempMaxEl = $value->xpath('.//high/fahrenheit');
				$tempMinEl = $value->xpath('.//low/fahrenheit');
				$iconEl = $value->xpath('.//icon_url');
				
				$conditionsEl = $value->xpath('.//conditions');
				$windspeadEl = $value->xpath('.//maxwind/mph');
				$winddirectionEl = $value->xpath('.//maxwind/dir');
				$cloudsEl = "";
				$humidityEl = $value->xpath('.//maxhumidity');
				
				$asofEl = $value->xpath('.//date/pretty');
				
				$tempMax = round((float)$tempMaxEl[0]);
				$tempMin = round((float)$tempMinEl[0]);			
				
				$weatherTpl = new SimpleXMLElement('<Weather/>');
				$weatherTpl->addChild('Location', (string)$nameEl[0]);
				$weatherTpl->addChild('Temperature', "$tempMin...$tempMax F.");
				$weatherTpl->addChild('Icon', (string)$iconEl[0]);
				$weatherTpl->addChild('Conditions', (string)$conditionsEl[0]);
				$weatherTpl->addChild('WindSpeed', (string)$windspeadEl[0]);
				$weatherTpl->addChild('WindDirection', (string)$winddirectionEl[0]);
				$weatherTpl->addChild('Clouds', (string)$cloudsEl);
				$weatherTpl->addChild('Humidity', (string)$humidityEl[0] . "%");
				$weatherTpl->addChild('AsOf', (string)$asofEl[0]);
				$weatherTpl->addChild('Source', 'Provided by www.wunderground.com');
				
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
			$msg = (string)$weatherList->xpath('//error/type')[0];
			if(!empty($weatherList->xpath('//error/description'))) 
				$msg .= (string)$weatherList->xpath('//error/description')[0];
			$info = new ProgramTpl(new SimpleXMLElement(ProgramTpl::rsERROR));
			$info->Title = "Exception";
			$info->Description = $msg; //(string)$weatherList->xpath('//error/type')[0] .':'. (string)$weatherList->xpath('//error/description')[0];
// 			print_r($weatherList);
			$feed = new feed(
				array(
					'resultLength'=>new resultLength(array('content'=>count($items)))
					, 'endIndex'=>new endIndex(array('content'=>count($items)))
					, 'item'=>array(new item($info))
				)		
			);				
		}	
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
			, XmlEmitter::ATR.'description'=>'Conditions and Forecast'
			, XmlEmitter::ATR.'sd_img'=>"$WebServer/$MythRokuDir/images/wundergroundLogo_4c.png"
			, XmlEmitter::ATR.'hd_img'=>"$WebServer/$MythRokuDir/images/wundergroundLogo_4c.png"
			, 'categoryLeaf'=>array()
		)
	);
	
	$menu = array();
	$results = array('conditions', 'forecast');	
	
	foreach ( $results as $value ) {
		$parms = array('Weather'=>rawurlencode("$value"));
		error_log(">>>" . rawurldecode($parms['Weather']),0);
		
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>ucwords($value)
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_weather_xml.php?".http_build_query($parms))  
    	);   
	}
	
	$weather->categoryLeaf = $menu;

	return $weather;		
}

function get_last_conditions_result($svc)
{
	$conditions = array('conditions'=>array('value = ?', 'MrpLastWeatherResults'));
	$MrpLastWeatherResults = MythSettings::first($conditions);
	
	if(empty($MrpLastWeatherResults)){
		$MrpLastWeatherResults = new MythSettings();
		$MrpLastWeatherResults->value = 'MrpLastWeatherResults';
		//TODO: refactor
		$MrpLastWeatherResults->hostname = new DateTime("-10 minutes"); //init with obsolete timestamp
		$MrpLastWeatherResults->save(); 
	}
	
	$lastCall = new DateTime($MrpLastWeatherResults->hostname);
	$tooSoon = new DateTime("-10 minutes"); 
	if($lastCall <= $tooSoon || empty($MrpLastWeatherResults->data))
	{
		try{
			error_log(">>>calling weather service: $svc", 0);
			
			$data = new SimpleXMLElement($svc, NULL, TRUE);			
			if(process_feed($data))
				$MrpLastWeatherResults->data = bin2hex(gzcompress($data->asXML(), 9));
			else 
				return $data;
		}catch (Exception $e){
			$msg = $e->getMessage();
			error_log(">>>SVC ERROR: " . $msg, 0);
			return new SimpleXMLElement("<error> <type>Exception</type> <description>$msg</description> </error>");
		}
		$MrpLastWeatherResults->hostname = new DateTime();
		$MrpLastWeatherResults->save();
	}
	
	error_log(">>>using stored weather results: $svc", 0);
	return simplexml_load_string(gzuncompress(hex2bin($MrpLastWeatherResults->data)));	
}

function process_feed($xml){
	$error = $xml->xpath('//error');
	$feature = $xml->xpath('//features/feature');
	
	return (empty($error) && !empty($feature));
}

?>
