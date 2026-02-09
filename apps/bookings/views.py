from django.contrib import messages
from django.contrib.auth.mixins import LoginRequiredMixin
from django.http import HttpResponseRedirect
from django.shortcuts import get_object_or_404, redirect, render
from django.views import View

from apps.events.models import Event

from .forms import CheckoutPhoneForm
from .models import EventAttendance
from .services import initiate_payment, validate_payment


class BookEventView(LoginRequiredMixin, View):
    def get(self, request, slug):
        event = get_object_or_404(Event, slug=slug, is_approved=True)

        if EventAttendance.objects.filter(user=request.user, event=event).exists():
            messages.warning(request, "You have already booked this event.")
            return redirect("event_detail", slug=slug)

        if not event.next_date:
            messages.error(
                request, "This event has no upcoming dates available for booking."
            )
            return redirect("event_detail", slug=slug)

        if event.is_sold_out:
            messages.error(request, "Sorry, this event is sold out.")
            return redirect("event_detail", slug=slug)

        if event.is_free:
            EventAttendance.objects.create(
                user=request.user, event=event, status="confirmed"
            )
            messages.success(request, "Successfully registered for the event!")
            return redirect("event_detail", slug=slug)

        form = CheckoutPhoneForm(initial={"phone": request.user.phone or ""})
        context = {
            "event": event,
            "form": form,
        }
        return render(request, "bookings/checkout.html", context)

    def post(self, request, slug):
        event = get_object_or_404(Event, slug=slug, is_approved=True)

        if EventAttendance.objects.filter(user=request.user, event=event).exists():
            messages.warning(request, "You have already booked this event.")
            return redirect("event_detail", slug=slug)

        if not event.next_date:
            messages.error(
                request, "This event has no upcoming dates available for booking."
            )
            return redirect("event_detail", slug=slug)

        if event.is_sold_out:
            messages.error(request, "Sorry, this event is sold out.")
            return redirect("event_detail", slug=slug)

        form = CheckoutPhoneForm(request.POST)
        if not form.is_valid():
            context = {
                "event": event,
                "form": form,
            }
            return render(request, "bookings/checkout.html", context)

        phone = form.cleaned_data["phone"]
        result = initiate_payment(request, event, request.user, phone)
        if result.get("already"):
            messages.warning(request, "You have already booked this event.")
            return redirect("event_detail", slug=event.slug)
        if result.get("error"):
            messages.error(request, result["error"])  # type: ignore
            return redirect("event_detail", slug=event.slug)
        return HttpResponseRedirect(result["payment_url"])  # type: ignore


class PaymentValidateView(LoginRequiredMixin, View):
    def get(self, request):
        pidx = request.GET.get("pidx")
        status = request.GET.get("status")
        purchase_order_id = request.GET.get("purchase_order_id")

        if not all([pidx, status, purchase_order_id]):
            messages.error(request, "Invalid payment response.")
            return redirect("explore")

        result = validate_payment(
            request, pidx, purchase_order_id, request.user, status=status
        )
        if result.get("ok"):
            messages.success(
                request, "Payment successful! You are registered for the event."
            )
        else:
            messages.error(request, result.get("error", "Payment verification failed."))

        try:
            parts = purchase_order_id.split("-")
            event_id = int(parts[1])
            event = get_object_or_404(Event, id=event_id)
            return redirect("event_detail", slug=event.slug)
        except Exception:
            return redirect("explore")


class CancelBookingView(LoginRequiredMixin, View):
    def post(self, request, slug):
        event = get_object_or_404(Event, slug=slug)
        attendance = get_object_or_404(EventAttendance, user=request.user, event=event)

        if event.is_free:
            attendance.delete()
        else:
            attendance.status = "cancelled"
            attendance.save()

        messages.success(request, "Booking cancelled successfully.")
        return redirect("event_detail", slug=slug)
