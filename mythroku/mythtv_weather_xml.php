<?php
require_once 'settings.php';
include_once 'player_feed.php';

const _DELAY = 90;

if(isset($_GET['Weather'])) {
	$select = rawurldecode($_GET['Weather']);	
	
	//build feed for this specific group
	error_log("selecting Weather: $select", 0);

	$weatherType = 'xml';
	$pws = "pws:" . (int)$PWS;
	$nws = "bestfct:" . (int)!$NWS;  
	$resource = "alerts/conditions/forecast10day/yesterday/almanac/satellite/$pws/$nws/q/$Country/$State/$City.$weatherType"; 

	$weatherSvc = "http://api.wunderground.com/api/$API_KEY/$resource";
	$weatherList = get_last_query_result($weatherSvc); 	
	
	$items = array();
	if(!empty($weatherList)){
		if(can_process_feed($weatherList)){
			foreach($weatherList->xpath('//response/alerts/alert') as $value) {
				if($select != "conditions")	continue;
				
				$significanceEl = $value->xpath('.//significance');  //http://www.weather.gov/os/vtec/pdfs/VTEC_explanation6.pdf
				$significance = (string)$significanceEl[0];
				if($significance == 'W'){  //Warning
					$icon = "$WebServer/$MythRokuDir/images/oval_red.png";
				}else if($significance == 'A'){  //Watch
					$icon = "$WebServer/$MythRokuDir/images/oval_orange.png";
				}else continue;  //skip any other types
				
				$nameEL = $weatherList->xpath('//response/current_observation/display_location/city');
				$descEl = $weatherList->xpath('.//description');
				$messageEl = $weatherList->xpath('.//message');
				$untilEl = $weatherList->xpath('.//expires');

				$asofEl = $weatherList->xpath('.//date_epoch');
				$asof = (string)$asofEl[0];
				
				$weatherTpl = new SimpleXMLElement('<Weather/>');
				$weatherTpl->addChild('Delay', _DELAY);
				$weatherTpl->addChild('Location', (string)$nameEL[0]);
				$weatherTpl->addChild('Description', (string)$descEl[0]);
				$weatherTpl->addChild('Message', (string)$messageEl[0]);
				$weatherTpl->addChild('Icon', $icon);	
				$weatherTpl->addChild('AsOf', date('A h:i:s', $asof));
				$weatherTpl->addChild('Until', "until $untilEl[0]");
				$weatherTpl->addChild('Source', 'Provided by www.wunderground.com');

				$current = new Weather($weatherTpl);
				
				$items[] = new item($current);
			}
			
			foreach($weatherList->xpath('//response/current_observation') as $value) {
				if($select != "conditions")	continue;
	
				$nameEl = $value->xpath('.//observation_location/city');  
				$tempEl = $value->xpath('.//temp_f');
				$iconEl = $value->xpath('.//icon_url');
				
				$conditionsEl = $value->xpath('.//weather');
				$windspeadEl = $value->xpath('.//wind_mph');
				$windgustEl = $value->xpath('.//wind_gust_mph');
				$winddirectionEl = $value->xpath('.//wind_dir');
				$cloudsEl = $value->xpath('.//visibility_mi');
				$humidityEl = $value->xpath('.//relative_humidity');
				
				$asofEl = $value->xpath('.//observation_epoch');
				$asof = (string)$asofEl[0];
							
				$temp = round((float)$tempEl[0]);
				
				$weatherTpl = new SimpleXMLElement('<Weather/>');
				$weatherTpl->addChild('Delay', _DELAY);
				$weatherTpl->addChild('Location', (string)$nameEl[0]);
				$weatherTpl->addChild('Temperature', $temp.' F.');
				$weatherTpl->addChild('Icon', (string)$iconEl[0]);
				$weatherTpl->addChild('Conditions', (string)$conditionsEl[0]);
				$weatherTpl->addChild('WindSpeed', (string)$windspeadEl[0]);
				$weatherTpl->addChild('WindDirection', (string)$winddirectionEl[0]);
				$weatherTpl->addChild('WindGust', (string)$windgustEl[0]);
				$weatherTpl->addChild('Clouds', (string)$cloudsEl[0]);
				$weatherTpl->addChild('Humidity', (string)$humidityEl[0]);
				$weatherTpl->addChild('AsOf', date('D H:i:s', $asof));
				$weatherTpl->addChild('Until', "Current Conditions");
				$weatherTpl->addChild('Source', 'Provided by www.wunderground.com');
				
				$current = new Weather($weatherTpl);
				
				$items[] = new item($current);
			}
			
			foreach($weatherList->xpath('//response/almanac') as $value) {
				if($select != "conditions")	continue;
				
				$nameEl = $value->xpath('//observation_location/city');
				$iconEl = $value->xpath('//satellite/image_url');
				
				$iconUrl = "";
				try{
					$iconDir = "cache";
					
					if (!is_dir($iconDir) or !is_writable($iconDir)) {
						throw new Exception("$iconDir is not writable.");
					}					
					
					$iconUrl = "$iconDir/curRadar.png";					
					file_put_contents( $iconUrl, file_get_contents(rawurldecode($iconEl[0])) );
				}catch(Exception $e) {					
					error_log(">>>Could not get radar image: " . $e->getMessage());
					$iconUrl = "$WebServer/$MythRokuDir/images/view-calendar-upcoming-days.png";
				}
				
				$airport_codeEl = $value->xpath('.//airport_code');
				$normal_highEl = $value->xpath('.//temp_high/normal/F');
				$normal_lowEl = $value->xpath('.//temp_low/normal/F');
				$record_highEl = $value->xpath('.//temp_high/record/F');
				$record_high_yearEl = $value->xpath('.//temp_high/recordyear');
				$record_lowEl = $value->xpath('.//temp_low/record/F');
				$record_low_yearEl = $value->xpath('.//temp_low/recordyear');				
				
				$tempMax = round((float)$normal_highEl[0]);
				$tempMin = round((float)$normal_lowEl[0]);
				
				$recordMax = round((float)$record_highEl[0]);
				$recordMaxYear = (string)$record_high_yearEl[0];
				$recordMin = round((float)$record_lowEl[0]);
				$recordMinYear = (string)$record_low_yearEl[0];
				
				$conditions = "Normal: $tempMin...$tempMax F. Records: HI $recordMax ($recordMaxYear) LO $recordMin ($recordMinYear)";
				
				$weatherTpl = new SimpleXMLElement('<Weather/>');
				$weatherTpl->addChild('Delay', 0);
				$weatherTpl->addChild('Location', "$nameEl[0] ($airport_codeEl[0])");
				$weatherTpl->addChild('Temperature', "$tempMin...$tempMax F.");
				$weatherTpl->addChild('Icon', "$WebServer/$MythRokuDir/$iconUrl");
				//rawurldecode(htmlspecialchars($iconEl[0])));   #probably fails in cookie handling from Roku to wunderground WUBLAST  ?  -RSH
				$weatherTpl->addChild('Conditions', $conditions);
				$weatherTpl->addChild('AsOf', date('M j'));
				$weatherTpl->addChild('Until', "Historical Norms");
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
				
				$asofEl = $value->xpath('.//date/epoch');
				$asof = (string)$asofEl[0];
				
				$tempMax = round((float)$tempMaxEl[0]);
				$tempMin = round((float)$tempMinEl[0]);			
				
				$weatherTpl = new SimpleXMLElement('<Weather/>');
				$weatherTpl->addChild('Delay', _DELAY);
				$weatherTpl->addChild('Location', (string)$nameEl[0]);
				$weatherTpl->addChild('Temperature', "$tempMin...$tempMax F.");
				$weatherTpl->addChild('Icon', (string)$iconEl[0]);
				$weatherTpl->addChild('Conditions', (string)$conditionsEl[0]);
				$weatherTpl->addChild('WindSpeed', (string)$windspeadEl[0]);
				$weatherTpl->addChild('WindDirection', (string)$winddirectionEl[0]);
				$weatherTpl->addChild('Clouds', (string)$cloudsEl);
				$weatherTpl->addChild('Humidity', (string)$humidityEl[0] . "%");
				$weatherTpl->addChild('AsOf', date('D dMo', $asof));
				$weatherTpl->addChild('Source', 'Provided by www.wunderground.com');
				
				$current = new Weather($weatherTpl);
				
				$items[] = new item($current);

				usort($items, 'items_date_compare');
			}						
	
			$feed = new feed(
				array(
					'resultLength'=>new resultLength(array('content'=>count($items)))
					, 'endIndex'=>new endIndex(array('content'=>count($items)))
					, 'item'=>$items
				)
			);
		}else{ 
			$msgEl = $weatherList->xpath('//error/type');
			$descEl = $weatherList->xpath('//error/description');
			
			$msg = (string)$msgEl[0];
			$desc = (string)$descEl[0];
			if(!empty($desc)) 
				$msg .= ": $desc";
			$info = new ProgramTpl(new SimpleXMLElement(ProgramTpl::rsERROR));
			$info->Title = "Exception";
			$info->Description = $msg; 

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
    	$menu[] = new categoryLeaf( 
    		array(XmlEmitter::ATR.'title'=>ucwords($value)
    		, XmlEmitter::ATR.'feed'=>"$WebServer/$MythRokuDir/mythtv_weather_xml.php?".http_build_query($parms))  
    	);   
	}
	
	$weather->categoryLeaf = $menu;

	return $weather;		
}

function get_last_query_result($svc)
{
	//free subscription currently allows 10 request per minute, 500 requests per day
	$delay = _DELAY;
	$conditions = array('conditions'=>array('value = ?', 'MrpLastWeatherResults'));
	$MrpLastWeatherResults = MythSettings::first($conditions);
	
	if(empty($MrpLastWeatherResults)){
		$MrpLastWeatherResults = new MythSettings();
		$MrpLastWeatherResults->value = 'MrpLastWeatherResults';
		//TODO: refactor
		$MrpLastWeatherResults->hostname = new DateTime("-$delay seconds"); //init with obsolete timestamp
		$MrpLastWeatherResults->save(); 
	}
	
	$lastCall = new DateTime($MrpLastWeatherResults->hostname);
	$tooSoon = new DateTime("-$delay seconds"); 
	if($lastCall <= $tooSoon || empty($MrpLastWeatherResults->data))
	{
		try{
			error_log(">>>calling weather service: $svc", 0);
			
			$data = new SimpleXMLElement($svc, NULL, TRUE);			
			if(can_process_feed($data))
				$MrpLastWeatherResults->data = gzcompress($data->asXML(), 9);
			else 
				return $data;
		}catch (Exception $e){
			return new SimpleXMLElement("<error> <type>Exception</type> <description>". $e->getMessage() ."</description> </error>");
		}
		$MrpLastWeatherResults->hostname = new DateTime();
		$MrpLastWeatherResults->save();
	}
	
	error_log(">>>using stored weather results: $MrpLastWeatherResults->hostname", 0);
	return simplexml_load_string(gzuncompress($MrpLastWeatherResults->data));	
}

function can_process_feed($xml){
	$error = $xml->xpath('//error');
	$feature = $xml->xpath('//features/feature');
	
	return (empty($error) && !empty($feature));
}

?>
