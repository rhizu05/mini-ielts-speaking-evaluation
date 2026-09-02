# Mini IELTS Speaking Evaluation API

Backend kecil untuk latihan IELTS Speaking. User melihat daftar pertanyaan, mengirim jawaban speaking dalam bentuk teks, lalu mendapat evaluasi singkat (estimated band, strengths, areas to improve) dari Gemini.

## Fitur

- `GET /api/questions` — daftar pertanyaan speaking (Part 1/2/3 + topic)
- `POST /api/speaking/submit` — kirim jawaban teks, divalidasi, disimpan, lalu dievaluasi Gemini
- Register / login / logout (Sanctum) + history attempt per user
- Guest mode: latihan & evaluasi tanpa login (attempt tidak tersimpan ke akun)
- Dashboard Vue (login, guest mode, daftar attempt, detail feedback, form submit)
- Automated test (panggilan Gemini di-mock, tanpa internet), termasuk negative test

## Dashboard (Vue)

- Login/register untuk menyimpan attempt per user
- Guest mode ("Continue as guest") untuk latihan tanpa akun
- Menampilkan daftar attempt/result speaking milik user
- Klik attempt untuk melihat detail feedback (band, strengths, improvements, raw_feedback dari Gemini)
- Form submit jawaban yang langsung tersimpan ke riwayat user
- Terdiri dari komponen reusable: `PracticeForm` dan `FeedbackPanel`

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3+
- **Database:** MySQL
- **Frontend:** Vue 3 + Vite + Tailwind CSS
- **Auth:** Laravel Sanctum (API token)
- **AI:** Google Gemini API

## Cara Menjalankan (lokal)

```bash
# 1. Install dependency PHP
composer install

# 2. Salin env template lalu sesuaikan
cp .env.example .env

# 3. Generate app key
php artisan key:generate

# 4. Buat database MySQL bernama "ielts_speaking"
#    (laragon/phpMyAdmin/HeidiSQL atau CLI)

# 5. Set kredensial database & Gemini di .env
#    DB_DATABASE=ielts_speaking
#    DB_USERNAME=root
#    DB_PASSWORD=
#    GEMINI_API_KEY=...

# 6. Jalankan migration + seeder
php artisan migrate --seed

# 7. Install dependency frontend & build
npm install
npm run build      # atau: npm run dev (untuk development live-reload)

# 8. Jalankan server
php artisan serve
```

Buka `http://127.0.0.1:8000` untuk frontend, dan API tersedia di bawah prefix `/api`.

## Menjalankan Test

```bash
php artisan test
```

Test berjalan di SQLite in-memory (tidak menyentuh MySQL), dan panggilan Gemini di-mock sehingga tidak memerlukan internet.

Terdapat 34 test yang mencakup:

- **Feature test** — daftar pertanyaan, submit jawaban, validasi payload, guest mode, dan auth (register/login/isolasi data antar user).
- **Unit test** — parsing respons Gemini (JSON valid, markdown, teks bebas, missing keys, respons kosong) dan error handling.
- **Negative test** — payload tidak valid, kredensial salah, endpoint tanpa token, serta respons Gemini yang tidak sesuai.

### Lint

```bash
vendor/bin/pint
```

## Skema Database

```
users (1) ──────< attempts (∞) >────── questions (1)
```

### `questions`

| Kolom         | Tipe         | Keterangan             |
| ------------- | ------------ | ---------------------- |
| id            | bigint (PK)  | auto-increment         |
| part          | tinyint      | Part IELTS: 1 / 2 / 3  |
| topic         | varchar(255) | Topik pertanyaan       |
| question_text | text         | Isi pertanyaan lengkap |
| created_at    | timestamp    |                        |
| updated_at    | timestamp    |                        |

### `attempts`

| Kolom        | Tipe         | Keterangan                             |
| ------------ | ------------ | -------------------------------------- |
| id           | bigint (PK)  | auto-increment                         |
| user_id      | bigint (FK)  | FK ke `users`, nullable (dukung guest) |
| question_id  | bigint (FK)  | FK ke `questions`                      |
| answer_text  | text         | Jawaban user                           |
| band_score   | decimal(2,1) | Estimated band (nullable)              |
| strengths    | json         | Array kelebihan jawaban (nullable)     |
| improvements | json         | Array area perbaikan (nullable)        |
| raw_feedback | text         | Feedback mentah dari Gemini (nullable) |
| created_at   | timestamp    |                                        |
| updated_at   | timestamp    |                                        |

### Relasi

- `User` hasMany `Attempt`; `Attempt` belongsTo `User`
- `Question` hasMany `Attempt`; `Attempt` belongsTo `Question`
- `user_id` nullable → attempt tetap bisa dibuat tanpa login (guest)
- `attempts.user_id` `nullOnDelete`, `attempts.question_id` `cascadeOnDelete`

## Dokumentasi API

Base URL: `http://127.0.0.1:8000/api`

| Method | URL                | Auth         | Deskripsi                             |
| ------ | ------------------ | ------------ | ------------------------------------- |
| GET    | `/questions`       | -            | Daftar pertanyaan speaking            |
| POST   | `/speaking/submit` | - (opsional) | Submit jawaban + evaluasi Gemini      |
| POST   | `/register`        | -            | Buat akun baru (balas token)          |
| POST   | `/login`           | -            | Login (balas token)                   |
| POST   | `/logout`          | Bearer token | Hapus token aktif                     |
| GET    | `/user`            | Bearer token | Data user yang sedang login           |
| GET    | `/attempts`        | Bearer token | History attempt milik user yang login |

### Contoh request/response

#### `POST /api/speaking/submit`

Request body:

```json
{
    "question_id": 1,
    "answer_text": "My hometown is Bandung. I like it because the weather is cool and the people are friendly."
}
```

Response sukses (201):

```json
{
    "data": {
        "id": 1,
        "user_id": null,
        "question_id": 1,
        "answer_text": "My hometown is Bandung...",
        "band_score": 6.5,
        "strengths": ["Good fluency", "Relevant details"],
        "improvements": ["Expand your vocabulary", "Reduce repetition"],
        "raw_feedback": "Overall a clear answer...",
        "created_at": "2026-09-01T12:00:00.000000Z",
        "updated_at": "2026-09-01T12:00:01.000000Z"
    }
}
```

Dokumentasi API lengkap (contoh request & response untuk semua endpoint) tersedia di [`docs/API.md`](docs/API.md).

Penjelasan alur sistem (bagaimana data diproses) tersedia di [`docs/SYSTEM_FLOW.md`](docs/SYSTEM_FLOW.md).

Koleksi Postman siap import tersedia di [`docs/postman_collection.json`](docs/postman_collection.json).

## Konfigurasi Gemini

1. Dapatkan API key dari [Google AI Studio](https://aistudio.google.com/).
2. Set di file `.env`:

```env
GEMINI_API_KEY=your-api-key
GEMINI_MODEL=gemini-flash-lite-latest
GEMINI_API_BASE_URL=https://generativelanguage.googleapis.com/v1beta
```

> **Penting:** API key atau credential apa pun **tidak boleh di-commit**. File `.env` sudah masuk `.gitignore`; gunakan `.env.example` sebagai template tanpa nilai rahasia.

## Pengalaman Deployment

Saat ini saya belum memiliki pengalaman langsung dalam melakukan deployment Laravel ke infrastruktur VPS (seperti Nginx/Apache di Ubuntu) maupun Shared Hosting.

Namun, saya memiliki pengalaman memublikasikan proyek berbasis Node.js/Next.js menggunakan **Vercel** (mengatur _environment variables_, integrasi _build step_ dari repository Git, dan konfigurasi kustom domain `.my.id`).

Saya sangat terbuka dan antusias untuk mempelajari _deployment pipeline_ Laravel (seperti setup Nginx, PHP-FPM, database server, maupun CI/CD) apabila diberikan kesempatan bergabung.
