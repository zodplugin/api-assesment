# API untuk Manajemen Pengguna

Perusahaan "DataTech" sedang membangun aplikasi manajemen pengguna yang digunakan oleh Front-End Developer untuk menampilkan, menambah, mengedit, dan menghapus data pengguna. Anda diminta untuk membangun RESTful API yang memungkinkan Front-End berkomunikasi dengan server untuk mengelola data pengguna tersebut. API ini harus mengakomodasi CRUD operations dan dilengkapi dengan autentikasi pengguna.

## Daftar Isi

- [Persyaratan](#persyaratan)
- [Instalasi](#instalasi)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Dokumentasi Swagger](#dokumentasi-swagger)
- [Menjalankan Unit Testing](#menjalankan-unit-testing)
- [Kontribusi](#kontribusi)

## Persyaratan

Sebelum memulai, pastikan Anda telah menginstal:
- PHP (versi yang didukung)
- Composer
- Laravel (versi yang didukung)
- Database (MySQL)

## Instalasi

1. Clone repositori ini: 
   `git clone https://github.com/zodplugin/api-assesment`

2. Pindah ke direktori proyek: 
   `cd api-assesment`

3. Install dependensi PHP: 
   `composer install`

4. Salin file `.env.example` ke `.env` dan sesuaikan konfigurasi database: 
   `cp .env.example .env`

5. Generate kunci aplikasi: 
   `php artisan key:generate`

6. Jalankan migrasi database: 
   `php artisan migrate`



## Menjalankan Aplikasi

Untuk menjalankan server pengembangan Laravel, gunakan perintah: 
`php artisan serve`
Aplikasi akan berjalan di `http://localhost:8000`.

## Dokumentasi Swagger

Dokumentasi API dapat diakses melalui Swagger. Untuk mengkonfigurasi dan menjalankan Swagger:

1. Pastikan Anda telah menginstal paket Swagger.
2. Tambahkan konfigurasi Swagger ke file `config/swagger.php`.
3. Jalankan server Laravel, dan akses Swagger UI di `http://localhost:8000/api/documentation`.

## Menjalankan Unit Testing

Untuk menjalankan unit testing, gunakan perintah: 
`php artisan test`


