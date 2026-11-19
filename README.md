# SIM Akreditasi RSU PKU Muhammadiyah Delanggu

Aplikasi Laravel untuk mengelola elemen penilaian akreditasi rumah sakit dan dokumen pendukungnya. Antarmuka menggunakan tema Glassmorphism dan dioptimalkan untuk desktop maupun perangkat mobile.

## Fitur

- Login dengan dua peran: admin dan user.
- Dashboard ringkasan bab, elemen penilaian, dan dokumen terbaru.
- Master data elemen penilaian berdasarkan kelompok Manajemen dan Pelayanan.
- Tambah, ubah, dan hapus elemen: bab, standar, EP, isi EP, dan RDWOS.
- Data elemen penilaian dari workbook untuk 16 bab dapat dimasukkan dengan `php artisan db:seed --class=AccreditationElementsSeeder` setelah menjalankan seeder utama. Urutan mengikuti nomor pada tiap sheet; jalankan ulang untuk menyelaraskan data dengan workbook. Untuk TKRS saja, gunakan `php artisan db:seed --class=TkrsElementSeeder`.
- Unggah satu dokumen untuk satu, beberapa, atau semua unit melalui popup; setiap unit mendapat catatan dokumen tersendiri dengan salinan file yang sama (PDF, Office, dan gambar; maksimal 10 MB).
- Cari dokumen berdasarkan nama file, unit, keterangan, standar, EP, atau bab; unduh dan hapus dokumen.
- Admin dapat mengelola master unit: tambah, ubah, dan hapus unit. Unit yang masih digunakan dokumen tidak dapat dihapus.
- User dapat mengelola Master Data dan dokumen, tetapi tidak dapat membuka fitur Setting Unit.
- Kolom Nilai RS dan Rekomendasi tidak disediakan.

## Menjalankan di Laragon

Persyaratan: PHP 8.3 atau lebih baru, Composer, ekstensi PHP `pdo_mysql`, dan MySQL/MariaDB Laragon.

1. Letakkan folder proyek di direktori Laragon, misalnya `C:\laragon\www\sim-akreditasi`.
2. Buat database MySQL bernama `sim_akreditasi` melalui HeidiSQL atau phpMyAdmin.
3. Salin `.env.example` menjadi `.env`, lalu sesuaikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`.
4. Pasang dependency dan buat application key:

   ```bash
   composer install
   php artisan key:generate
   ```

5. Isi `ADMIN_PASSWORD` dan `USER_PASSWORD` pada `.env`, lalu migrasikan tabel dan isi akun serta bab akreditasi:

   ```bash
   php artisan migrate --seed
   ```

6. Untuk menjalankan tanpa virtual host Laragon:

   ```bash
   php artisan serve
   ```

   Buka `http://127.0.0.1:8000`. Jika menggunakan virtual host, arahkan document root ke folder `public` dan sesuaikan `APP_URL` di `.env`.

## Akun awal

- Admin: username `adminakreditasi` (password diatur melalui `ADMIN_PASSWORD`).
- User: username `akreditasi` (password diatur melalui `USER_PASSWORD`).

Seeder mengambil kredensial dari `.env`. Pastikan password diganti dan disimpan dengan aman sebelum aplikasi digunakan pada lingkungan produksi. Seeder menyediakan akun admin dan user serta kategori bab Manajemen/Pelayanan; isi EP dapat dimasukkan melalui halaman Master Data.

Seeder elemen mengisi standar, EP, isi EP, dan urutan dari workbook sumber. Kolom **Upload Bukti** dan **Assessment** pada workbook belum menjadi kolom di aplikasi.

File yang diunggah disimpan pada storage privat Laravel (`storage/app/private`). Unduhan dilayani oleh aplikasi setelah pengguna masuk, bukan melalui URL file publik.
Daftar awal terdiri dari 43 unit yang ditetapkan pada `config/units.php`. Admin dapat mengelola unit melalui menu **Setting Unit**. Saat daftar awal diperbarui, dokumen tetap disimpan; dokumen yang terhubung ke unit lama di luar daftar baru dilepas dari unit dan tampil sebagai **Belum diatur** sampai dikelompokkan kembali.

## Pengujian

```bash
php artisan test
```
