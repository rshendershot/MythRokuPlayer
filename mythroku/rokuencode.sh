#!/bin/bash

set -o errexit
renice +15 --pid $$

#convert mpeg file to mp4 using handbrakecli
MYTHDIR=$1
MPGFILE=$2

# Should try and get these from settings.php, but for now...
DATABASEUSER=mythtv
DATABASEPASSWORD=mythtv

LOGFILE="/var/log/mythtv/rokuencode.log"
 
newbname=`echo $MPGFILE | sed 's/\(.*\)\..*/\1/'`
newname="$MYTHDIR/$newbname.mp4"

date=`date`
echo "$date: Roku Encode $MPGFILE to $newname" >> $LOGFILE

date=`date`
echo "$newbname:$date Encoding" >> $LOGFILE

#mythffmpeg -y -loglevel error -threads 4 -i $MYTHDIR/$MPGFILE -strict experimental -c:a aac -c:v mpeg4 -q:v 5 -q:a 2 -ac 2 -f mp4 $newname >> $LOGFILE 2>&1
#mythffmpeg -y -loglevel error -threads 4 -i "$newname.t" -vcodec copy -acodec copy $newname >> $LOGFILE 2>&1
mythffmpeg -y -loglevel error -i $MYTHDIR/$MPGFILE -strict experimental -c:a aac -c:v mpeg4 -q:v 5 -q:a 2 -ac 2 -f mp4 $newname >> $LOGFILE 2>&1 || {
	date=`date`
	echo "$newbname:$date Encoding with ffmpeg failed, failing over to HandBrakeCLI" >> $LOGFILE
	/usr/bin/HandBrakeCLI --preset='iPhone & iPod Touch' -i $MYTHDIR/$MPGFILE -o $newname  
}

rm -f "$newname.png" >> $LOGFILE 2>&1
mythpreviewgen --loglevel err --infile $newname --seconds 123 >> $LOGFILE 2>&1

# update the seek table
date=`date`
echo "$newname:$date Seek Table/update" >> $LOGFILE
mythcommflag --file $newname --rebuild

# remove the orignal mpg - note: when the recording is deleted using the roku, this original file will be deleted
# , but not if deleted from mythtv frontend or mythweb.  see http://www.mythtv.org/wiki/Find_orphans.py for a solution.
#date=`date`
#echo "$MYTHDIR/$MPGFILE:$date File/remove" >> $LOGFILE
#rm $MYTHDIR/$MPGFILE

date=`date`
echo "$newbname:$date Database/remove" >> $LOGFILE
# update the db to point to the mp4
NEWFILESIZE=`du -b "$newname" | cut -f1`
echo "UPDATE recorded SET basename='$newbname.mp4',filesize='$NEWFILESIZE' WHERE basename='$2';" > /tmp/update-database.sql
mysql --user=$DATABASEUSER --password=$DATABASEPASSWORD mythconverg < /tmp/update-database.sql

date=`date`
echo "$date: Roku Encode $newbname Complete" >> $LOGFILE
echo "" >> $LOGFILE

