# Athena — Sistem Pengajuan Jabatan Akademik Dosen

Aplikasi berbasis **Laravel 12** untuk mengelola pengajuan jabatan akademik dosen: pengajuan, pelacakan status verifikasi, dan riwayat jabatan dalam satu platform.

## Tema & Teknologi

- **Laravel 12** (PHP 8.2+)
- **Tailwind CSS v4** + Vite 6
- **DataTables 2** (pencarian, sorting per kolom, filter status, responsif)
- **MySQL** — database `athena`
- Tema modern **teal & putih**, layout responsif untuk desktop, tablet, dan mobile

## Fitur awal

- Login menggunakan **NIDN** + password
- Toggle **tampilkan/sembunyikan password** (view mode)
- Peringatan Caps Lock, opsi "Ingat saya", dan pembatasan percobaan login
- Dashboard dengan kartu statistik pengajuan
- Tabel riwayat pengajuan dengan DataTables:
  - pencarian data
  - sorting melalui header kolom
  - filter status
  - pagination
  - tampilan responsif (baris dapat diperluas di layar kecil)
- Sidebar responsif dengan drawer di perangkat mobile
- Pembatasan data: dosen hanya melihat pengajuannya sendiri, admin melihat seluruh pengajuan
- **Profil Dosen** (khusus role dosen):
  - **Data Pribadi** — nama, tempat/tanggal lahir, alamat, no. HP, dan status pernikahan
  - Data pasangan otomatis tampil jika status **Menikah**: nama, pekerjaan (ASN/Non ASN), NIP (jika ASN), tempat/tanggal lahir, dan tanggal nikah
  - Data anak dinamis (bisa lebih dari satu): nama, tempat/tanggal lahir, jenis kelamin, dan pekerjaan
  - **Portofolio Saya** — menampilkan data pribadi, pasangan, dan anak yang telah diisi
  - **Riwayat Pendidikan** — halaman modul (dalam pengembangan)

## Akun demo

| Peran | NIDN | Password |
| --- | --- | --- |
| Admin | `0000000001` | `password` |
| Dosen | `1987654321` | `password` |

> Akun demo dibuat oleh `DatabaseSeeder`. Ganti password sebelum digunakan di lingkungan produksi.

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Sesuaikan koneksi database pada `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=athena
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi, seeder, dan build aset:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Untuk pengembangan frontend dengan hot reload:

```bash
npm run dev
```

## Pengujian

```bash
php artisan test
```

Pengujian menggunakan SQLite in-memory sehingga tidak mengubah data MySQL.

## Struktur utama

```
app/Http/Controllers/Auth/AuthenticatedSessionController.php  # login/logout NIDN
app/Http/Controllers/DashboardController.php                  # statistik & data dashboard
app/Http/Controllers/ProfilDosenController.php                 # data pribadi & portofolio dosen
app/Http/Middleware/EnsureUserIsDosen.php                      # pembatasan akses role dosen
app/Http/Requests/UpdateDataPribadiRequest.php                 # validasi data pribadi & keluarga
app/Models/User.php                                           # user + NIDN + peran
app/Models/PengajuanJabatan.php                               # pengajuan jabatan akademik
app/Models/ProfilDosen.php                                    # profil dosen + data pasangan
app/Models/AnakDosen.php                                      # data anak dosen
database/migrations/                                          # skema users, pengajuan_jabatan, profil_dosen, anak_dosen
database/seeders/DatabaseSeeder.php                           # akun & data demo
resources/views/auth/login.blade.php                          # halaman login
resources/views/dashboard.blade.php                           # dashboard + DataTables
resources/views/profil/                                       # data pribadi, portofolio, riwayat pendidikan
resources/views/layouts/                                      # layout auth & aplikasi
resources/css/app.css                                         # tema Tailwind + DataTables
resources/js/app.js                                           # DataTables, toggle password, form profil, sidebar
tests/Feature/ProfilDosenTest.php                             # pengujian modul profil dosen
```

## Rencana modul berikutnya

- Detail **Riwayat Pendidikan** (jenjang, institusi, tahun lulus, berkas)
- Form pengajuan jabatan akademik beserta unggah berkas
- Alur verifikasi berjenjang (admin kepegawaian / tim penilai)
- Manajemen data dosen dan riwayat jabatan
- Notifikasi status pengajuan
- Ekspor laporan pengajuan
