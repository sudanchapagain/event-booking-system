from django.urls import path

from . import views

urlpatterns = [
    path("dashboard/", views.DashboardView.as_view(), name="dashboard"),
    path(
        "dashboard/bookings/",
        views.DashboardBookingsView.as_view(),
        name="dashboard_bookings",
    ),
    path(
        "dashboard/sales/", views.DashboardSalesView.as_view(), name="dashboard_sales"
    ),
    path(
        "dashboard/moderation/",
        views.DashboardModerationView.as_view(),
        name="dashboard_moderation",
    ),
    path(
        "dashboard/posts/", views.DashboardPostsView.as_view(), name="dashboard_posts"
    ),
    path(
        "dashboard/event/<int:event_id>/approve/",
        views.ApproveEventView.as_view(),
        name="approve_event",
    ),
    path(
        "dashboard/event/<int:event_id>/reject/",
        views.RejectEventView.as_view(),
        name="reject_event",
    ),
]
