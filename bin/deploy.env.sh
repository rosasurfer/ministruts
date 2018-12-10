#!/bin/bash
#
# Example of launching a deploy script and passing the configuration via the environment.
#
set -e


# notification configuration 
export NOTIFY_FOR_PROJECT="<project-name>"                          `# fill in the project identifier               `
export NOTIFY_ON_HOST="<hostname>"                                  `# fill in the hostname to notify if deployed on`
export NOTIFY_RECEIVER="<email@domain.tld>"                         `# fill in the notification receiver            `


# resolve the real deploy script location and run it
deploy="$(dirname "$(readlink -e "$0")")/bin/deploy.sh"             `# adjust to the deploy script's real location  `
$deploy "$@"
