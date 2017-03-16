#!/usr/bin/bash
#
# Git hook to run "composer install" if the file "composer.lock" was changed.
#
# Notes:
# ------
#  - This hook is not executed by Eclipse.
#
#  - error: The hook is not executed if merging failed due to conflicts.
#  - error: The hook is not executed if you run pull with --rebase option.
#
#  - post-rewrite works for me. As side effect it is also executed on amend.
#    @see  http://stackoverflow.com/questions/21307744/git-post-rebase-hook
#
#  - I would check updated submodules too:
#    check_run .gitmodules "git submodule init && git submodule update"


# check for and execute an existing user hook
if [ -f "$0.user" ]; then
   "$0.user" "$@"
fi


# check existence of Composer
result=$(type -P composer.phar 2> /dev/null)
if [ "$result" == "" ]; then
    result=$(type -P composer 2> /dev/null)
    [ "$result" == "" ] && echo " * error: could not find Composer" && exit 1
fi


# get changed file names
changed_files=$(git diff-tree -r --name-only --no-commit-id HEAD@{1} HEAD)


check_run() {
    [ -f "$1" ]                                     && \
    echo "$changed_files" | grep --quiet -Fx "$1"   && \
    echo " * changes detected in $1"                && \
    echo " * running $2"                            && \
    eval "$2 --ansi"
}


# run command if file has changed
check_run 'composer.lock' 'composer install'
