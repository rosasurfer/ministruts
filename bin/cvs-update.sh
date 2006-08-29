#!/bin/sh

PROJECT_ROOT=/var/www
USER=`cat ~/.cvs.shadow1`
PASS=`cat ~/.cvs.shadow2`

if   [ $USERNAME = 'user1' ] ; then
    PORT=2402
elif [ $USERNAME = 'user2' ] ; then
    PORT=2403
else
    PORT=2401
fi


cd ${PROJECT_ROOT}

export CVSROOT=:pserver:${USER}:${PASS}@localhost:${PORT}/home/cvs
cvs -d ${CVSROOT} login

#cvs -d ${CVSROOT} -qr checkout -PR -d php_lib -r HEAD php_lib
#-------------------------------------------------------------
cvs -d ${CVSROOT} -qr update -CPRd -r HEAD php_lib

cvs -d ${CVSROOT} logout
export -n CVSROOT


find php_lib -follow -type f -print0 | xargs -0r chmod 0644

# may take some time, let's do it in the background
find php_lib -follow -type d \( ! -group apache -o ! -user apache \) ! -name 'CVS'   -print0 | xargs -0r chown apache:apache && \
find php_lib -follow -type d                                                         -print0 | xargs -0r chmod 0755          && \
find php_lib -follow -type f   -path '*/bin*' -prune -regex '.*\.\(pl\|php\|sh\)'    -print0 | xargs -0r chmod 0754          && \
find php_lib -follow -type f ! -path '*/bin*' -prune ! -perm 0644                    -print0 | xargs -0r chmod 0644          && \
find php_lib -follow -name '.#*'                                                     -print0 | xargs -0r rm                  &
