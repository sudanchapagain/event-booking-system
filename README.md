<div align="center">
    <h1>Chautari</h1>
    <p>A simple event booking system built with PHP and PostgresDB.</p>
</div>

issues
------

On container initialization, `init.sql` is not executed automatically. fix:

```console
podman compose exec db bash
psql -U postgres -d event_booking_system -f docker-entrypoint-initdb.d/init.sql
```

---
