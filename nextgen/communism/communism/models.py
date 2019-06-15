import wtforms as wtf

import datetime


from .communism import db


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
        )


class AddCurrencyForm(wtf.Form):
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
