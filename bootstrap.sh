#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e

project_name="financial-equalizer"
destination="$1"
if [ -z "$destination" ] ; then
    destination="$project_name"
fi

# bootstrap project into current directory
hg clone /srv/hg/$project_name $destination
hg clone /srv/hg/xengine $destination/site/engine
hg clone /srv/hg/deploy-tools $destination/deploy-tools

echo "Bootstrapping '$project_name' with dependencies done to '$destination'"
