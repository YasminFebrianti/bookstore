<?php
require "../config/koneksi.php";
wajib_user("../");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$book_id = (int)($_POST["book_id"] ?? 0);
$jumlah  = max(1, (int)($_POST["jumlah"] ?? 1));
$user_id = $_SESSION["user_id"];

// Cek bukunya ada & stoknya cukup
$stmt = $conn->prepare("SELECT stok, judul FROM books WHERE id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$buku = $stmt->get_result()->fetch_assoc();

if (!$buku) {
    set_flash("Buku tidak ditemukan.", "error");
    header("Location: ../index.php");
    exit;
}

// Kalau buku sudah ada di keranjang, tinggal tambah jumlahnya
$cek = $conn->prepare("SELECT id, jumlah FROM carts WHERE user_id = ? AND book_id = ?");
$cek->bind_param("ii", $user_id, $book_id);
$cek->execute();
$ada = $cek->get_result()->fetch_assoc();

$jumlah_baru = $jumlah + ($ada["jumlah"] ?? 0);
if ($jumlah_baru > $buku["stok"]) {
    set_flash("Stok \"" . $buku["judul"] . "\" tinggal " . $buku["stok"] . ".", "error");
    header("Location: ../buku.php?id=" . $book_id);
    exit;
}

if ($ada) {
    $up = $conn->prepare("UPDATE carts SET jumlah = ? WHERE id = ?");
    $up->bind_param("ii", $jumlah_baru, $ada["id"]);
    $up->execute();
} else {
    $ins = $conn->prepare("INSERT INTO carts (user_id, book_id, jumlah) VALUES (?, ?, ?)");
    $ins->bind_param("iii", $user_id, $book_id, $jumlah);
    $ins->execute();
}

set_flash("\"" . $buku["judul"] . "\" masuk ke keranjang.");
header("Location: keranjang.php");
exit;
