<?php
require "config/koneksi.php";

$root = "";
$judul_halaman = "Beranda";

// Ambil kata kunci pencarian & kategori dari URL
$q   = trim($_GET["q"] ?? "");
$kat = (int)($_GET["kategori"] ?? 0);

// Susun query pelan-pelan sesuai filter yang dipakai
$sql = "SELECT b.*, c.nama_kategori
        FROM books b JOIN categories c ON c.id = b.category_id
        WHERE 1=1";
$tipe = "";
$param = [];

if ($q !== "") {
    $sql .= " AND (b.judul LIKE ? OR b.penulis LIKE ? OR c.nama_kategori LIKE ?)";
    $like = "%" . $q . "%";
    $tipe .= "sss";
    array_push($param, $like, $like, $like);
}
if ($kat > 0) {
    $sql .= " AND b.category_id = ?";
    $tipe .= "i";
    $param[] = $kat;
}
$sql .= " ORDER BY b.id DESC";

$stmt = $conn->prepare($sql);
if ($param) {
    $stmt->bind_param($tipe, ...$param);
}
$stmt->execute();
$buku = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$kategori = $conn->query("SELECT * FROM categories ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);

include "includes/header.php";
?>
<?php if ($q === "" && $kat === 0): ?>
<section class="hero">
  <div class="isi">
    <h2>SELAMAT DATANG DI RUMAH BACA</h2>
    <p>Temukan Cerita, Temukan Dunia.</p>
    <p>Setiap Buku Punya Cerita, Setiap Halaman Punya Makna.
Jelajahi beragam koleksi buku pilihan, temukan bacaan favoritmu, dan biarkan setiap halaman membawa inspirasi baru. Karena di Rumah Baca, selalu ada cerita yang menunggumu.</p>
  </div>
</section>
<?php endif; ?>

<main class="wadah">
  <?php tampil_flash(); ?>

  <div class="kategori">
    <a href="index.php" class="<?= $kat === 0 ? "aktif" : "" ?>">Semua</a>
    <?php foreach ($kategori as $k): ?>
      <a href="index.php?kategori=<?= $k["id"] ?>" class="<?= $kat === (int)$k["id"] ? "aktif" : "" ?>"><?= e($k["nama_kategori"]) ?></a>
    <?php endforeach; ?>
  </div>

  <?php if ($q !== ""): ?>
    <p class="muted">Hasil pencarian untuk "<b><?= e($q) ?></b>": <?= count($buku) ?> buku ditemukan.</p>
  <?php endif; ?>

  <?php if (!$buku): ?>
    <div class="kosong">Belum ada buku yang cocok. Coba kata kunci atau kategori lain ya.</div>
  <?php else: ?>
  <div class="grid-buku">
    <?php foreach ($buku as $b): ?>
      <article class="kartu-buku">
        <a href="buku.php?id=<?= $b["id"] ?>"><?= sampul($b, $root) ?></a>
        <div class="isi">
          <span class="muted"><?= e($b["nama_kategori"]) ?></span>
          <h3><a href="buku.php?id=<?= $b["id"] ?>"><?= e($b["judul"]) ?></a></h3>
          <span class="muted"><?= e($b["penulis"]) ?></span>
          <?php if (!empty($b["deskripsi"])): ?>
            <p class="muted" style="margin:6px 0 0"><?= e(mb_strimwidth($b["deskripsi"], 0, 90, "...", "UTF-8")) ?></p>
          <?php endif; ?>
          <span class="harga" style="margin-top:auto;padding-top:10px"><?= rupiah($b["harga"]) ?></span>
          <a class="btn garis" href="buku.php?id=<?= $b["id"] ?>" style="width:100%;margin-top:8px">Lihat Detail</a>
          <?php if ($b["stok"] > 0): ?>
            <form method="post" action="user/tambah_keranjang.php">
              <input type="hidden" name="book_id" value="<?= $b["id"] ?>">
              <input type="hidden" name="jumlah" value="1">
              <button type="submit" class="penuh" style="width:100%;margin-top:8px">Tambah ke Keranjang</button>
            </form>
          <?php else: ?>
            <button type="button" disabled style="width:100%;margin-top:8px">Stok habis</button>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
<?php include "includes/footer.php"; ?>
