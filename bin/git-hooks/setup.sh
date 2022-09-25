#!/bin/bash
#
#  A script to automate the setup of client-side Git hooks in your repository. With Git hooks you can automate custom actions after certain
#  Git events. The Git hooks in this directory automate the most repetitive Composer tasks:
#
#   "post-checkout": Called after a successfull Git checkout operation. If the Composer lock file changed as a result of the operation the
#                    command "composer install" is executed. Otherwise the command "composer dump-autoload" is executed.
#
#   "post-merge":    Called after a successfull Git merge operation. If the Composer lock file changed as a result of the operation the
#                    command "composer install" is executed. Otherwise the command "composer dump-autoload" is executed.
#
#  Git hooks are not executed if the Git operation fails (e.g. due to a checkout/merge conflict). In this case you have to run the Composer
#  command manually as before.
#
#
#  Automated setup (recommended):
#  ------------------------------
#  Follow these steps if you prefer the automated setup of your Git hooks:
#  - Add the script and the desired Git hooks to your repository (all to the same directory). You may add your own hooks.
#  - Add the setup script to the "scripts" section of your "composer.json" (see the link to Composer scripts below):
#     "scripts": {
#         "post-install-cmd": "bash <path-to>/git-hooks/setup.sh post-checkout post-merge"
#     }
#  - Commit setup script, Git hooks and "composer.json".
#
#  This will setup the Git hooks "post-checkout" and "post-merge" in your repository each time "composer install" is called.
#
#
#  Manual setup:
#  -------------
#  Follow these steps if you prefer the manual setup of your Git hooks:
#  - Add the script and the desired Git hooks to your repository (all to the same directory). You may add your own hooks.
#  - Commit setup script and Git hooks.
#  - Call the setup script manually.
#
#    Usage:
#        setup.sh  [EVENT ...]
#
#    Arguments:
#        EVENT    One or more Git hook files to copy from the script directory to the repository's Git hook directory.
#
#
#  Links:
#  ------
#  @see  https://git-scm.com/docs/githooks/2.9.5
#  @see  https://www.digitalocean.com/community/tutorials/how-to-use-git-hooks-to-automate-development-and-deployment-tasks
#  @see  https://getcomposer.org/doc/articles/scripts.md
#
set -eu -o pipefail

#
# print a message to stderr
#
function error() {
    echo "error: $@" 1>&2
}

#
# copy a hook file
#
function copyHook() {
    SOURCE="$SCRIPT_DIR/$1"
    TARGET="$GIT_HOOK_DIR/$1"

    # copy file
    if ! cmp -s "$SOURCE" "$TARGET"; then
        \cp "$SOURCE" "$TARGET" || return $?
    fi

    # set executable permission
    chmod u+x "$TARGET" || return $?
    return 0
}


# resolve directories
CWD=$(readlink -e "$PWD")
SCRIPT_DIR=$(dirname "$0")
REPO_ROOT_DIR=$(git rev-parse --show-toplevel)
GIT_HOOK_DIR=$(git rev-parse --git-dir)'/hooks'


# normalize paths on Windows
if [ $(type -P cygpath.exe) ]; then
    CWD=$(cygpath -m "$CWD")
    SCRIPT_DIR=$(cygpath -m "$SCRIPT_DIR")
    REPO_ROOT_DIR=$(cygpath -m "$REPO_ROOT_DIR")
    GIT_HOOK_DIR=$(cygpath -m "$GIT_HOOK_DIR")
fi


# call copyHook() with the passed arguments
STATUS=
for arg in "$@"; do
    copyHook "$arg" || STATUS=$?
done


# print execution status and exit
[ -z "$STATUS" ] && STATUS="OK" || STATUS="error $STATUS"
echo "Git hooks: $STATUS"
exit
