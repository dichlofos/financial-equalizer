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
common_repo_path="ssh://dmvn.net//srv/git"

# bootstrap project into current directory
( cd . && ( git push $common_repo_path/$project_name.git || true ) )
( cd site/xengine && ( git push $common_repo_path/xengine.git || true ) )
( cd deploy-tools && ( git push $common_repo_path/deploy-tools.git || true ) )

print_message "'$project_name' is synced with production $common_repo_path"
