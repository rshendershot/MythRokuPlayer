<?php
require_once 'settings.php';

// jobqueue classes
class Jobs extends XmlEmitter {}

$chanid = '';
$starttime = '';

if(isset(  $_GET['chanid']  ))
	$chanid = $_GET['chanid'];
	
if(isset(  $_GET['starttime']  ))
	$starttime = str_replace('Z','',str_replace('T',' ',$_GET['starttime']));	

error_log("jobqueue_service:  chanid:$chanid:starttime:$starttime:", 0);
//TODO refactor to use mythconverg.inuseprograms where recusage = 'jobqueue'


    $SQL = <<<EOF
SELECT J.status FROM jobqueue J
JOIN (
    SELECT CASE value
      WHEN 'JobAllowTranscode' THEN CAST(0x0001 AS UNSIGNED)
      WHEN 'JobAllowCommFlag'  THEN CAST(0x0002 AS UNSIGNED)
      WHEN 'JobAllowMetadata'  THEN CAST(0x0004 AS UNSIGNED)
      WHEN 'JobAllowUserJob1'  THEN CAST(0x0100 AS UNSIGNED)
      WHEN 'JobAllowUserJob2'  THEN CAST(0x0200 AS UNSIGNED)
      WHEN 'JobAllowUserJob3'  THEN CAST(0x0400 AS UNSIGNED)
      WHEN 'JobAllowUserJob4'  THEN CAST(0x0800 AS UNSIGNED)
      END AS type
    FROM settings
    WHERE value LIKE 'JobAllow%' AND data = 1
) AS S ON S.type = J.type
WHERE CAST(0x0100 AS UNSIGNED) > J.status
AND J.chanid = '$chanid' AND J.starttime = '$starttime'

EOF;

$jobs = new Jobs(
	array(
		'content'=>( 
			count(JobQueue::find_by_sql($SQL))>0 ? true:false
		)
	)
);

print $jobs;  //consumer gets XML in response to its jobqueue service call
	
?>
