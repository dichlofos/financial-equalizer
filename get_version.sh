#!/usr/bin/env bash

hg_tip () {
    hg log -r tip | head -n1 | cut -d ':' -f3
}

echo "main:$(hg_tip)"

( cd site/engine && echo "xengine:$(hg_tip)" )
