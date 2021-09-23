from flask_wtf import FlaskForm
from govuk_frontend_wtf.wtforms_widgets import (
    GovCheckboxesInput,
    GovCheckboxInput,
    GovDateInput,
    GovFileInput,
    GovPasswordInput,
    GovRadioInput,
    GovSelect,
    GovSubmitInput,
    GovTextArea,
    GovTextInput,
)
from wtforms.fields import (
    BooleanField,
    DateField,
    DateTimeField,
    DecimalField,
    FileField,
    FloatField,
    IntegerField,
    MultipleFileField,
    PasswordField,
    RadioField,
    SelectField,
    SelectMultipleField,
    StringField,
    SubmitField,
    TextAreaField,
)
from wtforms.validators import Email, EqualTo, InputRequired, Length, Optional, Regexp


class BankDetailsForm(FlaskForm):
    name_on_the_account = StringField(
        "Name on the account",
        widget=GovTextInput(),
        validators=[InputRequired(message="Enter the name on the account")],
    )
    sort_code = StringField(
        "Sort code",
        widget=GovTextInput(),
        validators=[
            InputRequired(message="Enter a sort code"),
            Regexp(regex=r"\d{6}", message="Enter a valid sort code like 309430"),
        ],
        description="Must be 6 digits long",
    )
    account_number = StringField(
        "Account number",
        widget=GovTextInput(),
        validators=[
            InputRequired(message="Enter an account number"),
            Regexp(regex=r"\d{6,8}", message="Enter a valid account number like 00733445"),
            Length(min=6, max=8, message="Account number must be between 6 and 8 digits"),
        ],
        description="Must be between 6 and 8 digits long",
    )
    roll_number = StringField(
        "Building society roll number (if you have one)",
        widget=GovTextInput(),
        validators=[
            Optional(),
            Length(
                min=1,
                max=18,
                message="Building society roll number must be between 1 and 18 characters",
            ),
            Regexp(
                regex=r"[a-zA-Z0-9- /.]*$",
                message="Building society roll number must only include letters a to z, numbers, hyphens, spaces, forward slashes and full stops",
            ),
        ],
        description="You can find it on your card, statement or passbook",
    )
    submit = SubmitField("Continue", widget=GovSubmitInput())


class CreateAccountForm(FlaskForm):
    first_name = StringField(
        "First name",
        widget=GovTextInput(),
        validators=[InputRequired(message="Enter your first name")],
    )
    last_name = StringField(
        "Last name",
        widget=GovTextInput(),
        validators=[InputRequired(message="Enter your last name")],
    )
    date_of_birth = DateField(
        "Date of birth",
        widget=GovDateInput(),
        validators=[InputRequired(message="Enter your date of birth")],
        description="For example, 31 3 1980",
    )
    national_insurance_number = StringField(
        "National Insurance number",
        widget=GovTextInput(),
        validators=[
            InputRequired(message="Enter a National Insurance number"),
            Length(
                max=13,
                message="National Insurance number must be 13 characters or fewer",
            ),
            Regexp(
                regex=r"^[a-zA-Z]{2}\d{6}[aAbBcCdD]$",
                message="Enter a National Insurance number in the correct format",
            ),
        ],
        description="It’s on your National Insurance card, benefit letter, payslip or P60. For example, ‘QQ 12 34 56 C’.",
    )
    email_address = StringField(
        "Email address",
        widget=GovTextInput(),
        validators=[
            InputRequired(message="Enter an email address"),
            Length(max=256, message="Email address must be 256 characters or fewer"),
            Email(message="Enter an email address in the correct format, like name@example.com"),
        ],
        description="You'll need this email address to sign in to your account",
    )
    telephone_number = StringField(
        "UK telephone number",
        widget=GovTextInput(),
        validators=[
            InputRequired(message="Enter a UK telephone number"),
            Regexp(
                regex=r"[\d \+]",
                message="Enter a telephone number, like 01632 960 001, 07700 900 982 or +44 0808 157 0192",
            ),
        ],
    )
    password = PasswordField(
        "Create a password",
        widget=GovPasswordInput(),
        validators=[
            InputRequired(message="Enter a password"),
            Length(min=8, message="Password must be at least 8 characters"),
        ],
        description="Must be at least 8 characters",
    )
    confirm_password = PasswordField(
        "Confirm password",
        widget=GovPasswordInput(),
        validators=[
            InputRequired(message="Confirm your password"),
            EqualTo("password", message="Passwords must match"),
        ],
    )
    terms_and_conditions = BooleanField(
        "I agree to the terms and conditions",
        widget=GovCheckboxInput(),
        validators=[InputRequired(message="Select to confirm you agree with the terms and conditions")],
    )
    submit = SubmitField("Create account", widget=GovSubmitInput())


class CookiesForm(FlaskForm):
    functional = RadioField(
        "Do you want to accept functional cookies?",
        widget=GovRadioInput(),
        validators=[InputRequired(message="Select yes if you want to accept functional cookies")],
        choices=[("no", "No"), ("yes", "Yes")],
        default="no",
    )
    analytics = RadioField(
        "Do you want to accept analytics cookies?",
        widget=GovRadioInput(),
        validators=[InputRequired(message="Select yes if you want to accept analytics cookies")],
        choices=[("no", "No"), ("yes", "Yes")],
        default="no",
    )
    save = SubmitField("Save cookie settings", widget=GovSubmitInput())

class SatisfactionForm(FlaskForm):
    radio_field = RadioField(
        "Overall, how did you feel about the service you received today?",
        widget=GovRadioInput(),
        validators=[InputRequired(message="Please select an option")],
        choices=[("Very Satisfied", "Very Satisfied"), ("Satisfied", "Satisfied"), ("Neither satisfied or dissatsfied", "Neither satisfied or dissatisfied"), ("Dissatisifed", "Dissatisfied"), ("Very Dissatisifed", "Very Dissatisfied")],
    )

    textarea_field = TextAreaField(
        "How could we improve this service?",
        widget=GovTextArea(),
        validators=[InputRequired(message="TextAreaField is required")],
        description="Do not include any personal or financial information, for example your National Insurance or credit card numbers.",
    )

    submit_button = SubmitField("Send Feedback", widget=GovSubmitInput())

class KitchenSinkForm(FlaskForm):
    string_field = StringField(
        "StringField",
        widget=GovTextInput(),
        validators=[InputRequired(message="StringField is required")],
    )

    email_field = StringField(
        "Email address",
        widget=GovTextInput(),
        validators=[InputRequired(message="Email address is required"), Email()],
    )

    float_field = FloatField(
        "FloatField",
        widget=GovTextInput(),
        validators=[InputRequired(message="FloatField is required")],
    )

    integer_field = IntegerField(
        "IntegerField",
        widget=GovTextInput(),
        validators=[InputRequired(message="IntegerField is required")],
    )

    decimal_field = DecimalField(
        "DecimalField",
        widget=GovTextInput(),
        validators=[InputRequired(message="DecimalField is required")],
    )

    textarea_field = TextAreaField(
        "TextAreaField",
        widget=GovTextArea(),
        validators=[InputRequired(message="TextAreaField is required")],
    )

    boolean_field = BooleanField(
        "BooleanField",
        widget=GovCheckboxInput(),
        validators=[InputRequired(message="Please tick the box")],
    )

    select_field = SelectField(
        "SelectField",
        widget=GovSelect(),
        validators=[InputRequired(message="Please select an option")],
        choices=[
            ("", "Please select"),
            ("one", "One"),
            ("two", "Two"),
            ("three", "Three"),
        ],
        default="",
    )

    select_multiple_field = SelectMultipleField(
        "SelectMultipleField",
        widget=GovCheckboxesInput(),
        validators=[InputRequired(message="Please select an option")],
        choices=[("one", "One"), ("two", "Two"), ("three", "Three")],
    )

    radio_field = RadioField(
        "RadioField",
        widget=GovRadioInput(),
        validators=[InputRequired(message="Please select an option")],
        choices=[("one", "One"), ("two", "Two"), ("three", "Three")],
    )

    file_field = FileField(
        "FileField",
        widget=GovFileInput(),
        validators=[InputRequired(message="Please upload a file")],
    )

    multiple_file_field = MultipleFileField(
        "MultipleFileField",
        widget=GovFileInput(multiple=True),
        validators=[InputRequired(message="Please upload a file")],
    )

    password_field = PasswordField(
        "PasswordField",
        widget=GovPasswordInput(),
        validators=[
            InputRequired("Password is required"),
            EqualTo(
                "password_retype_field",
                message="Please ensure both password fields match",
            ),
        ],
    )

    password_retype_field = PasswordField(
        "Re-type your password",
        widget=GovPasswordInput(),
        validators=[InputRequired("Please retype your password")],
    )

    date_field = DateField(
        "DateField",
        widget=GovDateInput(),
        validators=[InputRequired(message="DateField is required")],
    )

    date_time_field = DateTimeField(
        "DateTimeField",
        widget=GovDateInput(),
        validators=[InputRequired(message="DateTimeField is required")],
    )

    submit_button = SubmitField("SubmitField", widget=GovSubmitInput())
