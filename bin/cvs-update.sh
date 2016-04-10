#!/bin/sh
#
# TODO: Falls chmod versucht, per Netzlaufwerk eingebundene Dateien zu bearbeiten, schlägt es fehl (read-only file system).
#
PROJECTS_ROOT=/var/www
PROJECT=ministruts


# update project
echo Updating $PROJECT ...

PASS=`cat ~/.cvs.shadow`

cd $PROJECTS_ROOT

export CVSROOT=:pserver:$USERNAME:$PASS@localhost:2401/var/cvs/rosasurfer
cvs -d $CVSROOT login

#cvs -d $CVSROOT -qr checkout -PR -d $PROJECT -r HEAD $PROJECT
#-------------------------------------------------------------
cvs -d $CVSROOT -qr update -CPRd -r HEAD $PROJECT

cvs -d $CVSROOT logout
export -n CVSROOT


# The following takes some time, so we do it in the background.
#find $PROJECT -follow                                                               -print0 2>/dev/null | xargs -0r chmod a=r,u+w,a+X

find  $PROJECT -follow -type d \( ! -group apache -o ! -user apache \) ! -name 'CVS' -print0 2>/dev/null | xargs -0r chown    apache:apache && \
find  $PROJECT -follow -type d -name 'cache'                                         -print0 2>/dev/null | xargs -0r chown -R apache:apache && \
find  $PROJECT -follow -type f -name 'config-custom.properties'                      -print0 2>/dev/null | xargs -0r chown          :cvs    && \
find  $PROJECT -follow -type f -name 'config-custom.properties'                      -print0 2>/dev/null | xargs -0r chmod g+w              && \
#find $PROJECT -follow -type f -path '*/bin*' -prune -regex '.*\.\(pl\|php\|sh\)'    -print0 2>/dev/null | xargs -0r chmod ug+x             && \
#find $PROJECT -follow -type f -name '*.sh'                                          -print0 2>/dev/null | xargs -0r chmod u+x              && \
find  $PROJECT -follow -name '.#*'                                                   -print0 2>/dev/null | xargs -0r rm                     &

echo
