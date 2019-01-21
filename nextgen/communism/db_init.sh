#!/usr/bin/env bash
export PYTHONPATH=$PYTHONPATH:$(pwd)/communism
export FLASK_APP=communism.communism
export FLASK_DEBUG=1
flask shell <<EOF
from communism import communism as c
c.db.create_all()
quit
EOF
