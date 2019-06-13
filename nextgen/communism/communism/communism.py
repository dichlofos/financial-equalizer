#!/usr/bin/env python3

"""
Main Financial Equalizer core
"""

import datetime
import logging
# import json
# import logging
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
        backref=db.backref('member_sheet', lazy=True),
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
        return '<Member #{}: {} sheet:{} user:{}>'.format(self.id, self.display_name, self.sheet_id, self.user_id)


class AddMemberForm(wtf.Form):
    display_name = wtf.StringField('Имя участника', [wtf.validators.Length(min=1, max=25)])


class User(db.Model):
    """
    Registered user.
    Do not mix with `Member`.
    """
    id = db.Column(db.Integer, primary_key=True)
    user_name = db.Column(db.String(80), unique=True, nullable=False)
    email = db.Column(db.String(120), unique=True, nullable=False)

    def __repr__(self):
        return '<User #{}: {}, {}>'.format(self.id, self.user_name, self.email)


class Spending(db.Model):
    """
    Spending (external transaction with waste of money).
    Linked with exactly one member.
    """
    id = db.Column(db.Integer, primary_key=True)
    sheet_id = db.Column(
        db.Integer,
        db.ForeignKey('sheet.id'),
        nullable=False,
    )
    sheet = db.relationship(
        'Sheet',
        backref=db.backref('spending_sheet', lazy=True),
    )
    description = db.Column(db.String(256), nullable=False)
    amount = db.Column(db.Numeric(10, 3), nullable=False)
    date_time = db.Column(
        db.DateTime,
        nullable=True,
        default=datetime.datetime.utcnow,
    )

    # a link to particular member who spent this amount of money
    member_id = db.Column(
        db.Integer,
        db.ForeignKey('member.id'),
        nullable=False,
    )
    member = db.relationship(
        'Member',
        backref=db.backref('spending_member', lazy=True),
    )

    def __repr__(self):
        return '<Spending #{} of sheet #{}: {}, {}>'.format(
            self.id, self.sheet_id, self.description, self.amount,
            # self.date_time,
        )


class AddSpendingForm(wtf.Form):
    """
    Форма добавления статьи расходов
    """
    description = wtf.StringField(
        'Статья расхода',
        validators=[
            wtf.validators.Length(min=2, max=256),
        ]
    )
    amount = wtf.DecimalField(
        'Сумма расходов',
        validators=[
            wtf.validators.DataRequired(),
        ],
        places=2,
    )
    member_id = wtf.SelectField(
        'Участник',
        validators=[
            wtf.validators.DataRequired(),
        ],
        id='select_member',
        coerce=int,
    )


class SpendingPartialMembership(db.Model):
    """
    A partial membership in usage of particular spending.
    Links `Spending` and `Member`.
    By default, all spendings are distributed with weight of 1.0.
    """
    id = db.Column(db.Integer, primary_key=True)
    weight = db.Column(db.Numeric(10, 3), nullable=False)

    # for easy filtering
    sheet_id = db.Column(
        db.Integer,
        db.ForeignKey('sheet.id'),
        nullable=False,
    )
    sheet = db.relationship(
        'Sheet',
        backref=db.backref('spendingmembership_sheet', lazy=True),
    )
    spending_id = db.Column(
        db.Integer,
        db.ForeignKey('spending.id'),
        nullable=False,
    )
    spending = db.relationship(
        'Spending',
        backref=db.backref('spendingmembership_spending', lazy=True),
    )
    member_id = db.Column(
        db.Integer,
        db.ForeignKey('member.id'),
        nullable=False,
    )
    member = db.relationship(
        'Member',
        backref=db.backref('spendingmembership_member', lazy=True),
    )


class AddSpendingPartialMembership(wtf.Form):
    """
    Форма добавления частичного участия расходов
    """
    weight = wtf.DecimalField(
        'Доля',
        validators=[
            wtf.validators.DataRequired(),
        ],
        places=2,
    )
    member_id = wtf.SelectField(
        'Участник',
        validators=[
            wtf.validators.DataRequired(),
        ],
        id='select_member',
        coerce=int,
    )


class Currency(db.Model):
    """
    Currency for given sheet
    """
    id = db.Column(db.Integer, primary_key=True)
    sheet_id = db.Column(
        db.Integer,
        db.ForeignKey('sheet.id'),
        nullable=False,
    )
    sheet = db.relationship(
        'Sheet',
        backref=db.backref('currency_sheet', lazy=True),
    )
    sign = db.Column(db.String(64), nullable=False)
    rate = db.Column(db.Numeric(10, 3), nullable=False)

    def __repr__(self):
        return '<Currency #{} of sheet #{}: {}, {}>'.format(
            self.id, self.sheet_id, self.sign, self.rate,
            # self.date_time,
        )


class AddCurrency(wtf.Form):
    """
    Adding currency form
    """
    sign = wtf.StringField('Валюта', [wtf.validators.Length(min=2, max=40)])

    rate = wtf.DecimalField(
        'Курс',
        validators=[
            wtf.validators.DataRequired(),
        ],
        places=3,
    )


class MoneyMove(db.Model):
    """
    Money moving (internal transaction with zero-waste of money), e.g. depts
    """
    id = db.Column(db.Integer, primary_key=True)
    sheet_id = db.Column(
        db.Integer,
        db.ForeignKey('sheet.id'),
        nullable=False,
    )
    sheet = db.relationship(
        'Sheet',
        backref=db.backref('moneymove_sheet', lazy=True),
    )
    description = db.Column(db.String(256), nullable=False)
    amount = db.Column(db.Numeric(10, 3), nullable=False)
    date_time = db.Column(
        db.DateTime,
        nullable=True,
        default=datetime.datetime.utcnow,
    )
    credited_member_id = db.Column(
        db.Integer,
        db.ForeignKey('member.id'),
        nullable=False,
    )
    credited_member = db.relationship(
        'Member',
        backref=db.backref('credited_member', lazy=True),
        foreign_keys=(credited_member_id, ),
    )

    debited_member_id = db.Column(
        db.Integer,
        db.ForeignKey('member.id'),
        nullable=False,
    )
    debited_member = db.relationship(
        'Member',
        backref=db.backref('debited_member', lazy=True),
        foreign_keys=(debited_member_id, ),
    )

    def __repr__(self):
        return '<MoneyMove #{} of sheet #{}: {}, {}, debited #{}, credited #{}>'.format(
            self.id, self.sheet_id, self.description, self.amount,
            self.debited_member_id, self.credited_member_id,
        )


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
    add_spm_form = AddSpendingPartialMembership(f.request.form)

    sheet_members = Member.query.filter(Member.sheet_id == sheet_id)
    sheet_spendings = Spending.query.filter(Spending.sheet_id == sheet_id)

    member_choices = [
        (sheet_member.id, sheet_member.display_name)
        for sheet_member in sheet_members
    ]

    add_spending_form.member_id.choices = member_choices
    add_spm_form.member_id.choices = [(-1, '(не выбран)')] + member_choices

    sheet_spm = SpendingPartialMembership.query.filter(
        SpendingPartialMembership.sheet_id == sheet_id
    )

    sheet_spm_by_spending = defaultdict(list)
    for pm in sheet_spm:
        sheet_spm_by_spending[pm.spending_id].append(pm)

    if f.request.method == 'POST':
        if add_member_form.validate():
            return _handle_add_member_form(add_member_form)

        logging.error("add_spm_form.data %s", add_spm_form.member_id.data)
        if add_spm_form.validate() and add_spm_form.member_id.data > 0:  # valid identifier
            return _handle_add_spm_form(sheet_id, add_spm_form)

        if add_spending_form.validate():
            return _handle_add_spending_form(sheet_id, add_spending_form)

        logging.error("BAD HANDLER")
        # print('Validate add_spending_form FAILED')
        f.flash('Чё-то вы не то пытаетесь сделать')
        return f.redirect(f.url_for('sheet', sheet_id=sheet_id))

    if f.request.method == 'POST':
        if add_spm_form.validate():
            logging.info("working!!!")
            print("Working!!!")
            spm = SpendingPartialMembership(
                sheet_id=sheet_id,
            )
            db.session.add(spm)
            db.session.commit()
            f.flash('Неполное участие добавлено')

    return f.render_template(
        'sheet.html',
        add_member_form=add_member_form,
        add_spending_form=add_spending_form,
        add_spm_form=add_spm_form,
        sheet_members=sheet_members,
        sheet_spendings=sheet_spendings,
        sheet_spm_by_spending=sheet_spm_by_spending,
    )


def _handle_add_member_form(sheet_id, add_member_form):
    """
    `add_member_form` request handler
    """
    print(add_member_form.display_name.data)

    member = Member(
        sheet_id=sheet_id,
        display_name=add_member_form.display_name.data,
    )
    db.session.add(member)
    db.session.commit()
    f.flash('Участник добавлен')
    return f.redirect(f.url_for('sheet', sheet_id=sheet_id))


def _handle_add_spm_form(sheet_id, add_spm_form):
    """
    `add_spm_form` request handler
    """
    logging.error("Adding SpendingPartialMembership")
    # add_spm_form active
    spm = SpendingPartialMembership(
        sheet_id=sheet_id,
        spending_id=f.request.values['spending_id'],
        weight=add_spm_form.weight.data,
        member_id=add_spm_form.member_id.data,
    )
    db.session.add(spm)
    db.session.commit()
    f.flash('Неполное участие добавлено')
    return f.redirect(f.url_for('sheet', sheet_id=sheet_id))


def _handle_add_spending_form(sheet_id, add_spending_form):
    spending = Spending(
        sheet_id=sheet_id,
        description=add_spending_form.description.data,
        amount=add_spending_form.amount.data,
        member_id=add_spending_form.member_id.data,
    )
    db.session.add(spending)
    db.session.commit()
    f.flash('Статья расходов добавлена')
    return f.redirect(f.url_for('sheet', sheet_id=sheet_id))


