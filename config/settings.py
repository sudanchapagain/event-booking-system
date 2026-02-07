from pathlib import Path
from urllib.parse import parse_qsl, urlparse

import environ
from django.core.exceptions import ImproperlyConfigured

BASE_DIR = Path(__file__).resolve().parent.parent

env = environ.Env(
    DEBUG=(bool, False),
    ALLOWED_HOSTS=(list, ["localhost", "127.0.0.1"]),
)

env_file = BASE_DIR / ".env"
if env_file.exists():
    environ.Env.read_env(env_file)

SECRET_KEY = env("SECRET_KEY")

DEBUG = env("DEBUG")

DEMO = env.bool("DEMO", default=DEBUG)

ALLOWED_HOSTS = env("ALLOWED_HOSTS")

INSTALLED_APPS = [
    "django.contrib.auth",
    "django.contrib.postgres",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
    "django_extensions",
    "corsheaders",
    "storages",
    "apps.accounts",
    "apps.events",
    "apps.bookings",
    "apps.dashboard",
    "apps.pages",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "whitenoise.middleware.WhiteNoiseMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "corsheaders.middleware.CorsMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
]

ROOT_URLCONF = "config.urls"

TEMPLATES = [
    {
        "BACKEND": "django.template.backends.django.DjangoTemplates",
        "DIRS": [BASE_DIR / "templates"],
        "APP_DIRS": True,
        "OPTIONS": {
            "context_processors": [
                "django.template.context_processors.debug",
                "django.template.context_processors.request",
                "django.contrib.auth.context_processors.auth",
                "django.contrib.messages.context_processors.messages",
            ],
        },
    },
]

WSGI_APPLICATION = "config.wsgi.application"

_env_database_url = env("DATABASE_URL", default="")

if DEMO:
    if _env_database_url:
        _pg = urlparse(_env_database_url)
        DATABASES = {
            "default": {
                "ENGINE": "django.db.backends.postgresql",
                "NAME": _pg.path.replace("/", ""),
                "USER": _pg.username,
                "PASSWORD": _pg.password,
                "HOST": _pg.hostname,
                "PORT": _pg.port or 5432,
                "ATOMIC_REQUESTS": True,
                "OPTIONS": dict(parse_qsl(_pg.query)),
            }
        }
    else:
        DATABASES = {
            "default": {
                "ENGINE": "django.db.backends.postgresql",
                "NAME": env("PG_NAME", default="chautari"),
                "USER": env("PG_USER", default="postgres"),
                "PASSWORD": env("PG_PASSWORD", default="postgres"),
                "HOST": env("PG_HOST", default="localhost"),
                "PORT": env("PG_PORT", default=5432),
                "ATOMIC_REQUESTS": True,
                "OPTIONS": {},
            }
        }
else:
    if not _env_database_url:
        raise ImproperlyConfigured(
            "DEMO is False but DATABASE_URL is not set. Set DATABASE_URL or enable DEMO mode."
        )
    _pg = urlparse(_env_database_url)
    if not _pg.path:
        raise ImproperlyConfigured(
            "DATABASE_URL is invalid or missing a database name."
        )
    DATABASES = {
        "default": {
            "ENGINE": "django.db.backends.postgresql",
            "NAME": _pg.path.replace("/", ""),
            "USER": _pg.username,
            "PASSWORD": _pg.password,
            "HOST": _pg.hostname,
            "PORT": _pg.port or 5432,
            "ATOMIC_REQUESTS": True,
            "OPTIONS": dict(parse_qsl(_pg.query)),
        }
    }


AUTH_PASSWORD_VALIDATORS = [
    {
        "NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator",
    },
    {
        "NAME": "django.contrib.auth.password_validation.MinimumLengthValidator",
        "OPTIONS": {"min_length": 8},
    },
    {
        "NAME": "django.contrib.auth.password_validation.CommonPasswordValidator",
    },
    {
        "NAME": "django.contrib.auth.password_validation.NumericPasswordValidator",
    },
]


LANGUAGE_CODE = "en-us"
TIME_ZONE = "Asia/Kathmandu"
USE_I18N = True
USE_TZ = True


STATIC_URL = "static/"
STATIC_ROOT = BASE_DIR / "staticfiles"
STATICFILES_DIRS = [BASE_DIR / "static"]

STATICFILES_STORAGE = "whitenoise.storage.CompressedManifestStaticFilesStorage"

MEDIA_URL = "media/"
MEDIA_ROOT = BASE_DIR / "media"

SUPABASE_URL = env("SUPABASE_URL", default="")
SUPABASE_KEY = env("SUPABASE_KEY", default="")
SUPABASE_BUCKET = env("SUPABASE_BUCKET", default="event_images")

if DEMO:
    DEFAULT_FILE_STORAGE = "django.core.files.storage.FileSystemStorage"
else:
    if SUPABASE_URL and SUPABASE_KEY:
        DEFAULT_FILE_STORAGE = "storages.backends.s3boto3.S3Boto3Storage"
        AWS_S3_ENDPOINT_URL = f"{SUPABASE_URL}/storage/v1/s3"
        AWS_ACCESS_KEY_ID = SUPABASE_KEY
        AWS_SECRET_ACCESS_KEY = SUPABASE_KEY
        AWS_STORAGE_BUCKET_NAME = SUPABASE_BUCKET
        AWS_S3_REGION_NAME = "auto"
        AWS_DEFAULT_ACL = "public-read"
        AWS_QUERYSTRING_AUTH = False
    else:
        DEFAULT_FILE_STORAGE = "django.core.files.storage.FileSystemStorage"


DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

AUTH_USER_MODEL = "accounts.User"

LOGIN_URL = "login"
LOGIN_REDIRECT_URL = "home"
LOGOUT_REDIRECT_URL = "home"

KHALTI_SECRET_KEY = env("KHALTI_SECRET_KEY", default="")
KHALTI_PUBLIC_KEY = env("KHALTI_PUBLIC_KEY", default="")
KHALTI_BASE_URL = env("KHALTI_BASE_URL", default="https://api.khalti.com/api/v2")


CORS_ALLOWED_ORIGINS = [
    "http://localhost:3000",
    "http://127.0.0.1:3000",
]
