from django.core.validators import MinValueValidator
from django.db import models


class EventAttendance(models.Model):
    STATUS_CHOICES = [
        ("confirmed", "Confirmed"),
        ("cancelled", "Cancelled"),
        ("pending", "Pending"),
    ]

    user = models.ForeignKey(
        "accounts.User",
        on_delete=models.CASCADE,
        related_name="attendances",
    )

    event = models.ForeignKey(
        "events.Event",
        on_delete=models.CASCADE,
        related_name="attendances",
    )

    status = models.CharField(
        max_length=20, choices=STATUS_CHOICES, default="confirmed"
    )

    registered_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ["user", "event"]
        ordering = ["-registered_at"]
        indexes = [
            models.Index(fields=["event", "status"]),
        ]

    def __str__(self) -> str:
        return f"{self.user.email} - {self.event.title}"


class TicketSale(models.Model):
    user = models.ForeignKey(
        "accounts.User",
        on_delete=models.SET_NULL,
        null=True,
        related_name="ticket_purchases",
    )

    event = models.ForeignKey(
        "events.Event",
        on_delete=models.CASCADE,
        related_name="sales",
    )

    quantity = models.PositiveIntegerField(validators=[MinValueValidator(1)])
    total_price = models.DecimalField(max_digits=10, decimal_places=2)
    transaction_id = models.CharField(max_length=255, blank=True)
    customer_phone = models.CharField(
        max_length=10, blank=True, help_text="Phone number used during checkout"
    )
    purchased_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["-purchased_at"]
        indexes = [
            models.Index(fields=["event", "-purchased_at"]),
        ]

    def __str__(self) -> str:
        user_email = self.user.email if self.user else "Deleted User"
        return f"{user_email} - {self.event.title} ({self.quantity} tickets)"
