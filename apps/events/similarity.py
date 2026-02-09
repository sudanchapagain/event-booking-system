from __future__ import annotations

import math
import re
from collections import defaultdict
from typing import Iterable


STOP_WORDS = {
    "the",
    "a",
    "an",
    "and",
    "or",
    "but",
    "in",
    "on",
    "at",
    "to",
    "for",
    "of",
    "with",
    "is",
    "are",
    "was",
    "were",
    "be",
    "been",
    "being",
    "have",
    "has",
    "had",
    "do",
    "does",
    "did",
    "will",
    "would",
    "could",
    "should",
    "may",
    "might",
    "must",
    "can",
    "this",
    "that",
    "these",
    "those",
    "i",
    "you",
    "he",
    "she",
    "it",
    "we",
    "they",
    "what",
    "which",
    "who",
    "when",
    "where",
    "why",
    "how",
}


TOKEN_RE = re.compile(r"[a-z0-9]+", re.IGNORECASE)


def _tokenize(text: str) -> list[str]:
    tokens = TOKEN_RE.findall(text.lower())
    return [t for t in tokens if t not in STOP_WORDS]


def build_event_text(event) -> str:
    parts = [
        event.title or "",
        event.description or "",
        event.location or "",
    ]

    if hasattr(event, "categories"):
        parts.extend(cat.name for cat in event.categories.all())
    return " ".join(parts)


def build_vocabulary(texts: Iterable[str]) -> dict[str, int]:
    df: dict[str, int] = defaultdict(int)

    for text in texts:
        seen = set(_tokenize(text))
        for word in seen:
            df[word] += 1

    return dict(df)


def compute_idf(vocab: dict[str, int], doc_count: int) -> dict[str, float]:
    idf = {}
    for word, df in vocab.items():
        idf[word] = math.log((doc_count + 1) / (df + 1)) + 1.0
    return idf


def text_to_vector(
    text: str, idf: dict[str, float], vocab_order: list[str]
) -> list[float]:
    tokens = _tokenize(text)
    if not tokens:
        return [0.0] * len(vocab_order)

    tf: dict[str, int] = defaultdict(int)
    for t in tokens:
        tf[t] += 1

    length = len(tokens)
    vector: list[float] = []

    for word in vocab_order:
        tf_val = tf.get(word, 0) / length
        vector.append(tf_val * idf.get(word, 0.0))

    return vector


def cosine_similarity(vec1: list[float], vec2: list[float]) -> float:
    dot = 0.0
    mag1 = 0.0
    mag2 = 0.0

    for a, b in zip(vec1, vec2):
        dot += a * b
        mag1 += a * a
        mag2 += b * b

    if mag1 == 0.0 or mag2 == 0.0:
        return 0.0

    return dot / (math.sqrt(mag1) * math.sqrt(mag2))


def rebuild_all_embeddings() -> int:
    from .models import Event

    events = list(Event.objects.filter(is_approved=True).prefetch_related("categories"))

    if not events:
        return 0

    texts = [build_event_text(e) for e in events]
    vocab = build_vocabulary(texts)
    vocab_order = sorted(vocab.keys())
    idf = compute_idf(vocab, len(texts))

    for event in events:
        text = build_event_text(event)
        event.embedding = text_to_vector(text, idf, vocab_order)
        event.save(update_fields=["embedding"])

    return len(events)


def update_event_embedding(event) -> None:
    rebuild_all_embeddings()


def get_similar_events(event, limit: int = 5):
    from .models import Event
    from django.db.models import Case, When

    event_embedding = event.embedding
    if not isinstance(event_embedding, list):
        return list(
            Event.objects.filter(is_approved=True)
            .exclude(id=event.id)
            .order_by("-created_at")[:limit]
        )

    others = list(
        Event.objects.filter(
            is_approved=True,
            embedding__isnull=False,
        ).exclude(id=event.id)
    )

    similarities = []

    for other in others:
        other_embedding = other.embedding
        if not isinstance(other_embedding, list):
            continue
        if len(other_embedding) != len(event_embedding):
            continue

        score = cosine_similarity(event_embedding, other_embedding)
        similarities.append((other.id, score))

    similarities.sort(key=lambda x: x[1], reverse=True)
    top_ids = [eid for eid, score in similarities[:limit] if score > 0]

    if not top_ids:
        return []

    preserved = Case(*[When(id=eid, then=pos) for pos, eid in enumerate(top_ids)])
    return list(Event.objects.filter(id__in=top_ids).order_by(preserved))
