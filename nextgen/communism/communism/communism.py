#!/usr/bin/env python3

# all the imports

import os
# import sqlite3

import flask as f
import wtforms as wtf

# from flask import Flask, request, session, g, redirect, url_for, abort, \
#     render_template, flash

# from wtforms import Form, BooleanField, StringField, PasswordField, validators


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


class AddMemberForm(wtf.Form):
    name = wtf.StringField(u'Имя участника', [wtf.validators.Length(min=1, max=25)])


class AddSpendingForm(wtf.Form):
    description = wtf.StringField(u'Статья расхода', [wtf.validators.Length(min=2, max=256)])
    amount = wtf.DecimalField(
        u'Сумма расходов',
        [
            wtf.validators.DataRequired(),
        ],
        places=2,
    )
    member_id = wtf.SelectField(u'Участник', coerce=int)


@app.route('/')
def index():
    return f.render_template("layout.html")


@app.route('/sheets')
def sheets():
    return f.render_template("content.html")
    # return f.render_template("sheets.html")


@app.route('/sheet/<int:sheet_id>', methods=['GET', 'POST'])
def sheet(sheet_id):
    form = AddMemberForm(f.request.form)
    if f.request.method == 'POST' and form.validate():
        print(form.name.data)

        # user = User(form.username.data, form.email.data,
        # form.password.data)
        # db_session.add(user)
        f.flash(u'Участник добавлен')
        return f.redirect(f.url_for('sheet', sheet_id=sheet_id))

    return f.render_template('content.html', form=form)
