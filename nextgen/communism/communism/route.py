import logging

from collections import defaultdict

import flask as f

from .communism import db
from .communism import app
from . import models as m


@app.route('/', methods=['GET', 'POST'])
def index():
    add_sheet_form = m.AddSheetForm(f.request.form)

    if f.request.method == 'POST' and add_sheet_form.validate():
        print(add_sheet_form.description.data)

        sheet = m.Sheet(
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
    sheets = m.Sheet.query.all()
    return f.render_template('sheets.html', sheets=sheets)


@app.route('/sheet/<int:sheet_id>', methods=['GET', 'POST'])
def sheet(sheet_id):
    add_member_form = m.AddMemberForm(f.request.form)
    add_currency_form = m.AddCurrencyForm(f.request.form)
    add_spending_form = m.AddSpendingForm(f.request.form)
    add_spm_form = m.AddSpendingPartialMembership(f.request.form)

    sheet_members = m.Member.query.filter(m.Member.sheet_id == sheet_id)
    sheet_spendings = m.Spending.query.filter(m.Spending.sheet_id == sheet_id)

    member_choices = [
        (sheet_member.id, sheet_member.display_name)
        for sheet_member in sheet_members
    ]

    add_spending_form.member_id.choices = member_choices
    add_spm_form.member_id.choices = [(-1, '(не выбран)')] + member_choices

    sheet_spm = m.SpendingPartialMembership.query.filter(
        m.SpendingPartialMembership.sheet_id == sheet_id
    )

    sheet_spm_by_spending = defaultdict(list)
    for pm in sheet_spm:
        sheet_spm_by_spending[pm.spending_id].append(pm)

    if f.request.method == 'POST':
        if add_member_form.validate():
            return _handle_add_member_form(sheet_id, add_member_form)

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
            spm = m.SpendingPartialMembership(
                sheet_id=sheet_id,
            )
            db.session.add(spm)
            db.session.commit()
            f.flash('Неполное участие добавлено')

    return f.render_template(
        'sheet.html',
        add_member_form=add_member_form,
        add_currency_form=add_currency_form,
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

    member = m.Member(
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
    spm = m.SpendingPartialMembership(
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
    spending = m.Spending(
        sheet_id=sheet_id,
        description=add_spending_form.description.data,
        amount=add_spending_form.amount.data,
        member_id=add_spending_form.member_id.data,
    )
    db.session.add(spending)
    db.session.commit()
    f.flash('Статья расходов добавлена')
    return f.redirect(f.url_for('sheet', sheet_id=sheet_id))
