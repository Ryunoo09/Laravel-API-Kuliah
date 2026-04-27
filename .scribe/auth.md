# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_AUTH_KEY}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Dapatkan token dengan melakukan request <b>POST /api/v1/login</b> menggunakan email dan password Anda.
