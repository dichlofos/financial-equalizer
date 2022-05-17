#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e

project_name="financial-equalizer"
destination="$1"
if [ -z "$destination" ] ; then
    destination="$project_name"
fi

common_repo_path="git@github.com:dichlofos/"
if echo $(hostname) | grep -q dmvn ; then
    # production host
    common_repo_path="/srv/git"
fi

# bootstrap project into current directory
git clone $common_repo_path/$project_name.git $destination
( cd $destination; git checkout ec4f02533697da3447ce43c6014e209ef7e59639 ; cd - )
git clone $common_repo_path/xengine.git $destination/site/xengine
git clone $common_repo_path/deploy-tools.git $destination/deploy-tools

. $destination/deploy-tools/installer/installer.sh

# fix_hgrc $destination $project_name
# fix_hgrc $destination/site/xengine xengine
# fix_hgrc $destination/deploy-tools deploy-tools

echo "Bootstrapping '$project_name' with dependencies done to '$destination'"
