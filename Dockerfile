FROM python:3.13-slim

ENV PYTHONUNBUFFERED=1
ENV PYTHONDONTWRITEBYTECODE=1
WORKDIR /app

RUN apt-get update && \
    apt-get install -y --no-install-recommends build-essential netcat-openbsd gcc libpq-dev && \
    rm -rf /var/lib/apt/lists/*

COPY pyproject.toml ./

RUN python -m pip install --upgrade pip setuptools wheel

RUN pip install \
    "Django>=6" \
    "django-cors-headers>=4.3" \
    "psycopg2-binary>=2.9" \
    "whitenoise>=6.11" \
    "requests>=2.32.5" \
    "django-environ>=0.12" \
    "boto3>=1.42" \
    "django-storages>=1.14" \
    "django-extensions>=4.1" \
    "pillow>=12.1.0"

COPY . /app

RUN chmod +x /app/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/app/entrypoint.sh"]
CMD ["runserver", "0.0.0.0:8000"]
