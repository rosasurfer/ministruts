#!/bin/sh
################################################################################################################################
#                                                                                                                              #
# Sucht und meldet nicht abgefangene PHP-Fehler.                                                                               #
#                                                                                                                              #
#                                                                                                                              #
# Aufruf:  logPHPErrors.sh  <ERRORLOG_DIRECTORY>                                                                               #
#                                                                                                                              #
#   ERRORLOG_DIRECTORY - Das Verzeichnis, in dem nicht abgefangene PHP-Fehler gespeichert werden. Bei Verwendung von Wildcards #
#                        muß das Argument in Anführungszeichen gesetzt werden.                                                 #
#                                                                                                                              #
# Beispiel:  logPHPErrors.sh  "/var/www/*/log"                                                                                 #
#                                                                                                                              #
################################################################################################################################


# Name der PHP-Error-Logdatei (default: php_error_log)
#
LOGFILE_NAME="php_error_log"


# User und/oder E-Mailadressen für Benachrichtigungen (kommagetrennt, keine Leerzeichen; default: der aktuelle User)
#
EMAILS_TO=root



# ------------------------------------------------------------------------------------------------------------------------------
# Start
# ------------------------------------------------------------------------------------------------------------------------------
# Source function library
. /etc/rc.d/init.d/functions

shopt -s extglob; IFS=

# ggf. Config-Defaultvalues setzen
LOGFILE_NAME=${LOGFILE_NAME:-'php_error_log'}
EMAILS_TO=${EMAILS_TO:-`whoami`}


ERRORLOG_DIRECTORY="$1"


while [ 1 ]; do
   IFS=$'\n'
   for file in `find $ERRORLOG_DIRECTORY -name "$LOGFILE_NAME"`; do
      # Datei umbenennen
      mv -f "$file" "$file.tmp"

      # Zeilen einzeln an alle E-Mail-Adressen schicken
      IFS=$'\n'
      for line in `cat "$file.tmp"`; do
         echo "PHP error in: $file"$'\n\n'"$line" | mail -s "PHP: [FATAL] Uncatched error" $EMAILS_TO
      done; IFS=

      # aufräumen
      rm "$file.tmp"
   done; IFS=

   # Schlafen gehen
   sleep 60
done

exit


# ------------------------------------------------------------------------------------------------------------------------------
