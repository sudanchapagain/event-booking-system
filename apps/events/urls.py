from django.urls import path

from . import views

urlpatterns = [
    path("explore/", views.EventListView.as_view(), name="explore"),
    path("event/new/", views.EventCreateView.as_view(), name="event_create"),
    path("event/<slug:slug>/", views.EventDetailView.as_view(), name="event_detail"),
    path(
        "event/<slug:slug>/edit/", views.EventUpdateView.as_view(), name="event_update"
    ),
    path(
        "event/<slug:slug>/delete/",
        views.EventDeleteView.as_view(),
        name="event_delete",
    ),
]
