#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e
project_name="financial-equalizer"

# bootstrap project into current directory
hg clone ssh://hg@bitbucket.org/dichlofos/$project_name .
hg clone ssh://hg@bitbucket.org/dichlofos/xengine site/engine
echo "Bootstrapping $project_name with dependencies done"
