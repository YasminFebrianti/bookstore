<?php
require "../config/koneksi.php";
wajib_admin("../");

// Hapus pesan
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "hapus") {
    $id = (int)($_POST["id"] ?? 0);
    $d = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $d->bind_param("i", $id);
    $d->execute();
    set_flash("Pesan dihapus.");
    header("Location: pesan.php");
    exit;
}

$daftar = $conn->query("SELECT * FROM messages ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

$halaman_aktif = "pesan";
$judul_halaman = "Pesan Masuk";
include "_header.php";
?>
<h1>Pesan Masuk</h1>
<p class="muted">Pesan dari halaman Contact.</p>
<?php tampil_flash(); ?>

<?php if (!$daftar): ?><div class="kosong">Belum ada pesan masuk.</div><?php endif; ?>

<?php foreach ($daftar as $m): ?>
  <div class="panel">
    <div class="aksi" style="justify-content:space-between">
      <div><b><?= e($m["nama"]) ?></b> <span class="muted">&lt;<?= e($m["email"]) ?>&gt;</span></div>
      <span class="muted"><?= date("d M Y H:i", strtotime($m["created_at"])) ?></span>
    </div>
    <p style="margin:10px 0"><?= nl2br(e($m["isi_pesan"])) ?></p>
    <form method="post" class="sebaris" onsubmit="return confirm('Hapus pesan ini?')">
      <input type="hidden" name="aksi" value="hapus">
      <input type="hidden" name="id" value="<?= $m["id"] ?>">
      <button type="submit" class="btn merah kecil">Hapus</button>
    </form>
  </div>
<?php endforeach; ?>
<?php include "_footer.php"; ?>
