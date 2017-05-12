#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e

engine="site/engine"

if [ -x $engine/.hg ] ; then
    echo "Obtaining latest version"
    cd $my_dir/$engine && hg pull && hg update
else
    hg clone /home/mvel/work/xengine $engine
fi

echo "Updating dependencies done"
