#!/usr/bin/env bash
export PYTHONPATH=$PYTHONPATH:$(pwd)/communism
export FLASK_APP=communism.communism
export FLASK_DEBUG=1
flask run

