#!/usr/bin/env python3

"""
Main Financial Equalizer core
"""

import os
import json

import flask as f

import wtforms as wtf


app = f.Flask(__name__)  # create the application instance :)
app.config.from_object(__name__)  # load config from this file , flaskr.py

# Load default config and override config from an environment variable
app.config.update(
    dict(
        DATABASE=os.path.join(app.root_path, 'communism.db'),
        SECRET_KEY='development key',
        USERNAME='admin',
        PASSWORD='default',
    ),
)

app.config.from_envvar('COMMUNISM_SETTINGS', silent=True)


class AddMemberForm(wtf.Form):
    name = wtf.StringField('Имя участника', [wtf.validators.Length(min=1, max=25)])


class AddSpendingForm(wtf.Form):
    description = wtf.StringField('Статья расхода', [wtf.validators.Length(min=2, max=256)])
    amount = wtf.DecimalField(
        'Сумма расходов',
        [
            wtf.validators.DataRequired(),
        ],
        places=2,
    )
    member_id = wtf.SelectField('Участник', coerce=int)


@app.route('/')
def index():
    return f.render_template('layout.html')


@app.route('/sheets')
def sheets():
    return f.render_template('sheets.html')
    # return f.render_template("sheets.html")


@app.route('/sheet/<int:sheet_id>', methods=['GET', 'POST'])
def sheet(sheet_id):
    add_member_form = AddMemberForm(f.request.form)

    add_spending_form = AddSpendingForm(f.request.form)
    print(json.dumps(add_member_form.data, indent=4))
    if f.request.method == 'POST' and add_member_form.validate():
        print(add_member_form.name.data)

        # user = User(form.username.data, form.email.data,
        # form.password.data)
        # db_session.add(user)
        f.flash('Участник добавлен')
        return f.redirect(f.url_for('sheet', sheet_id=sheet_id))

    return f.render_template(
        'sheet.html',
        add_member_form=add_member_form,
        add_spending_form=add_spending_form,
    )
