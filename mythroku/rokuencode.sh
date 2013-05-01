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

echo "" >> $LOGFILE
echo "Roku Encode $MPGFILE to $newname, details in $LOGFILE"

date=`date`
echo "$newbname:$date Encoding" >> $LOGFILE
#/usr/bin/HandBrakeCLI -i $1/$2 -o $newname -e x264 -b 1500 -E faac -B 256 -R 48 -w 720
#/usr/bin/HandBrakeCLI -i $MYTHDIR/$MPGFILE -o $newname -e x264 -r 29.97 -b 1500 -E faac -B 256 -R 48 --decomb >> $LOGFILE 2>&1
#/usr/bin/HandBrakeCLI --preset='iPhone & iPod Touch' -i $MYTHDIR/$MPGFILE -o $newname >> $LOGFILE 2>&1
/usr/bin/HandBrakeCLI --preset='iPhone & iPod Touch' -i $MYTHDIR/$MPGFILE -o $newname 
### v0.9.6 audio sync problems with the following
#/usr/bin/HandBrakeCLI --preset='Normal' -i $MYTHDIR/$MPGFILE -o $newname 
#/usr/bin/HandBrakeCLI --preset='Android High' -i $MYTHDIR/$MPGFILE -o $newname 

#date=`date`
#echo "$newbname:$date Previews" >> $LOGFILE
#Mythtv seems to have problems with keyframes in mp4s, so make previews with ffmpeg
#   ffmpeg -loglevel quiet -ss 34 -vframes 1 -i $newname -y -f image2  $MYTHDIR/$newbname.mp4.png >> $LOGFILE 2>&1
#   ffmpeg -loglevel quiet -ss 34 -vframes 1 -i $newname -y -f image2 -s 100x75 $MYTHDIR/$newbname.mp4.64.100x75.png >> $LOGFILE 2>&1
#   ffmpeg -loglevel quiet -ss 34 -vframes 1 -i $newname -y -f image2 -s 320x240 $MYTHDIR/$newbname.mp4.64.320x240.png >> $LOGFILE 2>&1

date=`date`
echo "$newbname:$date Database/remove" >> $LOGFILE
# remove the orignal mpg and update the db to point to the mp4
NEWFILESIZE=`du -b "$newname" | cut -f1`
echo "UPDATE recorded SET basename='$newbname.mp4',filesize='$NEWFILESIZE',transcoded='1' WHERE basename='$2';" > /tmp/update-database.sql
mysql --user=$DATABASEUSER --password=$DATABASEPASSWORD mythconverg < /tmp/update-database.sql
#rm $MYTHDIR/$MPGFILE

# Make the bif files for trick play
#   cd $MYTHDIR
# If it's HD we assume it's 16:9
#   date=`date`
#echo "$newbname:$date makebif HD" >> $LOGFILE
#   /usr/local/bin/makebif.py -m 3 $newname >> $LOGFILE 2>&1
# If it's SD we assume it's 4:3
#   date=`date`
#   echo "$newbname:$date makebif SD" >> $LOGFILE
#   /usr/local/bin/makebif.py -m 0 $newname >> $LOGFILE 2>&1

date=`date`
echo "$newbname:$date Complete" >> $LOGFILE
echo "" >> $LOGFILE

