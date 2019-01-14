#!/usr/bin/env python3

"""
Main Financial Equalizer core
"""

import json
import logging
import os

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


class Sheet(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    description = db.Column(db.String(256), nullable=False)

    def __repr__(self):
        return '<Sheet #{}: {}>'.format(self.id, self.description)

    """
    body = db.Column(db.Text, nullable=False)
    pub_date = db.Column(db.DateTime, nullable=False,
        default=datetime.utcnow)
    """


class AddSheetForm(wtf.Form):
    description = wtf.StringField('Название листа', [wtf.validators.Length(min=1, max=256)])


class Member(db.Model):
    """
    A member of Sheet.
    Do not mix with user. Members can be linked to users.
    """
    id = db.Column(db.Integer, primary_key=True)
    display_name = db.Column(db.String(80), nullable=True)

    sheet_id = db.Column(
        db.Integer,
        db.ForeignKey('sheet.id'),
        nullable=False,
    )
    sheet = db.relationship(
        'Sheet',
        backref=db.backref('sheets', lazy=True),
    )

    user_id = db.Column(
        db.Integer,
        db.ForeignKey('user.id'),
        nullable=True,
    )
    user = db.relationship(
        'User',
        backref=db.backref('users', lazy=True),
    )

    def __repr__(self):
        return '<Member #{}: {} {} {}>'.format(self.id, self.display_name, self.sheet_id, self.user_id)


class AddMemberForm(wtf.Form):
    display_name = wtf.StringField('Имя участника', [wtf.validators.Length(min=1, max=25)])


class User(db.Model):
    # For registered users
    id = db.Column(db.Integer, primary_key=True)
    user_name = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)

    def __repr__(self):
        return '<User #{}: {}, {}>'.format(self.id, self.user_name, self.email)



class AddSpendingForm(wtf.Form):
    description = wtf.StringField('Статья расхода', [wtf.validators.Length(min=2, max=256)])
    amount = wtf.DecimalField(
        'Сумма расходов',
        [
            wtf.validators.DataRequired(),
        ],
        places=2,
    )
    # TODO(mvel): currency
    member_id = wtf.SelectField('Участник', coerce=int)


@app.route('/', methods=['GET', 'POST'])
def index():
    add_sheet_form = AddSheetForm(f.request.form)

    if f.request.method == 'POST' and add_sheet_form.validate():
        print(add_sheet_form.description.data)

        sheet = Sheet(
            description=add_sheet_form.description.data,
        )
        x = db.session.add(sheet)
        db.session.commit()
        print(repr(x))
        f.flash('Лист добавлен')
        # FIXME(mvel) hc sheet id
        sheet_id = 100
        return f.redirect(f.url_for('sheet', sheet_id=sheet_id))

    return f.render_template(
        'main.html',
        add_sheet_form=add_sheet_form,
    )


@app.route('/sheets')
def sheets():
    sheets = Sheet.query.all()
    return f.render_template('sheets.html', sheets=sheets)


@app.route('/sheet/<int:sheet_id>', methods=['GET', 'POST'])
def sheet(sheet_id):
    add_member_form = AddMemberForm(f.request.form)

    add_spending_form = AddSpendingForm(f.request.form)

    sheet_members = Member.query.filter(Member.sheet_id == sheet_id)

    logging.info('add_member_form: %s', json.dumps(add_member_form.data, indent=4))
    if f.request.method == 'POST' and add_member_form.validate():
        print(add_member_form.display_name.data)

        member = Member(
            sheet_id=sheet_id,
            display_name=add_member_form.display_name.data,
        )
        db.session.add(member)
        db.session.commit()
        f.flash('Участник добавлен')
        return f.redirect(f.url_for('sheet', sheet_id=sheet_id))

    if f.request.method == 'POST' and add_spending_form.validate():
        print(add_spending_form.description.data)
        print(add_spending_form.amount.data)

    return f.render_template(
        'sheet.html',
        add_member_form=add_member_form,
        add_spending_form=add_spending_form,
        sheet_members=sheet_members,
    )
