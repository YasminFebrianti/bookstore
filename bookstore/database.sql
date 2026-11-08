-- ============================================
-- BookStore - Langkah 1: Struktur Database
-- Cara pakai: buka phpMyAdmin > tab Import > pilih file ini
-- ============================================

CREATE DATABASE IF NOT EXISTS bookstore
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bookstore;

-- Tabel pengguna (Admin dan User dibedakan lewat kolom role)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kategori buku (dikelola Admin)
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL
);

-- Data buku (dikelola Admin)
CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  judul VARCHAR(200) NOT NULL,
  penulis VARCHAR(100) NOT NULL,
  harga INT NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  deskripsi TEXT,
  gambar VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Keranjang belanja User
CREATE TABLE carts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  book_id INT NOT NULL,
  jumlah INT NOT NULL DEFAULT 1,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);

-- Pesanan (mendukung COD dan Transfer + bukti transfer)
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  nama_penerima VARCHAR(100) NOT NULL,
  telepon VARCHAR(20) NOT NULL,
  alamat TEXT NOT NULL,
  total INT NOT NULL,
  metode_bayar ENUM('cod','transfer') NOT NULL,
  bukti_transfer VARCHAR(255) NULL,
  status_pembayaran ENUM('menunggu','menunggu_verifikasi','lunas','ditolak') NOT NULL DEFAULT 'menunggu',
  status_pesanan ENUM('diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'diproses',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Rincian buku pada tiap pesanan
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  book_id INT NOT NULL,
  jumlah INT NOT NULL,
  harga INT NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Pesan Contact to Admin
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  isi_pesan TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Data awal kategori (contoh, boleh diubah)
INSERT INTO categories (nama_kategori) VALUES
('Teknologi'), ('Bisnis'), ('Pendidikan'), ('Fiksi');
