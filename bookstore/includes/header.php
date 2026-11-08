<?php
// Header halaman toko (untuk User/tamu).
// Sebelum include file ini, halaman harus set: $root (misal "" atau "../") dan $judul_halaman.
$root = $root ?? "";
$jumlah_keranjang = 0;
if (isset($_SESSION["user_id"]) && $_SESSION["role"] === "user") {
    $stmt_keranjang = $conn->prepare("SELECT COALESCE(SUM(jumlah),0) FROM carts WHERE user_id = ?");
    $stmt_keranjang->bind_param("i", $_SESSION["user_id"]);
    $stmt_keranjang->execute();
    $stmt_keranjang->bind_result($jumlah_keranjang);
    $stmt_keranjang->fetch();
    $stmt_keranjang->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul_halaman ?? "Rumah Baca") ?> - Rumah Baca</title>
  <link rel="stylesheet" href="<?= $root ?>assets/style.css">
</head>
<body>
<header class="topbar">
  <div class="isi">
    <a class="logo" href="<?= $root ?>index.php"><span class="kotak">R</span> Rumah Baca</a>
    <nav class="menu">
      <a href="<?= $root ?>index.php">Beranda</a>
      <a href="<?= $root ?>about.php">About Us</a>
      <a href="<?= $root ?>contact.php">Contact</a>
    </nav>
    <form class="cari" method="get" action="<?= $root ?>index.php">
      <input type="search" name="q" placeholder="Cari judul, penulis, kategori" value="<?= e($_GET["q"] ?? "") ?>" aria-label="Cari buku">
      <button type="submit">Cari</button>
    </form>
    <div class="kanan">
      <?php if (!isset($_SESSION["user_id"])): ?>
        <a class="btn garis" href="<?= $root ?>auth/login.php">Masuk</a>
        <a class="btn" href="<?= $root ?>auth/register.php">Daftar</a>
      <?php elseif ($_SESSION["role"] === "admin"): ?>
        <a href="<?= $root ?>admin/dashboard.php">Panel Admin</a>
        <a href="<?= $root ?>auth/logout.php">Keluar</a>
      <?php else: ?>
        <a href="<?= $root ?>user/keranjang.php" title="Keranjang" aria-label="Keranjang"><span style="font-size:20px">🛒</span><span class="angka"><?= (int)$jumlah_keranjang ?></span></a>
        <a href="<?= $root ?>user/pesanan.php">Pesanan Saya</a>
        <span class="muted">Hai, <?= e($_SESSION["nama"]) ?></span>
        <a href="<?= $root ?>auth/logout.php">Keluar</a>
      <?php endif; ?>
    </div>
  </div>
</header>
