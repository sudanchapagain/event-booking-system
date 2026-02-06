import re

from django import forms
from django.contrib.auth.forms import AuthenticationForm
from django.core.exceptions import ValidationError

from .models import User


class LoginForm(AuthenticationForm):
    username = forms.EmailField(
        label="Email",
        widget=forms.EmailInput(attrs={"autofocus": True}),
    )


class SignupForm(forms.ModelForm):
    name = forms.CharField(
        label="Name",
        max_length=150,
        help_text="Your display name",
    )
    password = forms.CharField(
        label="Password",
        widget=forms.PasswordInput,
        strip=False,
    )
    phone = forms.CharField(
        label="Phone Number",
        max_length=10,
        required=True,
        help_text="phone number (98/97 + 8 digits)",
        widget=forms.TextInput(
            attrs={
                "placeholder": "9800000000",
                "pattern": "^(98|97)\\d{8}$",
            }
        ),
    )

    class Meta:
        model = User
        fields = ["email"]

    def clean_email(self):
        email = self.cleaned_data.get("email")

        if User.objects.filter(email=email).exists():
            raise ValidationError("user with this email already exists.")

        return email

    def clean_password(self):
        password = self.cleaned_data.get("password", "")

        if len(password) < 8:
            raise ValidationError("Password must be at least 8 characters long.")

        if not re.search(r"[A-Z]", password):
            raise ValidationError(
                "Password must contain at least one uppercase letter."
            )

        if not re.search(r"[a-z]", password):
            raise ValidationError(
                "Password must contain at least one lowercase letter."
            )

        if not re.search(r"\d", password):
            raise ValidationError("Password must contain at least one digit.")

        if not re.search(r"[!@#$%^&*(),.?\":{}|<>]", password):
            raise ValidationError(
                "Password must contain at least one special character."
            )

        return password

    def save(self, commit=True):
        user = super().save(commit=False)
        user.set_password(self.cleaned_data["password"])

        name = self.cleaned_data.get("name", "")
        user.first_name = name
        user.username = user.email.split("@")[0]
        user.phone = self.cleaned_data.get("phone", "")

        if commit:
            user.save()

        return user  # TODO:


class UserSettingsForm(forms.ModelForm):
    class Meta:
        model = User
        fields = ["first_name", "email", "phone"]
        labels = {
            "first_name": "Name",
            "phone": "Phone Number",
        }
        help_texts = {
            "phone": "phone number (98/97 + 8 digits)",
        }
