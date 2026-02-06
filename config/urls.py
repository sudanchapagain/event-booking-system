from django.conf import settings
from django.conf.urls.static import static
from django.urls import include, path

urlpatterns = [
    path("", include("apps.pages.urls")),
    path("", include("apps.accounts.urls")),
    path("", include("apps.events.urls")),
    path("", include("apps.bookings.urls")),
    path("", include("apps.dashboard.urls")),
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)
