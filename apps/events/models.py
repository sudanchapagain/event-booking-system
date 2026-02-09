from decimal import Decimal

from django.core.validators import MinValueValidator
from django.db import models
from django.utils import timezone
from django.utils.text import slugify

from .services import ImageService


class EventCategory(models.Model):
    name = models.CharField(max_length=100, unique=True)
    slug = models.SlugField(max_length=100, unique=True)

    class Meta:
        verbose_name_plural = "Event Categories"
        ordering = ["name"]

    def __str__(self) -> str:
        return self.name


class Event(models.Model):
    title = models.CharField(max_length=255)
    slug = models.SlugField(max_length=255, unique=True)
    location = models.CharField(max_length=255)
    description = models.TextField(blank=True)

    organizer = models.ForeignKey(
        "accounts.User",
        on_delete=models.SET_NULL,
        null=True,
        related_name="organized_events",
    )

    capacity = models.PositiveIntegerField(default=0)
    ticket_price = models.DecimalField(
        max_digits=10,
        decimal_places=2,
        default=Decimal("0.00"),
        validators=[MinValueValidator(Decimal("0.00"))],
    )

    categories = models.ManyToManyField(
        EventCategory, related_name="events", blank=True
    )
    is_approved = models.BooleanField(default=False)
    embedding = models.JSONField(null=True, blank=True)

    created_at = models.DateTimeField(auto_now_add=True)
    updated_at = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["-created_at"]
        indexes = [
            models.Index(fields=["is_approved", "-created_at"]),
            models.Index(fields=["organizer", "is_approved"]),
            models.Index(fields=["location"]),
        ]

    def __str__(self) -> str:
        return self.title

    def save(self, *args, **kwargs):
        if not self.slug:
            base = slugify(self.title) or "event"
            slug = base
            counter = 1
            while Event.objects.filter(slug=slug).exclude(pk=self.pk).exists():
                slug = f"{base}-{counter}"
                counter += 1
            self.slug = slug
        super().save(*args, **kwargs)

    @property
    def is_free(self) -> bool:
        return self.ticket_price == Decimal("0.00")

    @property
    def available_spots(self) -> int | None:
        if self.capacity == 0:
            return None
        booked = self.attendances.filter(status="confirmed").count()
        return max(0, self.capacity - booked)

    @property
    def is_sold_out(self) -> bool:
        available = self.available_spots
        return available is not None and available == 0

    @property
    def next_date(self):
        now = timezone.now()
        return self.dates.filter(start_date__gte=now).order_by("start_date").first()

    @property
    def primary_image(self):
        return self.images.filter(image_type="banner").first()


class EventDate(models.Model):
    event = models.ForeignKey(Event, on_delete=models.CASCADE, related_name="dates")
    start_date = models.DateTimeField()
    end_date = models.DateTimeField()

    class Meta:
        ordering = ["start_date"]
        indexes = [
            models.Index(fields=["event", "end_date"]),
            models.Index(fields=["event", "start_date"]),
        ]

    def __str__(self) -> str:
        return f"{self.event.title}: {self.start_date.strftime('%Y-%m-%d %H:%M')}"


def event_image_path(instance, filename: str) -> str:
    return f"events/{instance.event.id}/{filename}"


class EventImage(models.Model):
    IMAGE_TYPES = [("banner", "Banner"), ("gallery", "Gallery")]

    event = models.ForeignKey(Event, on_delete=models.CASCADE, related_name="images")
    image = models.ImageField(upload_to=event_image_path)
    image_type = models.CharField(max_length=20, choices=IMAGE_TYPES, default="gallery")

    def save(self, *args, **kwargs):
        if self.image:
            self.image = ImageService.downscale_image(self.image)
        super().save(*args, **kwargs)

    def __str__(self) -> str:
        return f"{self.event.title} - {self.image_type}"
