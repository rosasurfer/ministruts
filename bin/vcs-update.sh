#!/bin/sh
#

# (1) change working directory
SCRIPT_NAME=$(readlink -e "$0")
SCRIPT_DIR=$(dirname "$SCRIPT_NAME")
PROJECT_DIR=$(dirname "$SCRIPT_DIR")
cd "$PROJECT_DIR"


# (2) update project
[ ! -d ".git" ] && echo error: .git directory not found in project "$PROJECT_DIR" && exit
echo Updating $(basename "$PROJECT_DIR")...

git fetch origin                                                                  || exit
git status                                                                        || exit
git diff  --stat --ignore-space-at-eol  HEAD origin                               || exit
git reset --hard origin/legacy-no-namespaces                                      || exit
git status                                                                        || exit


# (3) check/update additional requirements: dependencies, submodules, permissions
