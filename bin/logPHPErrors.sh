#!/bin/sh
#
# Aufruf: find /var/www/project -name 'php_error_log' -print0 2> /dev/null | xargs -0r logPHPErrors.sh
#   oder: /bin/ls -1 /var/www/project/*/log/php_error_log 2> /dev/null | while read line ; do logPHPErrors.sh "$line" ; done
#
# Sollte als Cron-Job ausgeführt werden.
#
##############################################################################################################################



# Leerzeichengetrennte Liste der User oder E-Mail-Adressen, an die Benachrichtigungen geschickt werden sollen
# (z.B. 'root webmaster@domain.com')
#webmasters=root
webmasters='user1@domain.tld user2@domain.tld'



# default to current user, if not specified.
addresses=${webmasters:-`whoami`}



# alle übergebenen Dateinamen verarbeiten
for file ; do

    # Prüfen, ob die Datei gerade bearbeitet wird. Wenn ja, abbrechen, ansonsten umbenennen ...
    [ -f "$file.tmp" ] && echo "Remove file $file.tmp, it is in the way." 1>&2 && exit 1
    mv "$file" "$file.tmp"

    # ... und jede Zeile an jede E-Mail-Adresse schicken
    while read line ; do
        for address in $addresses ; do
            echo 'PHP error in: '$file $'\n\n'$line | mail -s "PHP error_log: [Fatal] Uncatched error" $address
        done
    done < "$file.tmp"

    # aufräumen
    rm "$file.tmp"
done


exit 0
