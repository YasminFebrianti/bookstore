# BookStore (PHP + MySQL)

## Cara pasang (XAMPP)
1. Taruh folder ini di `C:\xampp\htdocs\bookstore` (isi langsung: `index.php`, `admin`, `auth`, dst. TANPA folder bookstore lagi di dalamnya).
2. Nyalakan **Apache** dan **MySQL** di XAMPP.
3. Database: kalau belum, import `database.sql` lewat phpMyAdmin (tab Import). Kalau sudah pernah import, **nggak usah lagi** (struktur tabel nggak berubah).
4. Buka `http://localhost/bookstore/buat_admin.php` sekali buat bikin akun admin, lalu **hapus file `buat_admin.php`**.
   - Email: `admin@bookstore.com`, password: `admin123`
5. Buka `http://localhost/bookstore/`

## Yang perlu kamu ganti sendiri
- Info rekening transfer: `config/helper.php` (cari `INFO_REKENING`)
- Teks About Us: `about.php`
- Password database kalau MySQL-mu pakai password: `config/koneksi.php`

## Daftar fitur
Admin (login pakai akun admin)
- Kategori buku: tambah / ubah / hapus
- Data buku: tambah / ubah / hapus (+ upload sampul)
- List user terdaftar
- List pesanan semua user (+ lihat bukti transfer, konfirmasi/tolak pembayaran, ubah status pesanan)
- Pesan masuk dari halaman Contact

User
- Registrasi & login
- About Us, Contact to Admin
- Cari buku (judul / penulis / kategori) + filter kategori
- Keranjang (tambah, ubah jumlah, hapus)
- Checkout: COD atau Transfer (upload bukti transfer)
- Pesanan Saya (status, bukti transfer, upload ulang kalau ditolak)

## Peta file
- `index.php` beranda + pencarian, `buku.php` detail buku, `about.php`, `contact.php`
- `auth/` register, login, logout
- `user/` keranjang, checkout, pesanan
- `admin/` dashboard, kategori, buku, buku_form, users, pesanan, pesan
- `config/` koneksi + fungsi bantuan, `includes/` header & footer, `assets/` CSS
- `uploads/` tempat gambar sampul & bukti transfer
