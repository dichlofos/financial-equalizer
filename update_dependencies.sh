#!/usr/bin/env bash

my_self=$(readlink -f "$0")
my_dir="$(dirname "$my_self")"

set -e

xengine="site/xengine"

function check_repo() {
    [ -x $1/.hg ]
}


if echo $USER | grep -q mvel ; then
    # TODO(mvel): rework this utter crap
    if [ -x deploy-tools/.hg ] ; then
        echo "Obtaining latest deploy-tools version"
        ( cd $my_dir/deploy-tools && hg pull && hg update )
    else
        hg clone /home/mvel/work/deploy-tools deploy-tools
    fi
else
    if ! [ -x deploy-tools/.hg ] || ! [ -x $xengine/.hg ] ; then
        echo "Please checkout complete repo using bootstrap.sh"
        exit 1
    fi
fi

echo "Obtaining latest deploy-tools version"
( cd $my_dir/deploy-tools && hg pull && hg update )

. deploy-tools/installer/installer.sh

# from this momen we can use installer tools
print_message "Obtaining latest xengine version"
( cd $my_dir/$xengine && hg pull && hg update )

print_message "Updating dependencies done"
