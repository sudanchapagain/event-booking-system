from django.urls import path

from . import views

urlpatterns = [
    path("event/<slug:slug>/book/", views.BookEventView.as_view(), name="event_book"),
    path(
        "event/<slug:slug>/cancel/",
        views.CancelBookingView.as_view(),
        name="booking_cancel",
    ),
    path(
        "payment/validate/",
        views.PaymentValidateView.as_view(),
        name="payment_validate",
    ),
]
