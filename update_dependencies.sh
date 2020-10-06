#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e

xengine="site/xengine"

if echo $USER | grep -q mvel ; then
    if [ -x deploy-tools/.git ] ; then
        echo "Obtaining latest deploy-tools version"
        ( cd $my_dir/deploy-tools && git pull )
    else
        git clone git@github.com:dichlofos/deploy-tools.git deploy-tools
    fi
else
    if ! [ -x deploy-tools/.git ] || ! [ -x $xengine/.git ] ; then
        echo "Please checkout complete repo using bootstrap.sh"
        exit 1
    fi
fi

echo "Obtaining latest deploy-tools version"
( cd $my_dir/deploy-tools && git pull )

. deploy-tools/installer/installer.sh

# from this moment we can use installer tools
print_message "Obtaining latest xengine version"
( cd $my_dir/$xengine && git pull )

print_message "Updating dependencies done"
