<?php
require "config/koneksi.php";

$root = "";
$id = (int)($_GET["id"] ?? 0);

$stmt = $conn->prepare("SELECT b.*, c.nama_kategori FROM books b JOIN categories c ON c.id = b.category_id WHERE b.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) {
    set_flash("Buku tidak ditemukan.", "error");
    header("Location: index.php");
    exit;
}

$judul_halaman = $b["judul"];
include "includes/header.php";
?>
<main class="wadah">
  <?php tampil_flash(); ?>
  <div class="detail">
    <div class="gambar"><?= sampul($b, $root, 420) ?></div>
    <div class="info">
      <span class="muted"><?= e($b["nama_kategori"]) ?></span>
      <h1><?= e($b["judul"]) ?></h1>
      <span class="muted">Penulis: <?= e($b["penulis"]) ?></span>
      <span class="harga" style="font-size:26px"><?= rupiah($b["harga"]) ?></span>
      <span class="muted">Stok: <?= (int)$b["stok"] ?></span>
      <p><?= nl2br(e($b["deskripsi"] ?: "Belum ada deskripsi untuk buku ini.")) ?></p>

      <?php if ($b["stok"] > 0): ?>
        <form method="post" action="user/tambah_keranjang.php" style="max-width:320px">
          <input type="hidden" name="book_id" value="<?= $b["id"] ?>">
          <label for="jumlah">Jumlah</label>
          <input type="number" id="jumlah" name="jumlah" value="1" min="1" max="<?= (int)$b["stok"] ?>">
          <button type="submit" style="width:100%;margin-top:14px">Tambah ke Keranjang</button>
        </form>
      <?php else: ?>
        <div class="alert error">Maaf, stok buku ini lagi habis.</div>
      <?php endif; ?>
      <p><a href="index.php">&larr; Kembali ke beranda</a></p>
    </div>
  </div>
</main>
<?php include "includes/footer.php"; ?>
