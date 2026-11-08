<?php
require "../config/koneksi.php";
wajib_admin("../");

// Proses tambah / ubah / hapus (semua lewat POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";
    $id   = (int)($_POST["id"] ?? 0);
    $nama = trim($_POST["nama_kategori"] ?? "");

    try {
        if ($aksi === "tambah") {
            if ($nama === "") {
                set_flash("Nama kategori nggak boleh kosong.", "error");
            } else {
                $s = $conn->prepare("INSERT INTO categories (nama_kategori) VALUES (?)");
                $s->bind_param("s", $nama);
                $s->execute();
                set_flash("Kategori \"$nama\" berhasil ditambah.");
            }
        } elseif ($aksi === "ubah") {
            if ($nama === "") {
                set_flash("Nama kategori nggak boleh kosong.", "error");
            } else {
                $s = $conn->prepare("UPDATE categories SET nama_kategori = ? WHERE id = ?");
                $s->bind_param("si", $nama, $id);
                $s->execute();
                set_flash("Kategori berhasil diubah.");
            }
        } elseif ($aksi === "hapus") {
            $s = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $s->bind_param("i", $id);
            $s->execute();
            set_flash("Kategori berhasil dihapus.");
        }
    } catch (mysqli_sql_exception $ex) {
        // Gagal hapus karena masih dipakai buku
        set_flash("Kategori ini nggak bisa dihapus karena masih dipakai oleh buku. Pindahkan atau hapus bukunya dulu.", "error");
    }
    header("Location: kategori.php");
    exit;
}

$edit = null;
if (isset($_GET["edit"])) {
    $s = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $eid = (int)$_GET["edit"];
    $s->bind_param("i", $eid);
    $s->execute();
    $edit = $s->get_result()->fetch_assoc();
}

$daftar = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM books b WHERE b.category_id = c.id) AS jumlah_buku
                        FROM categories c ORDER BY c.nama_kategori")->fetch_all(MYSQLI_ASSOC);

$halaman_aktif = "kategori";
$judul_halaman = "Kategori Buku";
include "_header.php";
?>
<h1>Kategori Buku</h1>
<p class="muted">Tambah, ubah, dan hapus kategori.</p>
<?php tampil_flash(); ?>

<div class="panel" style="max-width:560px">
  <h2><?= $edit ? "Ubah kategori" : "Tambah kategori" ?></h2>
  <form method="post">
    <input type="hidden" name="aksi" value="<?= $edit ? "ubah" : "tambah" ?>">
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit["id"] ?>"><?php endif; ?>
    <label for="nama_kategori">Nama kategori</label>
    <input type="text" id="nama_kategori" name="nama_kategori" value="<?= e($edit["nama_kategori"] ?? "") ?>" required>
    <div class="aksi" style="margin-top:14px">
      <button type="submit"><?= $edit ? "Simpan Perubahan" : "Tambah" ?></button>
      <?php if ($edit): ?><a class="btn garis" href="kategori.php">Batal</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="panel">
  <div class="tabel-wrap">
  <table>
    <thead><tr><th>No</th><th>Nama kategori</th><th>Jumlah buku</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $n => $k): ?>
      <tr>
        <td><?= $n + 1 ?></td>
        <td><b><?= e($k["nama_kategori"]) ?></b></td>
        <td><?= $k["jumlah_buku"] ?></td>
        <td class="aksi">
          <a class="btn garis kecil" href="kategori.php?edit=<?= $k["id"] ?>">Edit</a>
          <form method="post" class="sebaris" onsubmit="return confirm('Hapus kategori ini?')">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= $k["id"] ?>">
            <button type="submit" class="btn merah kecil">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$daftar): ?><tr><td colspan="4" class="muted">Belum ada kategori.</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include "_footer.php"; ?>
