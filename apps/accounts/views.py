from django.contrib import messages
from django.contrib.auth import login
from django.contrib.auth.mixins import LoginRequiredMixin
from django.contrib.auth.views import LoginView
from django.shortcuts import redirect
from django.urls import reverse_lazy
from django.utils import timezone
from django.views.generic import CreateView, TemplateView, UpdateView

from apps.bookings.models import EventAttendance
from apps.events.models import Event

from .forms import LoginForm, SignupForm, UserSettingsForm


class CustomLoginView(LoginView):
    template_name = "registration/login.html"
    authentication_form = LoginForm
    redirect_authenticated_user = True


class SignupView(CreateView):
    template_name = "registration/signup.html"
    form_class = SignupForm
    success_url = reverse_lazy("home")

    def form_valid(self, form):
        response = super().form_valid(form)
        login(self.request, form.instance)
        messages.success(
            self.request, "Account created successfully! Welcome to Chautari."
        )
        return response

    def dispatch(self, request, *args, **kwargs):
        if request.user.is_authenticated:
            return redirect("home")
        return super().dispatch(request, *args, **kwargs)


class ProfileView(LoginRequiredMixin, TemplateView):
    template_name = "user/profile.html"

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        user = self.request.user

        context["upcoming_events"] = Event.objects.filter(
            attendances__user=user,
            attendances__status="confirmed",
            dates__start_date__gte=timezone.now(),
        ).distinct()

        context["past_events"] = Event.objects.filter(
            attendances__user=user,
            attendances__status="confirmed",
            dates__end_date__lt=timezone.now(),
        ).distinct()

        context["total_bookings"] = EventAttendance.objects.filter(
            user=user, status="confirmed"
        ).count()

        return context


class SettingsView(LoginRequiredMixin, UpdateView):
    template_name = "user/settings.html"
    form_class = UserSettingsForm
    success_url = reverse_lazy("settings")

    # TODO: pylance error.
    def get_object(self):
        return self.request.user

    def form_valid(self, form):
        messages.success(self.request, "Settings updated successfully!")
        return super().form_valid(form)
