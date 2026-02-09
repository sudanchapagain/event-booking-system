import requests
from django.conf import settings
from django.urls import reverse

from apps.bookings.models import EventAttendance, TicketSale


def initiate_payment(request, event, user, customer_phone):
    if not getattr(settings, "KHALTI_SECRET_KEY", None):
        return {"error": "Payment system is not configured."}

    if EventAttendance.objects.filter(
        user=user, event=event, status="confirmed"
    ).exists():
        return {"already": True}

    payload = {
        "return_url": request.build_absolute_uri(reverse("payment_validate")),
        "website_url": request.build_absolute_uri("/"),
        "amount": int(event.ticket_price * 100),
        "purchase_order_id": f"event-{event.id}-user-{user.id}",
        "purchase_order_name": event.title[:50],
        "customer_info": {
            "name": user.display_name,
            "email": user.email,
            "phone": customer_phone,
        },
    }

    try:
        response = requests.post(
            f"{settings.KHALTI_BASE_URL}epayment/initiate/",
            json=payload,
            headers={"Authorization": f"Key {settings.KHALTI_SECRET_KEY}"},
            timeout=30,
        )
        print(response.status_code, response.text)
        response.raise_for_status()
        data = response.json()
        if "payment_url" in data:
            EventAttendance.objects.get_or_create(
                user=user, event=event, defaults={"status": "pending"}
            )
            request.session["checkout_phone"] = customer_phone
            return {"payment_url": data["payment_url"]}
        return {"error": "Failed to initiate payment."}
    except requests.RequestException:
        return {"error": "Payment service is unavailable."}


def validate_payment(request, pidx, purchase_order_id, user, status=None):
    try:
        parts = purchase_order_id.split("-")
        event_id = int(parts[1])
        user_id = int(parts[3])
    except (IndexError, ValueError):
        return {"error": "Invalid payment reference."}

    if user_id != user.id:
        return {"error": "Payment verification failed."}

    if status is not None and status != "Completed":
        EventAttendance.objects.filter(
            user=user, event_id=event_id, status="pending"
        ).delete()
        return {"error": "Payment was not completed."}

    try:
        response = requests.post(
            f"{settings.KHALTI_BASE_URL}epayment/lookup/",
            json={"pidx": pidx},
            headers={"Authorization": f"Key {settings.KHALTI_SECRET_KEY}"},
            timeout=30,
        )
        response.raise_for_status()
        data = response.json()
    except requests.RequestException:
        return {"error": "Could not verify payment."}

    from apps.events.models import Event

    event = Event.objects.filter(id=event_id).first()
    if not event:
        return {"error": "Event not found."}

    if data.get("status") == "Completed":
        attendance = EventAttendance.objects.filter(user=user, event=event).first()
        if attendance:
            attendance.status = "confirmed"
            attendance.save()
        else:
            EventAttendance.objects.create(user=user, event=event, status="confirmed")

        TicketSale.objects.create(
            user=user,
            event=event,
            quantity=1,
            total_price=event.ticket_price,
            transaction_id=pidx,
            customer_phone=(request.session.pop("checkout_phone", user.phone or "")),
        )
        return {"ok": True}

    EventAttendance.objects.filter(user=user, event=event, status="pending").delete()
    return {"error": "Payment verification failed."}
