#!/bin/sh
#
# Aufruf: find /var/www -name 'php_error_log' -print0 2> /dev/null | xargs -0r logPHPErrors.sh
#   oder:
#         /bin/ls -1 /var/www/project/*/log/php_error_log 2> /dev/null | while read line ; do logPHPErrors.sh "$line" ; done
#
# Als Cron-Job ausführen.
#
##############################################################################################################################



# Leerzeichengetrennte Liste der User oder E-Mail-Adressen, an die Benachrichtigungen geschickt werden sollen
# (z.B. 'root webmaster@domain.com')
#webmasters=root
#webmasters='user1@domain.tld user2@domain.tld'
webmasters='user1@domain.tld'



# default to current user, if not specified.
addresses=${webmasters:-`whoami`}



# alle übergebenen Dateinamen verarbeiten
for file ; do

    # abbrechen, wenn die Datei gerade bearbeitet wird
    [ -f "$file.tmp" ] && echo "Remove file $file.tmp, it is in the way." 1>&2 && exit 1

    # Datei umbenennen, ...
    mv "$file" "$file.tmp"

    # ... jede Zeile an jede E-Mail-Adresse schicken, ...
    while read line ; do
        for address in $addresses ; do
            echo 'PHP error in: '$file $'\n\n'$line | mail -s "PHP: [FATAL] Uncatched error" $address
        done
    done < "$file.tmp"

    # ... und aufräumen
    rm "$file.tmp"
done


exit 0
