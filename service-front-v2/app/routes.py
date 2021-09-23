import json
import os

from flask import flash, make_response, redirect, render_template, request, url_for
from flask_wtf.csrf import CSRFError
from werkzeug.exceptions import NotFound

from app import app
from app.forms import BankDetailsForm, CookiesForm, CreateAccountForm, KitchenSinkForm, SatisfactionForm

@app.route("/")
def index():
    components = os.listdir("govuk_components")
    components.sort()

    return render_template("index.html", components=components)


@app.route("/components/<string:component>")
def component(component):
    try:
        with open("govuk_components/{}/fixtures.json".format(component)) as json_file:
            fixtures = json.load(json_file)
    except FileNotFoundError:
        raise NotFound

    return render_template("component.html", fixtures=fixtures)

@app.route("/feedback")
def feedback():
    return render_template("feedback.html")

@app.route("/flask-accessibility")
def accessibility():
    return render_template("accessibility.html")

@app.errorhandler(404)
def not_found(error):
    return render_template("404.html"), 404


@app.errorhandler(500)
def internal_server(error):
    return render_template("500.html"), 500

@app.route("/forms/bank-details", methods=["GET", "POST"])
def bank_details():
    form = BankDetailsForm()
    if form.validate_on_submit():
        flash("Form successfully submitted", "success")
        return redirect(url_for("index"))
    return render_template("bank_details.html", form=form)


@app.route("/forms/create-account", methods=["GET", "POST"])
def create_account():
    form = CreateAccountForm()
    if form.validate_on_submit():
        flash("Form successfully submitted", "success")
        return redirect(url_for("index"))
    return render_template("create_account.html", form=form)


@app.route("/forms/kitchen-sink", methods=["GET", "POST"])
def kitchen_sink():
    form = KitchenSinkForm()
    if form.validate_on_submit():
        flash("Form successfully submitted", "success")
        return redirect(url_for("index"))
    return render_template("kitchen_sink.html", form=form)

@app.route("/forms/satisfaction", methods=["GET", "POST"])
def satisfaction():
    form = SatisfactionForm()
    if form.validate_on_submit():
        flash("Form successfully submitted", "success")
        return redirect(url_for("index"))
    return render_template("satisfaction.html", form=form)
