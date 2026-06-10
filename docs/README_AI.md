# 🤖 Dokumentasi Sistem Kecerdasan Buatan (AI) - Seven Caffee

Dokumen ini menjelaskan arsitektur, algoritma, alur kerja, dan konfigurasi seluruh fitur **Kecerdasan Buatan (AI) & Machine Learning (ML)** yang diimplementasikan pada proyek **Seven Caffee**.

Sistem AI di proyek ini terbagi menjadi dua pilar utama:
1. **Machine Learning Lokal (Rubix ML & PHP)**: Untuk analitik prediksi, peramalan (*forecasting*), clustering, dan sistem rekomendasi menu.
2. **Generative AI (Groq API & Llama 3.1)**: Untuk asisten chatbot pelanggan (*S7-Assistant*) dan rekomendasi strategi bisnis otomatis bagi administrator.

---

## 📐 Arsitektur Sistem AI & ML

```mermaid
graph TD
    %% Database & Input
    subgraph Data Layer
        DB[(Database MySQL)]
        Cache[(Laravel Cache)]
    end

    %% Rubix ML Pipeline
    subgraph Rubix ML Engine (PHP Local)
        RP[RevenuePredictor - Ridge Regressor]
        DF[DemandForecast - Ridge Regressor]
        KM[User Clustering - K-Means]
        CBF[Content-Based Recommender - Euclidean]
        CF[Collaborative Filtering - Cosine]
    end

    %% Groq LLM Layer
    subgraph Generative AI (Groq API)
        Llama[Llama 3.1 8B Instant]
    end

    %% Outputs & UI
    subgraph UI & Dashboard
        DB -->|Data Transaksi & Menu| RP & DF & KM & CBF & CF
        Cache -->|Pengaturan Toko| Llama
        
        RP -->|Prediksi Pendapatan| AdminDashboard[Dashboard Analitik Admin]
        DF -->|Rekomendasi Restok| AdminDashboard
        KM -->|Menu Populer Personalisasi| UserMenu[Halaman Rekomendasi Menu]
        CBF & CF -->|Hybrid Recommendation| UserMenu
        
        Llama -->|S7-Assistant Chatbot| ChatWidget[Widget Chatbot User]
        Llama -->|Business Strategies| AIInsight[AI Strategy Insight Admin]
    end

    style Rubix ML Engine fill:#f9f,stroke:#333,stroke-width:2px
    style Generative AI fill:#bbf,stroke:#333,stroke-width:2px
    style UI & Dashboard fill:#bfb,stroke:#333,stroke-width:2px
```

---

## 🧠 1. Machine Learning Engine (Rubix ML)

Sistem ini didukung oleh **Rubix ML** (library machine learning berkinerja tinggi untuk PHP). Seluruh model disimpan secara lokal di direktori `storage/app/ai-models/`.

### A. Peramalan Pendapatan & Stok (Regression)
Menggunakan algoritma **Ridge Regression** (regresi linier dengan regularisasi L2) untuk menghindari masalah *overfitting* pada data historis yang berskala kecil hingga menengah.

#### 1. Prediksi Pendapatan ([RevenuePredictor.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/AI/Models/RevenuePredictor.php))
* **Tujuan**: Memprediksi akumulasi pendapatan kafe hingga akhir bulan berjalan.
* **Fitur Input (5 Dimensi)**:
  1. Bulan (`month`)
  2. Hari dalam sebulan (`day_of_month`)
  3. Hari dalam seminggu (`day_of_week`)
  4. Indikator Akhir Pekan (`is_weekend` - 0 atau 1)
  5. Pendapatan hari sebelumnya (`lag_1_day_revenue`)
* **Metrik Evaluasi**: Mean Absolute Error (MAE).

#### 2. Peramalan Permintaan Bahan Baku ([DemandForecast.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/AI/Models/DemandForecast.php))
* **Tujuan**: Memprediksi kuantitas penjualan suatu produk untuk 30 hari ke depan guna mengidentifikasi risiko stok habis.
* **Fitur Input (5 Dimensi)**:
  1. Bulan (`month`)
  2. Hari dalam sebulan (`day_of_month`)
  3. Hari dalam seminggu (`day_of_week`)
  4. Indikator Akhir Pekan (`is_weekend` - 0 atau 1)
  5. Kuantitas penjualan hari sebelumnya (`lag_1_day_sales_qty`)
* **Pemberitahuan Restok**: Jika hasil sisa hari berdasarkan *burn-rate* kurang dari atau sama dengan 14 hari, item tersebut akan muncul pada widget **Rekomendasi Restok** di halaman Admin.

---

### B. Sistem Rekomendasi Hybrid
Menghasilkan rekomendasi menu yang personal dan dinamis untuk pelanggan melalui kombinasi metode:

#### 1. Content-Based Filtering ([ContentBasedRecommender.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/AI/Models/ContentBasedRecommender.php))
* **Konsep**: Merekomendasikan menu yang mirip dengan karakteristik menu yang pernah dibeli pengguna.
* **Fitur Produk**: `[category_id, price, is_signature, popularity]`.
* **Proses**:
  1. Fitur dinormalisasi dengan `ZScaleStandardizer`.
  2. Profil selera pengguna dihitung berdasarkan rata-rata tertimbang (*weighted average*) dari produk yang pernah dibeli.
  3. Kemiripan dihitung menggunakan **Euclidean Distance Kernel**. Produk dengan jarak terpendek (paling mirip) akan direkomendasikan.

#### 2. Collaborative Filtering ([CollaborativeFilteringRecommender.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/AI/Models/CollaborativeFilteringRecommender.php))
* **Konsep**: Merekomendasikan menu berdasarkan riwayat pembelian pengguna lain yang memiliki selera serupa (*User-to-User similarity*).
* **Vektor Pengguna**: Kuantitas pembelian per-produk `[qty_prod1, qty_prod2, ..., qty_prodN]`.
* **Proses**:
  1. Matriks pembelian dinormalisasi.
  2. Kemiripan antar-pengguna dihitung dengan **Cosine Distance Kernel** (sangat cocok untuk matriks jarang / *sparse matrix*).
  3. Mencari 5 pengguna paling mirip, lalu mengumpulkan produk yang mereka beli tetapi belum dibeli oleh pengguna target.

#### 3. Klusterisasi Pelanggan ([TrainUserClustering.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/Console/Commands/TrainUserClustering.php))
* **Tujuan**: Mengelompokkan pelanggan berdasarkan frekuensi pembelian kategori menu.
* **Algoritma**: **K-Means Clustering**.
* **Penerapan**: Pada bagian menu "Sedang Populer", pengguna akan melihat rekomendasi menu terlaris khusus di dalam kelompok klusternya (*trending-in-cluster*).

### C. Orkestrasi & Presentasi Rekomendasi (User Interface)
Setelah model dilatih, logika orkestrasi di sisi pengguna disalurkan melalui:
* **Orkestrasi Rekomendasi ([RecommendationController.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/Http/Controllers/Web/User/RecommendationController.php))**:
  * Menggabungkan hasil rekomendasi model *Content-Based* dan *Collaborative Filtering*.
  * Memfilter menu yang sudah pernah dibeli atau ditambahkan ke favorit agar rekomendasi selalu berupa produk baru.
  * Memiliki strategi *fallback* cerdas: Jika hasil rekomendasi kurang dari 6 (misalnya pada pelanggan baru), sistem akan mengisi slot kosong dengan menu terpopuler (global) atau menu unggulan (*signature*).
* **Personalisasi Menu Populer ([PopularMenuController.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/Http/Controllers/Web/User/PopularMenuController.php))**:
  * Menampilkan menu populer berdasarkan kelompok kluster K-Means dari pengguna yang bersangkutan (`cluster_id`).
  * Jika pengguna belum masuk ke kluster mana pun (pengguna baru), halaman akan otomatis melakukan *fallback* dengan menyajikan menu terlaris secara global kafe.
* **Tampilan & Komponen Pendukung**:
  * [recommendation.blade.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/resources/views/user/recommendation.blade.php): Halaman antarmuka rekomendasi menu yang dilengkapi badge penunjuk algoritma yang aktif (`hybrid`, `collaborative`, `content-based`, atau `trending`).
  * [popular.blade.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/resources/views/user/popular.blade.php): Halaman antarmuka menu populer yang telah terpersonalisasi berdasarkan kluster.

---

## 💬 2. Generative AI (Groq API & Llama 3.1)

Integrasi LLM eksternal untuk interaksi dinamis dan analisis bisnis tingkat lanjut dengan model **Llama 3.1 8B Instant**.

### A. S7-Assistant Chatbot ([ChatbotController.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/Http/Controllers/Web/User/ChatbotController.php))
* **Fitur**: Chatbot interaktif di pojok kanan bawah beranda pelanggan.
* **System Prompt Dinamis**:
  Sistem secara otomatis membaca database dan menyuntikkan informasi berikut ke dalam *System Prompt* sebelum dikirim ke Groq:
  * Nama toko, alamat, dan kontak WhatsApp ter-update.
  * Daftar menu aktif beserta harga dan kategorinya.
  * Promo aktif beserta tanggal kedaluwarsanya.
  * Pengaturan jam buka-tutup kafe.
* **Filter Topik Keamanan**:
  Metode `containsForbidden()` menyaring kata kunci sensitif (seperti manipulasi instruksi system prompt, kode pemrograman, matematika, politik) untuk memastikan chatbot tetap berfokus pada perannya sebagai barista kafe.

### B. AI Strategy Insight ([AiAnalyticController.php](file:///c:/laragon/www/proyek3-vianos-creative-compound/app/Http/Controllers/Web/Admin/AiAnalyticController.php))
* **Fitur**: Memberikan ringkasan strategi bisnis otomatis pada dashboard analitik admin.
* **Proses**:
  1. Controller mengumpulkan metrik operasional riil (hari paling ramai, kategori terlaris, pertumbuhan pendapatan, dan tingkat retensi).
  2. Mengirimkan parameter tersebut ke Groq API dengan perintah untuk merumuskan 1 paragraf rekomendasi bisnis taktis (seperti ide bundling, promo khusus hari ramai, atau persiapan stok).
  3. Jika koneksi API gagal atau kuota habis, sistem memiliki teks *fallback* cerdas agar UI tidak kosong.

---

## ⚙️ Pipeline Training & Otomatisasi

Agar performa prediksi dan rekomendasi tetap akurat dengan tren penjualan terbaru, model ML harus dilatih ulang secara berkala.

### 1. Jadwal Otomatis (Laravel Scheduler)
Penjadwalan didefinisikan pada `routes/console.php` dan berjalan secara asinkronus menggunakan antrean (*Queue*):
* **Harian (Pukul 00:00)**: Melakukan klusterisasi ulang pelanggan (`ai:cluster-users`).
* **Mingguan (Senin Pukul 01:00)**: Melatih ulang model Revenue Predictor & Demand Forecast (`TrainAiModelsJob`).
* **Mingguan (Senin Pukul 02:00)**: Melatih ulang model sistem rekomendasi Content-Based (`TrainRecommenderJob`).
* **Mingguan (Senin Pukul 03:00)**: Melatih ulang model sistem rekomendasi Collaborative Filtering (`TrainCollaborativeFilteringJob`).

### 2. Eksekusi Manual via Tinker
Anda dapat memicu pelatihan ulang model kapan saja secara manual melalui Laravel Tinker:

```bash
php artisan tinker
```

Jalankan perintah berikut di dalam Tinker shell:
```php
// 1. Latih Ulang Model Rekomendasi (Content-Based)
App\Jobs\TrainRecommenderJob::dispatch();

// 2. Latih Ulang Model Rekomendasi (Collaborative Filtering)
App\Jobs\TrainCollaborativeFilteringJob::dispatch();

// 3. Latih Ulang Model Klusterisasi Pengguna (K-Means)
Artisan::call('ai:cluster-users');

// 4. Latih Ulang Model Prediksi Penjualan & Stok (Ridge Regression)
App\Jobs\TrainAiModelsJob::dispatch();
```

---

## 🧪 Skrip Pengujian AI

Tersedia dua skrip mandiri untuk memvalidasi kesehatan sistem rekomendasi secara instan dari CLI:

1. **Uji Sederhana (Content-Based):**
   ```bash
   php test_recommender.php
   ```
   *Memeriksa apakah file model JSON dapat dimuat dan memproses input profil rasa dasar.*

2. **Uji Komprehensif (Content & Collaborative):**
   ```bash
   php test_recommender_comprehensive.php
   ```
   *Mengambil data pengguna riil dari database, mensimulasikan vektor pembelian mereka, membandingkan performa kedua model AI, dan menampilkan rekomendasi top-6 secara mendetail.*

---

## 📂 Struktur File Terkait AI

Berikut adalah lokasi file yang mengatur seluruh kecerdasan buatan pada proyek ini:

```text
proyek3-vianos-creative-compound/
├── app/
│   ├── AI/
│   │   └── Models/
│   │       ├── RevenuePredictor.php                  # Model Ridge Regressor pendapatan
│   │       ├── DemandForecast.php                    # Model Ridge Regressor permintaan stok
│   │       ├── ContentBasedRecommender.php           # Model kemiripan Euclidean produk
│   │       └── CollaborativeFilteringRecommender.php # Model kemiripan Cosine pengguna
│   ├── Console/
│   │   └── Commands/
│   │       └── TrainUserClustering.php               # Perintah Artisan KMeans clustering
│   ├── Http/
│   │   └── Controllers/
│   │       └── Web/
│   │           ├── Admin/
│   │           │   └── AiAnalyticController.php      # Integrasi metrik & Groq Insight Admin
│   │           └── User/
│   │               ├── ChatbotController.php         # Penanganan API Groq chatbot Llama 3.1
│   │               ├── RecommendationController.php  # Orkestrasi rekomendasi hybrid (Content + Collab)
│   │               └── PopularMenuController.php     # Presentasi menu populer terklusterisasi
│   └── Jobs/
│       ├── TrainAiModelsJob.php                      # Job training predictor & forecast
│       ├── TrainCollaborativeFilteringJob.php        # Job training Collaborative Filtering
│       └── TrainRecommenderJob.php                   # Job training Content-Based
├── resources/
│   └── views/
│       ├── user/
│       │   ├── recommendation.blade.php              # UI halaman rekomendasi cerdas
│       │   └── popular.blade.php                     # UI halaman menu populer terpersonalisasi
│       └── components/
│           └── chatbot.blade.php                     # UI & Alpine.js Chatbot widget
├── storage/
│   └── app/
│       └── ai-models/                                # Folder penyimpanan file biner & JSON model AI
├── test_recommender.php                              # Skrip CLI pengujian ringkas
└── test_recommender_comprehensive.php                # Skrip CLI pengujian komprehensif
```
