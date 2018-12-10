#!/bin/bash
#
# Example of launching a deploy script and passing parameters via the environment.
#
set -e


# configure notification
export NOTIFY_FOR_PROJECT="<project-name>"                      `# fill in the project identifier               `
export NOTIFY_ON_HOST="<hostname>"                              `# fill in the hostname to notify if deployed on`
export NOTIFY_RECEIVER="<email@domain.tld>"                     `# fill in the notification receiver            `


# resolve deploy script location and run it
deploy="$(dirname "$(readlink -e "$0")")/bin/deploy.sh"
$deploy "$@"
