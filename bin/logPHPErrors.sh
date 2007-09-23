#!/bin/sh



cd /var/www/project
find . -name 'php_error_log'


exit 0



# Parameter prüfen
#[ ! $# -eq 1 -o ! "${1#**.}" = "1" ] && echo "Usage: ${0#**/} <logfile.1>" 1>&2 && exit 1


# Variablen definieren
logfilename=impression_log
logfile=$logfilename.1
date=`date '+%Y-%m-%d'`


# prüfen, ob die Logdatei existiert und beschreibbar ist
cd /var/www/project/domain.tld/log
[ ! -f $logfile ] && echo "File not found: $logfile"    1>&2 && exit 1
[ ! -w $logfile ] && echo "File not writable: $logfile" 1>&2 && exit 1


# sortieren (wenn im Apache BufferedLogs eingeschaltet sind, sind die Logs nicht sortiert)
sort -o $logfile $logfile


# Existenz des Webverzeichnisses überprüfen und ggf. anlegen
pid=25001
targetdir=/var/www/project/domain.tld/htdocs/path/logs/$pid
if [ ! -d $targetdir ] ; then
    mkdir -p $targetdir
    [ ! -d $targetdir ] && echo "Cannot create webmaster directory: $targetdir" 1>&2 && exit 1
fi


# Partner-ID rausfiltern und Ergebnisse gezippt ins entsprechende Webverzeichnis schreiben
targetfile=$targetdir/$logfilename-$date.zip
[ -f $targetfile ] && echo "File already exists: $targetfile" 1>&2 && exit 1
grep "pid=$pid" $logfile > $logfilename-$date
zip -9 $targetfile $logfilename-$date > /dev/null
rm $logfilename-$date
