from __future__ import annotations

import re
from io import BytesIO
from typing import TYPE_CHECKING

from django.core.files.base import ContentFile
from PIL import Image

if TYPE_CHECKING:
    from django.db.models import QuerySet

    from .models import Event


class SimilarityService:
    @staticmethod
    def compute_embedding(event: Event) -> list[float]:
        text = f"{event.title} {event.location}"

        category_names = " ".join([cat.name for cat in event.categories.all()])
        text = f"{text} {category_names}"

        words = re.findall(r"\w+", text.lower())
        word_freq = {}

        for word in words:
            word_freq[word] = word_freq.get(word, 0) + 1

        if not word_freq:
            return []

        max_freq = max(word_freq.values())
        return list(word_freq.values()) if max_freq > 0 else []

    @staticmethod
    def cosine_similarity(vec1: list[float], vec2: list[float]) -> float:
        if not vec1 or not vec2:
            return 0.0

        max_len = max(len(vec1), len(vec2))
        v1 = vec1 + [0.0] * (max_len - len(vec1))
        v2 = vec2 + [0.0] * (max_len - len(vec2))

        dot_product = sum(a * b for a, b in zip(v1, v2, strict=True))

        magnitude1 = sum(a * a for a in v1) ** 0.5
        magnitude2 = sum(b * b for b in v2) ** 0.5

        if magnitude1 == 0 or magnitude2 == 0:
            return 0.0

        return dot_product / (magnitude1 * magnitude2)

    @staticmethod
    def get_similar_events(
        event: Event, queryset: QuerySet | None = None, limit: int = 5
    ) -> QuerySet:
        from .models import Event as EventModel

        if queryset is None:
            queryset = EventModel.objects.filter(is_approved=True)

        queryset = queryset.exclude(pk=event.pk)

        current_embedding = event.embedding
        if not current_embedding:
            return queryset.none()

        events_with_scores = []
        for candidate in queryset:
            candidate_embedding = candidate.embedding
            if not candidate_embedding:
                continue

            try:
                similarity = SimilarityService.cosine_similarity(
                    current_embedding, candidate_embedding
                )
                events_with_scores.append((candidate.pk, similarity))
            except (TypeError, ValueError):
                continue

        events_with_scores.sort(key=lambda x: x[1], reverse=True)

        top_ids = [pk for pk, _ in events_with_scores[:limit]]

        if not top_ids:
            return queryset.none()

        from django.db.models import Case, When

        preserved_order = Case(
            *[When(pk=pk, then=pos) for pos, pk in enumerate(top_ids)],
            default=len(top_ids),
        )

        return (
            queryset.filter(pk__in=top_ids)
            .annotate(similarity_order=preserved_order)
            .order_by("similarity_order")
        )


class ImageService:
    MAX_FILE_SIZE = 1_000_000  # 1MB in bytes
    MAX_WIDTH = 2000
    MAX_HEIGHT = 2000

    @staticmethod
    def downscale_image(image_file) -> ContentFile:
        try:
            img = Image.open(image_file)
        except FileNotFoundError:
            return image_file

        if img.width > ImageService.MAX_WIDTH or img.height > ImageService.MAX_HEIGHT:
            img.thumbnail(
                (ImageService.MAX_WIDTH, ImageService.MAX_HEIGHT),
                Image.Resampling.LANCZOS,
            )

        output = BytesIO()
        if img.mode in ("RGBA", "LA", "P"):
            rgb_img = Image.new("RGB", img.size, (255, 255, 255))
            rgb_img.paste(img, mask=img.split()[-1] if img.mode == "RGBA" else None)
            img = rgb_img

        img.save(output, format="JPEG", quality=85, optimize=True)
        output.seek(0)

        return ContentFile(output.getvalue(), name="image.jpg")
