# Resident photo batch upload limits

The resident Photo and Signature Manager accepts up to 20 photos per batch, 10 MB per file, and 60 MB combined. Photos must be named `{resident_id}.jpg`, `{resident_id}.jpeg`, or `{resident_id}.png`.

Production PHP and web-server settings must allow the complete request to reach Livewire. Configure `upload_max_filesize` to at least `10M`, `post_max_size` to at least `64M` (preferably `70M` for multipart overhead), and keep the Livewire temporary-upload validation at or above 60 MB. If Nginx or another reverse proxy is used, its request-body limit must also be at least 70 MB.

The application still enforces its own 20-file, 10 MB per-file, and 60 MB combined limits. Accepted photos are resized to fit within 1600 × 1600 pixels and re-encoded before permanent storage. Resident self-service uploads remain limited to 5 MB and the single staff API photo endpoint remains limited to 2 MB.
