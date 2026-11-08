<?php
require "../config/koneksi.php";
wajib_admin("../");

// Kalau ada ?id=... berarti mode edit, kalau nggak ada berarti tambah baru
$id = (int)($_GET["id"] ?? 0);
$buku = null;
if ($id > 0) {
    $s = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $buku = $s->get_result()->fetch_assoc();
    if (!$buku) {
        set_flash("Buku tidak ditemukan.", "error");
        header("Location: buku.php");
        exit;
    }
}

$kategori = $conn->query("SELECT * FROM categories ORDER BY nama_kategori")->fetch_all(MYSQLI_ASSOC);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cat   = (int)($_POST["category_id"] ?? 0);
    $judul = trim($_POST["judul"] ?? "");
    $penulis = trim($_POST["penulis"] ?? "");
    $harga = (int)($_POST["harga"] ?? 0);
    $stok  = (int)($_POST["stok"] ?? 0);
    $desk  = trim($_POST["deskripsi"] ?? "");

    if ($cat <= 0 || $judul === "" || $penulis === "") {
        $error = "Kategori, judul, dan penulis wajib diisi.";
    } elseif ($harga < 0 || $stok < 0) {
        $error = "Harga dan stok nggak boleh minus.";
    } else {
        [$gambar_baru, $err_upload] = upload_gambar($_FILES["gambar"] ?? null, "buku");
        if ($err_upload) {
            $error = $err_upload;
        } else {
            if ($buku) {
                // Edit: kalau ada gambar baru, ganti. Kalau nggak, pakai gambar lama.
                $gambar = $gambar_baru ?: $buku["gambar"];
                $u = $conn->prepare("UPDATE books SET category_id=?, judul=?, penulis=?, harga=?, stok=?, deskripsi=?, gambar=? WHERE id=?");
                $u->bind_param("issiissi", $cat, $judul, $penulis, $harga, $stok, $desk, $gambar, $id);
                $u->execute();
                if ($gambar_baru) {
                    hapus_file_upload($buku["gambar"]);
                }
                set_flash("Buku berhasil diubah.");
            } else {
                $i = $conn->prepare("INSERT INTO books (category_id, judul, penulis, harga, stok, deskripsi, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $i->bind_param("issiiss", $cat, $judul, $penulis, $harga, $stok, $desk, $gambar_baru);
                $i->execute();
                set_flash("Buku berhasil ditambah.");
            }
            header("Location: buku.php");
            exit;
        }
    }
    // Kalau error, tampilkan lagi isian yang tadi diketik
    $buku = array_merge($buku ?? [], [
        "category_id" => $cat, "judul" => $judul, "penulis" => $penulis,
        "harga" => $harga, "stok" => $stok, "deskripsi" => $desk,
        "gambar" => $buku["gambar"] ?? null,
    ]);
}

$halaman_aktif = "buku";
$judul_halaman = $id ? "Edit Buku" : "Tambah Buku";
include "_header.php";
?>
<h1><?= $id ? "Edit Buku" : "Tambah Buku" ?></h1>
<p class="muted"><a href="buku.php">&larr; Kembali ke data buku</a></p>

<?php if (!$kategori): ?>
  <div class="alert error">Belum ada kategori. <a href="kategori.php">Tambah kategori dulu</a> ya.</div>
<?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

<div class="panel" style="max-width:720px">
  <form method="post" enctype="multipart/form-data">
    <label for="category_id">Kategori</label>
    <select id="category_id" name="category_id" required>
      <option value="">-- Pilih kategori --</option>
      <?php foreach ($kategori as $k): ?>
        <option value="<?= $k["id"] ?>" <?= ((int)($buku["category_id"] ?? 0) === (int)$k["id"]) ? "selected" : "" ?>><?= e($k["nama_kategori"]) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="judul">Judul buku</label>
    <input type="text" id="judul" name="judul" value="<?= e($buku["judul"] ?? "") ?>" required>

    <label for="penulis">Penulis</label>
    <input type="text" id="penulis" name="penulis" value="<?= e($buku["penulis"] ?? "") ?>" required>

    <div class="dua-kolom">
      <div>
        <label for="harga">Harga (Rp)</label>
        <input type="number" id="harga" name="harga" min="0" value="<?= e($buku["harga"] ?? 0) ?>" required>
        <span class="muted">Tulis angka saja, tanpa titik. Contoh: 85000</span>
      </div>
      <div>
        <label for="stok">Stok</label>
        <input type="number" id="stok" name="stok" min="0" value="<?= e($buku["stok"] ?? 0) ?>" required>
      </div>
    </div>

    <label for="deskripsi">Deskripsi</label>
    <textarea id="deskripsi" name="deskripsi" rows="4"><?= e($buku["deskripsi"] ?? "") ?></textarea>

    <label for="gambar">Gambar sampul (JPG/PNG/WEBP, maks 2 MB)</label>
    <?php if (!empty($buku["gambar"])): ?>
      <div style="width:90px;margin-bottom:8px;border-radius:8px;overflow:hidden"><?= sampul($buku, "../", 120) ?></div>
      <span class="muted">Kosongkan kalau nggak mau ganti gambar.</span>
    <?php endif; ?>
    <input type="file" id="gambar" name="gambar" accept="image/png,image/jpeg,image/webp">

    <div class="aksi" style="margin-top:20px">
      <button type="submit"><?= $id ? "Simpan Perubahan" : "Tambah Buku" ?></button>
      <a class="btn garis" href="buku.php">Batal</a>
    </div>
  </form>
</div>
<?php include "_footer.php"; ?>
