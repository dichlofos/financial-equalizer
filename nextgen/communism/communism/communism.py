#!/usr/bin/env python3

"""
Main Financial Equalizer core
"""

import datetime
import logging
import os
from collections import defaultdict

import flask as f

import flask_sqlalchemy as fsql

import wtforms as wtf

app = f.Flask(__name__)
app.config.from_object(__name__)
# Load default config and override config from an environment variable
app.config.update(
    dict(
        SECRET_KEY='development key',
        USERNAME='admin',
        PASSWORD='default',
        SQLALCHEMY_DATABASE_URI='sqlite:///' + os.path.join(app.root_path, 'communism.db'),
        SQLALCHEMY_TRACK_MODIFICATIONS=False,
    ),
)

app.config.from_envvar('COMMUNISM_SETTINGS', silent=True)
db = fsql.SQLAlchemy(app)
