#!/bin/sh

#iconv -f iso-8859-15 -t utf-8 {source} -o {target}

IFS=$'\n'
for file in $(find . -type f); do
   #file -i $file | grep "; charset=iso-8859-1"
   if file -i "$file" | grep -q "; charset=iso-8859-1" ; then
      echo "converting: $file"
      iconv -f iso-8859-1 -t utf-8 "$file" > "${file}.ansi-to-utf8"
      mv "${file}.ansi-to-utf8" "${file}"
   fi
done
