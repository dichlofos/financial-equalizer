#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e

project_name="financial-equalizer"
destination="$1"
if [ -z "$destination" ] ; then
    destination="$project_name"
fi

common_repo_path="ssh://hg@bitbucket.org/dichlofos"
if echo $(hostname) | grep -q dmvn ; then
    # production host
    common_repo_path="/srv/hg"
fi

# bootstrap project into current directory
hg clone $common_repo_path/$project_name $destination
hg clone $common_repo_path/xengine $destination/site/xengine
hg clone $common_repo_path/deploy-tools $destination/deploy-tools

. $destination/deploy-tools/installer/installer.sh

fix_hgrc $destination $project_name
fix_hgrc $destination/site/xengine xengine
fix_hgrc $destination/deploy-tools deploy-tools

echo "Bootstrapping '$project_name' with dependencies done to '$destination'"
