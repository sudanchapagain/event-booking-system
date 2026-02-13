from decimal import Decimal
from django.db.models import OuterRef, Subquery, IntegerField

from django import forms
from django.contrib import messages
from django.contrib.auth.mixins import LoginRequiredMixin, UserPassesTestMixin
from django.db.models import Count, Prefetch, Q
from django.db.models.functions import Greatest
from django.contrib.postgres.search import TrigramSimilarity
from django.shortcuts import redirect
from django.urls import reverse, reverse_lazy
from django.utils import timezone
from django.utils.text import slugify
from django.views.generic import (
    CreateView,
    DeleteView,
    DetailView,
    ListView,
    UpdateView,
)

from apps.bookings.models import EventAttendance

from .forms import (
    EventDateForm,
    EventDateFormSet,
    EventFilterForm,
    EventForm,
    EventImageFormSet,
)
from .models import Event, EventCategory, EventDate
from .similarity import get_similar_events, update_event_embedding


class EventListView(ListView):
    template_name = "events/explore.html"
    model = Event
    context_object_name = "events"
    paginate_by = 12

    def get_queryset(self):
        now = timezone.now()

        future_dates = Prefetch(
            "dates", EventDate.objects.filter(end_date__gte=now).order_by("start_date")
        )

        confirmed_attendances = Prefetch(
            "attendances", EventAttendance.objects.filter(status="confirmed")
        )

        attendance_count_subq = (
            EventAttendance.objects.filter(event=OuterRef("pk"), status="confirmed")
            .order_by()
            .values("event")
            .annotate(c=Count("id"))
            .values("c")
        )

        queryset = (
            Event.objects.filter(is_approved=True)
            .select_related("organizer")
            .prefetch_related(
                "categories", future_dates, "images", confirmed_attendances
            )
            .annotate(
                confirmed_count=Subquery(
                    attendance_count_subq, output_field=IntegerField()
                )
            )
        )

        search = self.request.GET.get("q") or self.request.GET.get("search")
        if search:
            search = search.strip()
            queryset = (
                queryset.annotate(
                    similarity=Greatest(
                        TrigramSimilarity("title", search),
                        TrigramSimilarity("location", search),
                        TrigramSimilarity("description", search),
                    )
                )
                .filter(similarity__gte=0.12)
                .distinct()
                .order_by("-similarity")
            )

        location = self.request.GET.get("location")
        if location:
            location = location.strip()
            queryset = queryset.filter(location__icontains=location)

        categories = [c for c in self.request.GET.getlist("category") if c.strip()]
        if not categories:
            single_cat = self.request.GET.get("category")
            if single_cat and single_cat.strip():
                categories = [single_cat.strip()]

        if categories:
            slug_filters = []
            id_filters = []
            for cat in categories:
                cat = cat.strip()
                try:
                    id_filters.append(int(cat))
                except (ValueError, TypeError):
                    slug_filters.append(cat)

            category_filter = Q()
            if slug_filters:
                category_filter |= Q(categories__slug__in=slug_filters)
            if id_filters:
                category_filter |= Q(categories__id__in=id_filters)

            if category_filter:
                queryset = queryset.filter(category_filter).distinct()

        min_price = self.request.GET.get("min_price")
        if min_price:
            queryset = queryset.filter(ticket_price__gte=Decimal(min_price))

        max_price = self.request.GET.get("max_price")
        if max_price:
            queryset = queryset.filter(ticket_price__lte=Decimal(max_price))

        free_only = self.request.GET.get("free_only") or self.request.GET.get("free")
        if free_only:
            queryset = queryset.filter(ticket_price=Decimal("0.00"))

        date_from = self.request.GET.get("date_from")
        if date_from:
            queryset = queryset.filter(dates__start_date__date__gte=date_from)

        date_to = self.request.GET.get("date_to")
        if date_to:
            queryset = queryset.filter(dates__start_date__date__lte=date_to)

        if "category_filter" in locals() and category_filter:
            qs = queryset.distinct()
        else:
            qs = queryset

        if not qs.query.order_by:
            qs = qs.order_by("-confirmed_count", "id")

        return qs

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["filter_form"] = EventFilterForm(self.request.GET or None)
        context["categories"] = EventCategory.objects.all()
        return context

    def render_to_response(self, context, **response_kwargs):
        return super().render_to_response(context, **response_kwargs)


class EventDetailView(DetailView):
    template_name = "events/detail.html"
    model = Event
    context_object_name = "event"

    def get_queryset(self):
        confirmed_attendances = Prefetch(
            "attendances", EventAttendance.objects.filter(status="confirmed")
        )
        queryset = (
            Event.objects.select_related("organizer")
            .prefetch_related("categories", "dates", "images", confirmed_attendances)
            .annotate(
                confirmed_count=Count(
                    "attendances", filter=Q(attendances__status="confirmed")
                )
            )
        )
        if not self.request.user.is_authenticated:
            return queryset.filter(is_approved=True)
        if self.request.user.is_site_admin:
            return queryset
        return queryset.filter(Q(is_approved=True) | Q(organizer=self.request.user))

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        event = self.object

        if self.request.user.is_authenticated:
            context["user_attendance"] = EventAttendance.objects.filter(
                user=self.request.user, event=event
            ).first()
            context["is_owner"] = event.organizer == self.request.user

        context["similar_events"] = get_similar_events(event, limit=4)
        context["now"] = timezone.now()
        context["has_future_dates"] = event.next_date is not None
        return context


class EventOwnerMixin(LoginRequiredMixin, UserPassesTestMixin):
    def test_func(self):
        event = self.get_object()
        return event.organizer == self.request.user or self.request.user.is_site_admin


class EventCreateView(LoginRequiredMixin, CreateView):
    template_name = "events/create.html"
    model = Event
    form_class = EventForm

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        if self.request.POST:
            context["date_formset"] = EventDateFormSet(
                self.request.POST, prefix="dates"
            )
            context["image_formset"] = EventImageFormSet(
                self.request.POST, self.request.FILES, prefix="images"
            )
        else:
            context["date_formset"] = EventDateFormSet(prefix="dates")
            context["image_formset"] = EventImageFormSet(prefix="images")
        return context

    def form_valid(self, form):
        context = self.get_context_data()
        date_formset = context["date_formset"]
        image_formset = context["image_formset"]

        if date_formset.is_valid() and image_formset.is_valid():
            self.object = form.save(commit=False)
            self.object.organizer = self.request.user
            self.object.slug = slugify(self.object.title)
            base_slug = self.object.slug
            counter = 1
            while Event.objects.filter(slug=self.object.slug).exists():
                self.object.slug = f"{base_slug}-{counter}"
                counter += 1
            self.object.save()
            form.save_m2m()

            new_cat_names = form.cleaned_data.get("new_categories", [])
            for name in new_cat_names:
                slug = slugify(name)
                category, _ = EventCategory.objects.get_or_create(
                    slug=slug,
                    defaults={"name": name},
                )
                self.object.categories.add(category)

            date_formset.instance = self.object
            date_formset.save()
            image_formset.instance = self.object
            image_formset.save()

            messages.success(
                self.request, "Event created! It will be visible after admin approval."
            )
            return redirect("event_detail", slug=self.object.slug)
        return self.render_to_response(self.get_context_data(form=form))

    def get_success_url(self):
        return reverse("event_detail", kwargs={"slug": self.object.slug})


class EventUpdateView(EventOwnerMixin, UpdateView):
    template_name = "events/update.html"
    model = Event
    form_class = EventForm

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        if self.request.POST:
            context["date_formset"] = EventDateFormSet(
                self.request.POST, instance=self.object, prefix="dates"
            )
            context["image_formset"] = EventImageFormSet(
                self.request.POST,
                self.request.FILES,
                instance=self.object,
                prefix="images",
            )
        else:
            now = timezone.now()
            date_queryset = self.object.dates.filter(end_date__gte=now)

            NoExtraDateFormSet = forms.inlineformset_factory(
                Event,
                EventDate,
                form=EventDateForm,
                extra=0,
                can_delete=True,
                min_num=0,
                validate_min=False,
            )
            context["date_formset"] = NoExtraDateFormSet(
                instance=self.object, prefix="dates", queryset=date_queryset
            )
            context["event_is_past"] = not date_queryset.exists()

            context["image_formset"] = EventImageFormSet(
                instance=self.object, prefix="images"
            )
        return context

    def form_valid(self, form):
        context = self.get_context_data()
        date_formset = context["date_formset"]
        image_formset = context["image_formset"]

        if date_formset.is_valid() and image_formset.is_valid():
            self.object = form.save()

            new_cat_names = form.cleaned_data.get("new_categories", [])
            for name in new_cat_names:
                slug = slugify(name)
                category, _ = EventCategory.objects.get_or_create(
                    slug=slug,
                    defaults={"name": name},
                )
                self.object.categories.add(category)

            now = timezone.now()
            self.object.dates.filter(end_date__lt=now).delete()

            date_formset.save()
            image_formset.save()
            update_event_embedding(self.object)

            messages.success(self.request, "Event updated successfully!")
            return redirect("event_detail", slug=self.object.slug)
        return self.render_to_response(self.get_context_data(form=form))

    def get_success_url(self):
        return reverse("event_detail", kwargs={"slug": self.object.slug})


class EventDeleteView(EventOwnerMixin, DeleteView):
    template_name = "events/delete.html"
    model = Event
    success_url = reverse_lazy("explore")

    def delete(self, request, *args, **kwargs):
        messages.success(request, "Event deleted successfully.")
        return super().delete(request, *args, **kwargs)
