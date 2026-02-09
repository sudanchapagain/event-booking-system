from django.contrib import messages
from django.contrib.auth.mixins import LoginRequiredMixin, UserPassesTestMixin
from django.db.models import Count, Prefetch, Q, Sum
from django.http import HttpRequest
from django.shortcuts import get_object_or_404, redirect
from django.views import View
from django.views.generic import ListView, TemplateView

from apps.bookings.models import EventAttendance, TicketSale
from apps.events.models import Event


class OrganizerRequiredMixin(LoginRequiredMixin, UserPassesTestMixin):
    request: HttpRequest

    def test_func(self):
        return self.request.user.is_organizer or self.request.user.is_site_admin


class AdminRequiredMixin(UserPassesTestMixin):
    def test_func(self):
        return self.request.user.is_authenticated and self.request.user.is_site_admin


class DashboardView(OrganizerRequiredMixin, TemplateView):
    template_name = "dashboard/home.html"

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["active_tab"] = "dashboard"
        user = self.request.user

        if user.is_site_admin:
            events = Event.objects.all()
        else:
            events = Event.objects.filter(organizer=user)

        context["total_events"] = events.count()
        context["approved_events"] = events.filter(is_approved=True).count()
        context["pending_events"] = events.filter(is_approved=False).count()
        context["upcoming_events_count"] = events.filter(is_approved=True).count()

        sales = TicketSale.objects.filter(event__in=events)
        context["total_revenue"] = (
            sales.aggregate(total=Sum("total_price"))["total"] or 0
        )
        context["total_tickets_sold"] = (
            sales.aggregate(total=Sum("quantity"))["total"] or 0
        )
        context["total_attendees"] = EventAttendance.objects.filter(
            event__in=events, status="confirmed"
        ).count()

        # Optimize recent_events with prefetch to avoid N+1 queries
        confirmed_attendances = Prefetch(
            "attendances", EventAttendance.objects.filter(status="confirmed")
        )
        context["recent_events"] = (
            events.prefetch_related("categories", "images", confirmed_attendances)
            .annotate(
                confirmed_count=Count(
                    "attendances", filter=Q(attendances__status="confirmed")
                )
            )
            .order_by("-created_at")[:5]
        )

        return context


class DashboardBookingsView(OrganizerRequiredMixin, ListView):
    template_name = "dashboard/bookings.html"
    context_object_name = "bookings"
    paginate_by = 20

    def get_queryset(self):
        user = self.request.user
        if user.is_site_admin:
            queryset = EventAttendance.objects.all()
        else:
            queryset = EventAttendance.objects.filter(event__organizer=user)

        event_id = self.request.GET.get("event")
        if event_id:
            queryset = queryset.filter(event_id=event_id)

        return (
            queryset.select_related("user", "event", "event__organizer")
            .prefetch_related("event__categories", "event__images")
            .order_by("-registered_at")
        )

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["active_tab"] = "bookings"
        user = self.request.user

        if user.is_site_admin:
            context["events"] = Event.objects.all()
        else:
            context["events"] = Event.objects.filter(organizer=user)

        context["selected_event"] = self.request.GET.get("event")
        return context


class DashboardSalesView(OrganizerRequiredMixin, TemplateView):
    template_name = "dashboard/sales.html"

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["active_tab"] = "sales"
        user = self.request.user

        if user.is_site_admin:
            events = Event.objects.all()
        else:
            events = Event.objects.filter(organizer=user)

        context["event_sales"] = (
            events.prefetch_related("categories", "images")
            .annotate(
                total_revenue=Sum("sales__total_price"),
                tickets_sold=Sum("sales__quantity"),
                attendee_count=Count(
                    "attendances", filter=Q(attendances__status="confirmed")
                ),
            )
            .order_by("-total_revenue")
        )

        context["total_revenue"] = (
            TicketSale.objects.filter(event__in=events).aggregate(
                total=Sum("total_price")
            )["total"]
            or 0
        )

        context["total_sales"] = (
            TicketSale.objects.filter(event__in=events).aggregate(
                total=Sum("quantity")
            )["total"]
            or 0
        )

        context["events_with_sales"] = (
            events.filter(sales__isnull=False).distinct().count()
        )

        context["sales"] = (
            TicketSale.objects.filter(event__in=events)
            .select_related("user", "event", "event__organizer")
            .prefetch_related("event__categories", "event__images")
            .order_by("-purchased_at")[:20]
        )

        return context


class DashboardModerationView(AdminRequiredMixin, TemplateView):
    template_name = "dashboard/moderation.html"

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["active_tab"] = "moderation"
        context["pending_events"] = (
            Event.objects.filter(is_approved=False)
            .select_related("organizer")
            .prefetch_related("categories", "images")
        )
        context["reported_content"] = []
        return context


class DashboardPostsView(AdminRequiredMixin, ListView):
    template_name = "dashboard/posts.html"
    context_object_name = "all_events"
    paginate_by = 20

    def get_queryset(self):
        return (
            Event.objects.all()
            .select_related("organizer")
            .prefetch_related("categories", "images")
            .order_by("-created_at")
        )

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["active_tab"] = "posts"
        return context


class ApproveEventView(AdminRequiredMixin, View):
    def post(self, request, event_id):
        event = get_object_or_404(Event, id=event_id)
        event.is_approved = True
        event.save()
        messages.success(request, f"Event '{event.title}' has been approved.")
        return redirect("dashboard_moderation")


class RejectEventView(AdminRequiredMixin, View):
    def post(self, request, event_id):
        event = get_object_or_404(Event, id=event_id)
        title = event.title
        event.delete()
        messages.success(request, f"Event '{title}' has been rejected and removed.")
        return redirect("dashboard_moderation")
