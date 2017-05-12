#!/usr/bin/env bash

deploy_service() {
    service_name="$1"
    target_server="$2"
    work_dir="deploy/$1"
    production_workdir=$HOME/$work_dir

    # prepare repo on remote
    ssh $target_server bash -xe <<EOF
        if ! [ -e $production_workdir ] ; then
            mkdir $production_workdir
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
