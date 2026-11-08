<?php
require "../config/koneksi.php";
wajib_admin("../");

// Hapus buku
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["aksi"] ?? "") === "hapus") {
    $id = (int)($_POST["id"] ?? 0);

    $s = $conn->prepare("SELECT gambar FROM books WHERE id = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $b = $s->get_result()->fetch_assoc();

    try {
        $d = $conn->prepare("DELETE FROM books WHERE id = ?");
        $d->bind_param("i", $id);
        $d->execute();
        if ($b) {
            hapus_file_upload($b["gambar"]);
        }
        set_flash("Buku berhasil dihapus.");
    } catch (mysqli_sql_exception $ex) {
        // Buku sudah pernah dipesan, jadi nggak boleh dihapus
        set_flash("Buku ini nggak bisa dihapus karena sudah ada di pesanan user. Kamu bisa set stoknya jadi 0 aja.", "error");
    }
    header("Location: buku.php");
    exit;
}

$cari = trim($_GET["q"] ?? "");
$sql = "SELECT b.*, c.nama_kategori FROM books b JOIN categories c ON c.id = b.category_id";
if ($cari !== "") {
    $sql .= " WHERE b.judul LIKE ? OR b.penulis LIKE ?";
    $stmt = $conn->prepare($sql . " ORDER BY b.id DESC");
    $like = "%$cari%";
    $stmt->bind_param("ss", $like, $like);
} else {
    $stmt = $conn->prepare($sql . " ORDER BY b.id DESC");
}
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$halaman_aktif = "buku";
$judul_halaman = "Data Buku";
include "_header.php";
?>
<div class="aksi" style="justify-content:space-between">
  <div>
    <h1>Data Buku</h1>
    <p class="muted" style="margin:4px 0 0">Tambah, ubah, dan hapus buku yang dijual.</p>
  </div>
  <a class="btn" href="buku_form.php">+ Tambah Buku</a>
</div>
<?php tampil_flash(); ?>

<form method="get" class="aksi" style="margin:16px 0;max-width:420px">
  <input type="search" name="q" placeholder="Cari judul atau penulis" value="<?= e($cari) ?>">
  <button type="submit">Cari</button>
</form>

<div class="panel">
  <div class="tabel-wrap">
  <table>
    <thead><tr><th>Sampul</th><th>Judul</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $b): ?>
      <tr>
        <td style="width:70px"><div style="width:54px;height:72px;overflow:hidden;border-radius:6px"><?= sampul($b, "../", 72) ?></div></td>
        <td><b><?= e($b["judul"]) ?></b><br><span class="muted"><?= e($b["penulis"]) ?></span></td>
        <td><?= e($b["nama_kategori"]) ?></td>
        <td><?= rupiah($b["harga"]) ?></td>
        <td><?= $b["stok"] > 0 ? (int)$b["stok"] : '<span class="badge merah">Habis</span>' ?></td>
        <td class="aksi">
          <a class="btn garis kecil" href="buku_form.php?id=<?= $b["id"] ?>">Edit</a>
          <form method="post" class="sebaris" onsubmit="return confirm('Hapus buku ini?')">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= $b["id"] ?>">
            <button type="submit" class="btn merah kecil">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="6" class="muted">Belum ada buku. Klik "Tambah Buku" untuk mulai.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include "_footer.php"; ?>
