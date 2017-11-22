#!/bin/bash
#

# notification configuration   
NOTIFY_FOR_SITE=www.domain.tld
NOTIFY_TO_EMAIL=email@domain.tld
NOTIFY_IF_ON=hostname


# check git availability
command -v git >/dev/null || { echo "ERROR: Git command not found."; exit 1; }


# change to the project's toplevel directory
cd "$(dirname "$(readlink -e "$0")")"
PROJECT_DIR=$(git rev-parse --show-toplevel 2>/dev/null)
cd "$PROJECT_DIR"


# check arguments
if [ $# -eq 0 ]; then
    # get current branch name
    NAME=$(git rev-parse --abbrev-ref HEAD)
    if [ "$NAME" = "HEAD" ]; then
        COMMIT=$(git rev-parse --short HEAD)
        echo "HEAD detached at $COMMIT, you must specify a ref-name."
        echo "Usage: $(basename "$0") [<branch-name> | <tag-name> | <commit-sha>]"
        exit 2
    fi
    BRANCH="$NAME"
elif [ $# -eq 1 ]; then
    # resolve argument type
    if git show-ref -q --verify "refs/heads/$1"; then
        BRANCH="$1"
    elif git show-ref -q --verify "refs/tags/$1"; then
        TAG="$1"
    elif git rev-parse -q --verify "$1^{commit}" >/dev/null; then
        COMMIT="$1"
    else
        echo "Unknown ref-name $1"
        echo "Usage: $(basename "$0") [<branch-name> | <tag-name> | <commit-sha>]"
        exit 2
    fi            
else    
    echo "Usage: $(basename "$0") [<branch-name> | <tag-name> | <commit-sha>]"
    exit 2
fi


# update project 
OLD=$(git rev-parse --short HEAD)
git fetch origin
if   [ -n "$BRANCH" ]; then { git checkout $BRANCH; git merge origin/$BRANCH; }
elif [ -n "$TAG"    ]; then { git checkout $TAG;                              }
elif [ -n "$COMMIT" ]; then { git checkout $COMMIT;                           }
fi    


# check changes and send deployment notifications
NEW=$(git rev-parse --short HEAD)
HOSTNAME=$(hostname)

if [ "$OLD" = "$NEW" ]; then
    echo No changes.
elif [ "$NOTIFY_IF_ON" = "$HOSTNAME" ]; then
    if command -v sendmail >/dev/null; then
        (
        echo 'From: "Deployments '$NOTIFY_FOR_SITE'" <'$NOTIFY_TO_EMAIL'>'
        if   [ -n "$BRANCH" ]; then echo "Subject: Updated $NOTIFY_FOR_SITE, branch $BRANCH to latest ($NEW)" 
        elif [ -n "$TAG"    ]; then echo "Subject: Reset $NOTIFY_FOR_SITE to tag $TAG"
        elif [ -n "$COMMIT" ]; then echo "Subject: Reset $NOTIFY_FOR_SITE to commit $NEW"
        fi
        git log --pretty='%h %ae %s' $OLD..$NEW
        ) | sendmail -f $NOTIFY_TO_EMAIL $NOTIFY_TO_EMAIL
    fi         
fi


# update access permissions and ownership for writing files
DIRS="etc/log  etc/tmp"

for dir in $dirs; do
    dir="$PROJECT_DIR/$dir/"
    [ -d "$dir" ] || mkdir -p "$dir"
    chmod 777 "$dir"    
done

USER=username
id -u "$USER" >/dev/null 2>&1 && chown -R "$USER.$USER" "$PROJECT_DIR"
