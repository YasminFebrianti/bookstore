<?php
// Kerangka halaman Admin (sidebar). Halaman harus set $halaman_aktif & $judul_halaman sebelum include.
wajib_admin("../");
$root = "../";
$halaman_aktif = $halaman_aktif ?? "";

function menu_admin($file, $nama, $aktif) {
    $kelas = ($aktif === $file) ? "aktif" : "";
    echo '<a class="' . $kelas . '" href="' . $file . '.php">' . $nama . '</a>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($judul_halaman ?? "Admin") ?> - Admin Rumah Baca</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="admin">
  <aside class="sisi">
    <div class="judul">Rumah Baca Admin</div>
    <?php
    menu_admin("dashboard", "Dashboard", $halaman_aktif);
    menu_admin("kategori", "Kategori Buku", $halaman_aktif);
    menu_admin("buku", "Data Buku", $halaman_aktif);
    menu_admin("users", "List User", $halaman_aktif);
    menu_admin("pesanan", "List Pesanan", $halaman_aktif);
    menu_admin("pesan", "Pesan Masuk", $halaman_aktif);
    ?>
    <a href="../index.php" style="margin-top:auto">Lihat Toko</a>
    <a href="../auth/logout.php" style="color:#FBBF24">Keluar</a>
  </aside>
  <main class="konten">
