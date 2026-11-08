<?php
require "../config/koneksi.php";
$halaman_aktif = "dashboard";
$judul_halaman = "Dashboard";
include "_header.php";

function hitung($conn, $sql) {
    return (int)$conn->query($sql)->fetch_row()[0];
}
$jml_buku     = hitung($conn, "SELECT COUNT(*) FROM books");
$jml_kategori = hitung($conn, "SELECT COUNT(*) FROM categories");
$jml_user     = hitung($conn, "SELECT COUNT(*) FROM users WHERE role = 'user'");
$jml_pesanan  = hitung($conn, "SELECT COUNT(*) FROM orders");
$perlu_verif  = hitung($conn, "SELECT COUNT(*) FROM orders WHERE status_pembayaran = 'menunggu_verifikasi'");
$jml_pesan    = hitung($conn, "SELECT COUNT(*) FROM messages");
?>
<h1>Dashboard</h1>
<p class="muted">Halo, <?= e($_SESSION["nama"]) ?>. Ini ringkasan toko kamu.</p>
<?php tampil_flash(); ?>

<div class="statistik">
  <div class="kotak"><b><?= $jml_buku ?></b><span>Total buku</span></div>
  <div class="kotak"><b><?= $jml_kategori ?></b><span>Kategori</span></div>
  <div class="kotak"><b><?= $jml_user ?></b><span>User terdaftar</span></div>
  <div class="kotak"><b><?= $jml_pesanan ?></b><span>Total pesanan</span></div>
  <div class="kotak"><b><?= $perlu_verif ?></b><span>Bukti transfer perlu dicek</span></div>
  <div class="kotak"><b><?= $jml_pesan ?></b><span>Pesan masuk</span></div>
</div>

<?php if ($perlu_verif > 0): ?>
  <div class="alert error">Ada <?= $perlu_verif ?> pesanan yang bukti transfernya belum dicek. <a href="pesanan.php">Cek sekarang</a></div>
<?php endif; ?>
<?php include "_footer.php"; ?>
