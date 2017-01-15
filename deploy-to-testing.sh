#!/usr/bin/env bash
hg push ssh://dmvn.net//home/mvel/work/financial-equalizer
ssh dmvn.net bash -xe <<EOF
cd $HOME/work/financial-equalizer
hg up
sudo ./local-install.sh testing
EOF
