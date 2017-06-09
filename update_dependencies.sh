#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e

if [ -x deploy-tools/.hg ] ; then
    echo "Obtaining latest deploy-tools version"
    ( cd $my_dir/deploy-tools && hg pull && hg update )
else
    hg clone /home/mvel/work/deploy-tools deploy-tools
fi

. deploy-tools/installer/installer.sh

engine="site/xengine"

if [ -x $engine/.hg ] ; then
    echo "Obtaining latest xengine version"
    ( cd $my_dir/$engine && hg pull && hg update )
else
    hg clone /home/mvel/work/xengine $engine
fi

echo "Updating dependencies done"
