#!/usr/bin/env bash
set -xe

unalias grep 2>/dev/null || true

host="$( hostname )"
root="/var/www/html"
www_user="root:root"
if echo $host | grep -q lambda ; then
    root="/var/www/vhosts/fe"
    www_user="www-data:www-data"
elif echo $host | grep -q dmvn ; then
    root="/srv/www/communism"
    www_user="www-data:www-data"
fi
sudo mkdir -p $root
sudo cp -a . $root/
sudo mkdir -p $root/data
sudo chmod -R 777 $root/data
sudo chown -R $www_user $root
