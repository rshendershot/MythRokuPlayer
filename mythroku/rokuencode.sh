#!/bin/bash

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
/usr/bin/HandBrakeCLI --preset='iPhone & iPod Touch' -i $MYTHDIR/$MPGFILE -o $newname 

date=`date`
echo "$newbname:$date Database/remove" >> $LOGFILE
# update the db to point to the mp4
NEWFILESIZE=`du -b "$newname" | cut -f1`
echo "UPDATE recorded SET basename='$newbname.mp4',filesize='$NEWFILESIZE' WHERE basename='$2';" > /tmp/update-database.sql
mysql --user=$DATABASEUSER --password=$DATABASEPASSWORD mythconverg < /tmp/update-database.sql

# update the seek table
#date=`date`
#echo "$MYTHDIR/$MPGFILE:$date Seek Table/update" >> $LOGFILE
#mythcommflag --file $MYTHDIR/$MPGFILE --rebuild

# remove the orignal mpg - note: when the recording is deleted using the roku, this original file will be deleted
# , but not if deleted from mythtv frontend or mythweb.  see http://www.mythtv.org/wiki/Find_orphans.py for a solution.
#date=`date`
#echo "$MYTHDIR/$MPGFILE:$date File/remove" >> $LOGFILE
#rm $MYTHDIR/$MPGFILE

date=`date`
echo "$date: Roku Encode $newbname Complete" >> $LOGFILE
echo "" >> $LOGFILE

