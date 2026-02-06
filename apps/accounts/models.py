import re

from django.contrib.auth.models import AbstractUser
from django.core.exceptions import ValidationError
from django.db import models


def validate_nepal_phone(value: str) -> None:
    pattern = r"^(98|97)\d{8}$"
    if not re.match(pattern, value):
        raise ValidationError(
            "Phone number must start with 98 or 97 and be exactly 10 digits."
        )


class User(AbstractUser):
    email = models.EmailField(unique=True)
    phone = models.CharField(
        max_length=10,
        blank=True,
        validators=[validate_nepal_phone],
    )

    is_organizer = models.BooleanField(default=False)
    is_site_admin = models.BooleanField(default=False)

    USERNAME_FIELD = "email"
    REQUIRED_FIELDS = ["username"]

    def __str__(self) -> str:
        return self.email

    @property
    def display_name(self) -> str:
        return self.first_name if self.first_name else self.username
