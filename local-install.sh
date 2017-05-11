#!/usr/bin/env bash

# Deployment script.
# Contains configuration for all deploy places

program_name="Financial Equalizer"

if [[ "$1" == "--help" ]] || [[ "$1" == "-h" ]] ; then
    echo "$program_name deployment script"
    echo "Usage: $0 [(empty)|production|testing]"
    exit 0
fi

set -xe

mode="$1"

unalias grep 2>/dev/null || true

host="$( hostname )"
root="/var/www/html"
www_user="root:root"
if echo $host | grep -q lambda ; then
    root="/var/www/vhosts/fe"
    www_user="www-data:www-data"
elif echo $host | grep -q blackbox ; then
    root="/var/www/vhosts/fe"
    www_user="www-data:www-data"
elif echo $host | grep -q dmvn ; then
    if [ "$mode" = "production" ] ; then
        root="/srv/www/communism"
    elif [ "$mode" = "testing" ] ; then
        root="/srv/www/communism.test"
    else
        echo "Invalid mode '$mode'. Specify it, please"
        exit 1
    fi
    www_user="www-data:www-data"
fi
sudo mkdir -p $root
sudo cp -a ./site/* $root/
sudo mkdir -p $root/data
sudo chmod -R 777 $root/data
sudo chown -R $www_user $root

set +x

echo "$program_name was successfully deployed to '$root' in mode '$mode'"
