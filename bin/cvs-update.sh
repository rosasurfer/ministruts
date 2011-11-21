#!/bin/sh
PROJECTS_ROOT=/var/www
PROJECT=ministruts


# update project
echo Updating $PROJECT ...

PASS=`cat ~/.cvs.shadow`

if [ $USERNAME = 'user1' ] ; then
    PORT=2402
else
    PORT=2401
fi


cd $PROJECTS_ROOT

export CVSROOT=:pserver:$USERNAME:$PASS@localhost:$PORT/var/cvs
cvs -d $CVSROOT login

#cvs -d $CVSROOT -qr checkout -PR -d $PROJECT -r HEAD $PROJECT
#-------------------------------------------------------------
cvs -d $CVSROOT -qr update -CPRd -r HEAD $PROJECT

cvs -d $CVSROOT logout
export -n CVSROOT


# may take some time, let's do it in the background
find $PROJECT -follow -type f                                                         -print0 2>/dev/null | xargs -0r chmod 0644
find $PROJECT -follow -type d \( ! -group apache -o ! -user apache \) ! -name 'CVS'   -print0 2>/dev/null | xargs -0r chown apache:apache && \
find $PROJECT -follow -type d                                                         -print0 2>/dev/null | xargs -0r chmod 0755          && \
find $PROJECT -follow -type f   -path '*/bin*' -prune -regex '.*\.\(pl\|php\|sh\)'    -print0 2>/dev/null | xargs -0r chmod 0754          && \
find $PROJECT -follow -type f ! -path '*/bin*' -prune ! -perm 0644                    -print0 2>/dev/null | xargs -0r chmod 0644          && \
find $PROJECT -follow -type f -name '*.sh'                                            -print0 2>/dev/null | xargs -0r chmod u+x           && \
find $PROJECT -follow -name '.#*'                                                     -print0 2>/dev/null | xargs -0r rm                  &

echo