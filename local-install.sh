#!/usr/bin/env bash
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
sudo cp -a . $root/
sudo mkdir -p $root/data
sudo chmod -R 777 $root/data
sudo chown -R $www_user $root
