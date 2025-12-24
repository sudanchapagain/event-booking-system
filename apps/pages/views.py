from django.views.generic import TemplateView

from apps.events.models import Event


class HomeView(TemplateView):
    template_name = "pages/home.html"

    def get_context_data(self, **kwargs):
        context = super().get_context_data(**kwargs)
        context["featured_events"] = Event.objects.filter(
            is_approved=True
        ).select_related("organizer")[:6]
        return context


class AboutView(TemplateView):
    template_name = "pages/about.html"


class ContactView(TemplateView):
    template_name = "pages/contact.html"


class PrivacyView(TemplateView):
    template_name = "pages/privacy.html"


class TermsView(TemplateView):
    template_name = "pages/terms.html"


class DPAView(TemplateView):
    template_name = "pages/dpa.html"
