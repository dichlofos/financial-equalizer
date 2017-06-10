#!/usr/bin/env bash

hg_tip () {
    hg log -r tip | head -n1 | cut -d ':' -f3
}

( cd . && echo "financial-equalizer:$(hg_tip)" )
( cd site/xengine && echo "xengine:$(hg_tip)" )
( cd deploy-tools && echo "deploy-tools:$(hg_tip)" )
