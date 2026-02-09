import re

from django import forms
from django.core.exceptions import ValidationError
from django.utils import timezone

from .models import Event, EventCategory, EventDate, EventImage


class EventForm(forms.ModelForm):
    new_categories = forms.CharField(
        required=False,
        help_text="Enter new categories separated by commas",
        widget=forms.TextInput(
            attrs={"placeholder": "e.g. Workshop, Networking, Tech Talk"}
        ),
    )

    class Meta:
        model = Event

        fields = [
            "title",
            "location",
            "description",
            "capacity",
            "ticket_price",
            "categories",
        ]

        widgets = {
            "description": forms.Textarea(attrs={"rows": 5}),
            "capacity": forms.NumberInput(attrs={"min": 0}),
            "ticket_price": forms.NumberInput(attrs={"min": 0, "step": "0.01"}),
            "categories": forms.CheckboxSelectMultiple(),
        }

    def clean_new_categories(self):
        new_cats = self.cleaned_data.get("new_categories", "")

        if not new_cats:
            return []

        names = [name.strip() for name in new_cats.split(",") if name.strip()]

        for name in names:
            if not re.match(r"^[\w\s\-&]+$", name, re.UNICODE):
                raise ValidationError(f"Category '{name}' contains invalid characters.")

            if len(name) > 100:
                raise ValidationError(
                    f"Category '{name}' is too long (max 100 characters)."
                )
        return names

    def clean_title(self):
        title = self.cleaned_data.get("title", "")

        if not re.match(r"^[\w\s\-.,!?&()]+$", title, re.UNICODE):
            raise ValidationError("Title contains invalid characters.")

        return title

    def clean_location(self):
        location = self.cleaned_data.get("location", "")

        if not re.match(r"^[\w\s\-.,]+$", location, re.UNICODE):
            raise ValidationError("Location contains invalid characters.")

        return location

    def clean_capacity(self):
        capacity = self.cleaned_data.get("capacity")

        if capacity is None:
            return 0

        if not isinstance(capacity, int):
            try:
                capacity = int(capacity)
            except (ValueError, TypeError):
                raise ValidationError("Capacity must be a whole number.") from None

        if capacity < 0:
            raise ValidationError("Capacity cannot be negative.")
        return capacity

    def clean_ticket_price(self):
        price = self.cleaned_data.get("ticket_price")

        if price is None:
            return 0

        if price < 0:
            raise ValidationError("Ticket price cannot be negative.")

        return price


class EventDateForm(forms.ModelForm):
    class Meta:
        model = EventDate
        fields = ["start_date", "end_date"]
        widgets = {
            "start_date": forms.DateTimeInput(
                attrs={"type": "datetime-local"}, format="%Y-%m-%dT%H:%M"
            ),
            "end_date": forms.DateTimeInput(
                attrs={"type": "datetime-local"}, format="%Y-%m-%dT%H:%M"
            ),
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        start_date_field = self.fields["start_date"]
        end_date_field = self.fields["end_date"]

        if hasattr(start_date_field, "input_formats"):
            start_date_field.input_formats = ["%Y-%m-%dT%H:%M"]

        if hasattr(end_date_field, "input_formats"):
            end_date_field.input_formats = ["%Y-%m-%dT%H:%M"]

    def clean_start_date(self):
        start_date = self.cleaned_data.get("start_date")

        if start_date is None:
            raise ValidationError("Start date is required.")

        if not self.instance.pk and start_date < timezone.now():
            raise ValidationError("Start date cannot be in the past.")

        return start_date

    def clean_end_date(self):
        end_date = self.cleaned_data.get("end_date")

        if end_date is None:
            raise ValidationError("End date is required.")

        return end_date

    def clean(self):
        cleaned_data = super().clean()
        start_date = cleaned_data.get("start_date")
        end_date = cleaned_data.get("end_date")

        if start_date and end_date:
            if end_date <= start_date:
                raise ValidationError(
                    {"end_date": "End date must be after start date."}
                )

        return cleaned_data


EventDateFormSet = forms.inlineformset_factory(
    Event,
    EventDate,
    form=EventDateForm,
    extra=1,
    can_delete=True,
    min_num=1,
    validate_min=True,
)


class EventImageForm(forms.ModelForm):
    class Meta:
        model = EventImage
        fields = ["image", "image_type"]

        widgets = {
            "image": forms.FileInput(attrs={"accept": "image/*"}),
            "image_type": forms.HiddenInput(),
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.fields["image"].required = False
        self.fields["image_type"].required = False

        if not self.instance.pk:
            self.initial["image_type"] = "banner"


EventImageFormSet = forms.inlineformset_factory(
    Event, EventImage, form=EventImageForm, extra=1, can_delete=True, max_num=1
)


class EventFilterForm(forms.Form):
    search = forms.CharField(required=False)
    location = forms.CharField(required=False)
    category = forms.MultipleChoiceField(
        required=False, widget=forms.CheckboxSelectMultiple()
    )

    min_price = forms.DecimalField(required=False, min_value=0)
    max_price = forms.DecimalField(required=False, min_value=0)
    date_from = forms.DateField(
        required=False, widget=forms.DateInput(attrs={"type": "date"})
    )

    date_to = forms.DateField(
        required=False, widget=forms.DateInput(attrs={"type": "date"})
    )

    free_only = forms.BooleanField(required=False)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["category"].choices = [
            (c.slug, c.name) for c in EventCategory.objects.all()
        ]
