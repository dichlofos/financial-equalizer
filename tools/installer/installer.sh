#!/usr/bin/env bash

deploy_service() {

    service_name="$1"
    target_server="$2"
    mode="$3"

    production_workdir="$HOME/deploy/$1"

    # prepare repo on remote
    ssh $target_server bash -xe <<EOF

        if ! [ -e $production_workdir ] ; then
            mkdir -p $production_workdir
            cd $production_workdir
            hg init
        fi
EOF

    hg push ssh://$target_server/$production_workdir
    ssh $target_server bash -xe <<EOF
        cd $production_workdir
        hg up
        sudo ./install.sh $mode
EOF

}
