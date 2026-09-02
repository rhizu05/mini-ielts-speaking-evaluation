# Alur Sistem — Mini IELTS Speaking Evaluation

Dokumen ini menjelaskan bagaimana data diproses dari awal sampai akhir, sebagai bahan persiapan interview.

## 1. Gambaran Umum

Aplikasi ini adalah sistem latihan IELTS Speaking. Pengguna dapat:

1. Melihat daftar pertanyaan speaking.
2. Menjawab pertanyaan dalam bentuk teks.
3. Mendapat evaluasi otomatis dari Google Gemini (skor band, kelebihan, area perbaikan).
4. (Opsional) Login/register agar riwayat jawaban tersimpan per pengguna.
5. (Opsional) Melanjutkan sebagai **guest** untuk latihan tanpa akun.

**Stack:** Laravel 13 (backend) + MySQL (database) + Vue 3 (frontend) + Google Gemini (AI) + Sanctum (autentikasi).

---

## 2. Arsitektur Tingkat Tinggi

```
[ Browser / Vue 3 ]
        │  HTTP (JSON)
        ▼
[ Laravel API ] ──── route (routes/api.php)
        │
        ├── Controller  ── memproses request
        │      ├── FormRequest ── validasi input
        │      ├── Model (Eloquent) ── akses database
        │      └── Service (GeminiService) ── panggil AI
        │
        ▼
[ MySQL Database ] ── tabel: users, questions, attempts
        ▲
[ Google Gemini API ] ── evaluasi jawaban
```

---

## 3. Alur Data per Fitur

### 3.1. Melihat Daftar Pertanyaan

```
User buka halaman
  → Vue onMounted() memanggil GET /api/questions
  → Route mengarah ke QuestionController@index
  → Query tabel questions (diurutkan per part)
  → Response JSON: { data: [...] }
  → Vue menampilkan daftar pertanyaan
```

**Data yang mengalir:** `questions` (id, part, topic, question_text) → JSON → Vue.

### 3.2. Submit Jawaban & Evaluasi (inti sistem)

```
User memilih pertanyaan + mengetik jawaban
  → Vue mengirim POST /api/speaking/submit
  → Route mengarah ke SpeakingController@submit

Langkah di dalam controller:
  1. SubmitSpeakingRequest memvalidasi:
     - question_id wajib ada di tabel questions
     - answer_text 20–2000 karakter
     (jika gagal → response 422 otomatis, tidak lanjut)

  2. Menyimpan attempt ke database:
     - user_id (null jika guest, id user jika login)
     - question_id
     - answer_text
     (kolom hasil evaluasi masih kosong/null)

  3. Memanggil GeminiService->evaluate():
     - Menyusun prompt (meminta Gemini berperan examiner IELTS)
     - Mengirim HTTP POST ke Gemini API
     - Meminta output JSON (band_score, strengths, improvements, feedback)

  4. Parsing respons Gemini:
     - Membersihkan markdown (```json)
     - json_decode menjadi array
     - Menormalkan hasil

  5. Update attempt dengan hasil evaluasi:
     - band_score, strengths, improvements, raw_feedback

  6. Response 201 + data attempt lengkap
```

**Poin penting untuk interview:**

- **Urutan simpan → evaluasi → update**: jawaban disimpan dulu sebelum evaluasi, sehingga jika Gemini gagal, jawaban pengguna tidak hilang.
- **Error handling**: pemanggilan Gemini dibungkus `try/catch`. Jika gagal, response tetap 201 dengan pesan "answer saved but evaluation failed".
- **Desain user_id nullable**: mendukung pengguna tanpa login (guest).

### 3.3. Register / Login (Sanctum)

```
POST /api/register atau /api/login
  → AuthController memvalidasi input
  → User dibuat / dicari
  → Password di-hash (Hash::make)
  → Token dibuat (createToken)
  → Response: { user, token }
```

**Cara token bekerja:**

```
Setiap request terproteksi mengirim header:
  Authorization: Bearer <token>

Middleware auth:sanctum membaca token
  → menemukan user terkait
  → mengizinkan/menolak request
```

### 3.4. Melihat Riwayat Attempt (per user)

```
GET /api/attempts (dengan token)
  → AttemptController@index
  → Ambil user dari token
  → Ambil semua attempts milik user (relasi hasMany)
  → Eager load relasi question (menghindari N+1 query)
  → Response: { data: [...] }
```

### 3.5. Guest Mode

```
User memilih "Continue as guest"
  → Vue menyimpan state guestMode = true (tanpa token)
  → Form submit tetap memanggil POST /api/speaking/submit
  → Karena tidak ada header Authorization, backend menyimpan user_id = null
  → Hasil evaluasi langsung ditampilkan (FeedbackPanel)
  → Riwayat tidak tersimpan karena guest tidak punya akun
```

**Poin penting:**
- Guest tetap mendapat evaluasi Gemini, namun attempt tidak terhubung ke akun mana pun.
- Saat guest memilih "Sign in to save", mereka diarahkan ke layar login untuk menyimpan progres ke akun.

---

## 4. Relasi Database

```
users (1) ──────< attempts (∞) >────── questions (1)
```

- Satu user memiliki banyak attempts (`hasMany`).
- Satu attempt milik satu user (`belongsTo`).
- Satu question memiliki banyak attempts (`hasMany`).
- Satu attempt milik satu question (`belongsTo`).

**Tabel `attempts`:**
- `user_id` (nullable, FK → users) — siapa yang menjawab
- `question_id` (FK → questions) — pertanyaan yang dijawab
- `answer_text` — jawaban
- `band_score`, `strengths`, `improvements`, `raw_feedback` — hasil evaluasi

---

## 5. Bagaimana Gemini Diintegrasikan

```
.env (GEMINI_API_KEY, GEMINI_MODEL, GEMINI_API_BASE_URL)
        │
        ▼
config/services.php ── memetakan env ke config
        │
        ▼
AppServiceProvider ── binding singleton GeminiService
        │
        ▼
GeminiService::evaluate()
  ├── buildPrompt()   — menyusun instruksi ke AI
  ├── HTTP POST       — kirim request ke Gemini
  └── parse()         — rapikan respons JSON
```

**Kenapa service terpisah:**
- Mudah di-test (di-mock, tanpa internet).
- Mudah diganti (jika pindah provider AI).
- Controller tetap bersih.

---

## 6. Bagaimana Testing Bekerja

```
php artisan test
  → Menggunakan SQLite in-memory (bukan MySQL)
  → Database di-refresh per test (RefreshDatabase)
  → GeminiService di-mock (tidak memanggil API asli)
```

**Test yang ada (34 test):**
- List pertanyaan
- Submit jawaban (mock Gemini)
- Validasi payload invalid (negative test)
- Register + lihat attempt (per user)
- Endpoint attempts wajib auth (negative test)
- Isolasi data antar user (Alice/Bob)
- Unit test parsing respons Gemini (JSON valid, markdown, teks bebas, missing keys, respons kosong, error handling)

---

## 7. Poin Penting untuk Interview

| Topik | Jawaban singkat |
|-------|-----------------|
| Kenapa `user_id` nullable? | Mendukung guest tanpa login; attempt tetap tersimpan |
| Bagaimana guest mode bekerja? | UI menawarkan "Continue as guest"; submit tanpa token → `user_id` null, hasil tetap tampil |
| Kenapa simpan dulu baru evaluasi? | Jawaban tidak hilang jika Gemini gagal |
| Kenapa `strengths`/`improvements` pakai JSON? | Berupa array, fleksibel tanpa tabel terpisah |
| Kenapa `band_score` decimal(2,1)? | Format band IELTS (4.0–9.0, 1 desimal) |
| Kenapa Gemini di-mock saat test? | Test tidak bergantung internet/API key |
| Kenapa credential di `.env`? | Tidak boleh di-commit; `.env` di `.gitignore` |
| Apa itu `findOrFail`? | Mengembalikan 404 otomatis jika data tidak ada |
| Apa beda `hasMany` vs `belongsTo`? | Dua sisi relasi yang sama, dilihat dari arah berbeda |
| Apa itu eager load (`with`)? | Mengambil relasi sekaligus, menghindari N+1 query |
