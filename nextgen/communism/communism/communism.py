#!/usr/bin/env python3

# all the imports

import os
# import sqlite3

import flask as f

# from flask import Flask, request, session, g, redirect, url_for, abort, \
#     render_template, flash


app = f.Flask(__name__)  # create the application instance :)
app.config.from_object(__name__)  # load config from this file , flaskr.py

# Load default config and override config from an environment variable
app.config.update(dict(
    DATABASE=os.path.join(app.root_path, 'communism.db'),
    SECRET_KEY='development key',
    USERNAME='admin',
    PASSWORD='default'
))

app.config.from_envvar('COMMUNISM_SETTINGS', silent=True)


@app.route('/')
def index():
    return f.render_template("layout.html")
