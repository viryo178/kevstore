# Kevstore API

Base URL lokal:

```text
http://127.0.0.1:8000/api
```

API memakai session CodeIgniter yang sama dengan aplikasi web. Login dulu lewat `POST /api/login`, lalu gunakan cookie session yang dikembalikan client.

## Auth

```http
POST /api/login
Content-Type: application/json

{
  "username": "admin",
  "password": "password"
}
```

```http
GET /api/me
POST /api/logout
```

## Akun

```http
GET /api/akun?q=grok&status=aktif&kategori=sharing&limit=100&offset=0
GET /api/akun/{id}
POST /api/akun
PUT /api/akun/{id}
PATCH /api/akun/{id}
DELETE /api/akun/{id}
POST /api/akun/{id}/tambah-max-user
```

Body tambah/update akun:

```json
{
  "nama_akun": "Grok",
  "kategori": "sharing",
  "status": "aktif",
  "username": "email@example.com",
  "password": "secret",
  "website": "https://example.com",
  "note": "catatan",
  "max_user": 0,
  "expired_password": "2026-12-31"
}
```

## Lainnya

```http
GET /api/notifications
GET /api/activity
GET /api/users
GET /api/notes
POST /api/notes
GET /api/notes/{id}
PUT /api/notes/{id}
DELETE /api/notes/{id}
GET /api/kepegawaian?bulan=2026-06
POST /api/kepegawaian
GET /api/chat/messages?limit=50
POST /api/chat/messages
```

Body absensi:

```json
{
  "id_user": 2,
  "tanggal": "2026-06-14",
  "status": "masuk"
}
```

Body chat:

```json
{
  "message": "Halo"
}
```

