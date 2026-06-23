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

## Dashboard

```http
GET /api/dashboard
```

Mengembalikan ringkasan total akun, akun tersedia, dan data notifikasi.

## Akun

```http
GET /api/akun?q=grok&status=aktif&kategori=sharing&limit=100&offset=0
GET /api/akun/{id}
POST /api/akun
PUT /api/akun/{id}
PATCH /api/akun/{id}
DELETE /api/akun/{id}
POST /api/akun/{id}/tambah-max-user
GET /api/akun/deactived
GET /api/akun/ganti-password-exp
POST /api/akun/bulk
PATCH /api/akun/bulk
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

Body bulk tambah akun bisa pakai array:

```json
{
  "accounts": [
    {
      "username": "user1@example.com",
      "password": "secret1",
      "note": "catatan",
      "nama_akun": "Grok",
      "kategori": "belum_terjual",
      "status": "aktif",
      "max_user": 0
    },
    {
      "username": "user2@example.com",
      "password": "secret2"
    }
  ]
}
```

Atau format teks seperti fitur bulk web:

```json
{
  "bulk_accounts": "user1@example.com|secret1|catatan\nuser2@example.com|secret2"
}
```

Body bulk edit akun:

```json
{
  "accounts": [
    {
      "id_akun": 1,
      "status": "terjual",
      "kategori": "private",
      "max_user": 1
    },
    {
      "id_akun": 2,
      "username": "baru@example.com",
      "password": "password-baru"
    }
  ]
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
