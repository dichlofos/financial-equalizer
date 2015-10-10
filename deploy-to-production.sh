#!/usr/bin/env bash
ssh -A dmvn.net bash -xe <<EOF
cd $HOME/work/financial-equalizer
hg pull
hg up
sudo ./local-install.sh
EOF
