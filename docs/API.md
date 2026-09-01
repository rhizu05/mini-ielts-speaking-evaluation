# Mini IELTS Speaking Evaluation API — Dokumentasi API

Base URL: `http://127.0.0.1:8000/api`

Semua endpoint mengembalikan JSON. Endpoint yang memerlukan autentikasi menggunakan header:

```
Authorization: Bearer <token>
```

Token didapatkan dari response `POST /register` atau `POST /login`.

## Ringkasan Endpoint

| Method | URL                | Auth         | Deskripsi                                     |
|--------|--------------------|--------------|-----------------------------------------------|
| GET    | `/questions`       | -            | Daftar pertanyaan speaking                    |
| POST   | `/speaking/submit` | - (opsional) | Submit jawaban + evaluasi Gemini              |
| POST   | `/register`        | -            | Buat akun baru (mengembalikan token)          |
| POST   | `/login`           | -            | Login (mengembalikan token)                   |
| POST   | `/logout`          | Bearer       | Hapus token aktif                             |
| GET    | `/user`            | Bearer       | Data user yang sedang login                   |
| GET    | `/attempts`        | Bearer       | Riwayat attempt milik user yang login         |

---

## 1. GET `/questions`

Mengembalikan daftar pertanyaan speaking (diurutkan berdasarkan part).

**Contoh response (200):**

```json
{
  "data": [
    {
      "id": 1,
      "part": 1,
      "topic": "Hometown",
      "question_text": "Where is your hometown, and what do you like most about it?"
    },
    {
      "id": 5,
      "part": 2,
      "topic": "Technology",
      "question_text": "Describe a piece of technology you use every day."
    }
  ]
}
```

---

## 2. POST `/speaking/submit`

Menerima jawaban user, memvalidasi payload, menyimpannya, lalu mengevaluasi menggunakan Gemini.

**Request body:**

```json
{
  "question_id": 1,
  "answer_text": "My hometown is Bandung. I like it because the weather is cool and the people are friendly."
}
```

**Contoh response sukses (201):**

```json
{
  "data": {
    "id": 1,
    "user_id": null,
    "question_id": 1,
    "answer_text": "My hometown is Bandung. I like it because the weather is cool and the people are friendly.",
    "band_score": 6.5,
    "strengths": ["Good fluency", "Relevant details"],
    "improvements": ["Expand your vocabulary", "Reduce repetition"],
    "raw_feedback": "Overall a clear answer. Add more detail to support your ideas.",
    "created_at": "2026-09-01T12:00:00.000000Z",
    "updated_at": "2026-09-01T12:00:01.000000Z"
  }
}
```

**Contoh response error validasi (422):**

```json
{
  "message": "The question id field is required. (and 1 more error)",
  "errors": {
    "question_id": ["The question id field is required."],
    "answer_text": ["The answer text field must be at least 20 characters."]
  }
}
```

---

## 3. POST `/register`

Membuat akun baru dan mengembalikan token.

**Request body:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

**Contoh response (201):**

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|abc123..."
  }
}
```

---

## 4. POST `/login`

Login dan mengembalikan token.

**Request body:**

```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Contoh response (200):**

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "1|abc123..."
  }
}
```

**Contoh response error kredensial (422):**

```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

## 5. POST `/logout`

Menghapus token yang sedang aktif. Memerlukan header Bearer.

**Contoh response (200):**

```json
{
  "message": "Logged out successfully."
}
```

---

## 6. GET `/user`

Mengembalikan data user yang sedang login. Memerlukan header Bearer.

**Contoh response (200):**

```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2026-09-01T12:00:00.000000Z",
    "updated_at": "2026-09-01T12:00:00.000000Z"
  }
}
```

**Contoh response tanpa token (401):**

```json
{
  "message": "Unauthenticated."
}
```

---

## 7. GET `/attempts`

Mengembalikan riwayat attempt milik user yang login, beserta data pertanyaannya. Memerlukan header Bearer.

**Contoh response (200):**

```json
{
  "data": [
    {
      "id": 2,
      "user_id": 1,
      "question_id": 5,
      "answer_text": "I use my phone every day because it helps me work and communicate with others.",
      "band_score": 7.0,
      "strengths": ["Clear structure", "Good vocabulary"],
      "improvements": ["Add more examples"],
      "raw_feedback": "A well-organised answer.",
      "created_at": "2026-09-01T13:00:00.000000Z",
      "updated_at": "2026-09-01T13:00:01.000000Z",
      "question": {
        "id": 5,
        "part": 2,
        "topic": "Technology",
        "question_text": "Describe a piece of technology you use every day."
      }
    }
  ]
}
```
