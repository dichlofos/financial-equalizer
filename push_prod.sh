#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

. deploy-tools/installer/installer.sh

set -e

project_name="financial-equalizer"
destination="$1"
if [ -z "$destination" ] ; then
    destination="$project_name"
fi

# push to production host
common_repo_path="ssh://dmvn.net//srv/hg"

# bootstrap project into current directory
hg push $common_repo_path/$project_name
( cd site/xengine && hg push $common_repo_path/xengine )
( cd deploy-tools && hg push $common_repo_path/deploy-tools )

print_message "'$project_name' is synced with production $common_repo_path"
