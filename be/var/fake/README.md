# Fake JSON corpus

`be/var/fake/*.json` is the canonical fake-data corpus for this app.

- Each file is a top-level JSON array of objects.
- PHP Fake classes may build lookup indexes in memory, but seed values should live here.
- Runtime writes in dev/test are in-memory only; tests and browser sessions must not rewrite these JSON files.
- The same files are suitable as BEAR.ApiDoc fake-data examples. When BEAR.ApiDoc is configured with `<fakeData dir="be/var/fake"/>`, it can link these payloads as OpenAPI Example Objects instead of requiring a custom example generator in this app.

Descriptions belong in this README or in schema/docs, not as `$comment` fields mixed into corpus JSON.
