import re

from django import forms
from django.core.exceptions import ValidationError


def validate_nepal_phone(value: str) -> None:
    pattern = r"^(98|97)\d{8}$"
    if not re.match(pattern, value):
        raise ValidationError(
            "Phone number must start with 98 or 97 and be exactly 10 digits."
        )


class CheckoutPhoneForm(forms.Form):
    phone = forms.CharField(
        max_length=10,
        required=True,
        validators=[validate_nepal_phone],
        widget=forms.TextInput(
            attrs={
                "class": "form-input",
                "placeholder": "9800000000",
                "pattern": "^(98|97)\\d{8}$",
                "title": "Phone number must start with 98 or 97 and be exactly 10 digits",
            }
        ),
        label="Phone Number",
        help_text="This phone number will be used for your transaction.",
    )
