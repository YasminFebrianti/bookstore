<?php
require "config/koneksi.php";

$root = "";
$judul_halaman = "Contact";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama  = trim($_POST["nama"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $isi   = trim($_POST["isi_pesan"] ?? "");

    if ($nama === "" || $email === "" || $isi === "") {
        $error = "Semua kolom wajib diisi ya.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email belum benar.";
    } else {
        $uid = $_SESSION["user_id"] ?? null;
        $stmt = $conn->prepare("INSERT INTO messages (user_id, nama, email, isi_pesan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $uid, $nama, $email, $isi);
        $stmt->execute();
        set_flash("Pesan kamu sudah terkirim ke admin. Terima kasih!");
        header("Location: contact.php");
        exit;
    }
}

// Kalau sudah login, isi nama otomatis
$nama_awal = $_POST["nama"] ?? ($_SESSION["nama"] ?? "");

include "includes/header.php";
?>
<main class="wadah halaman">
  <h1>Contact to Admin</h1>
  <div class="panel" style="max-width:640px">
    <p class="muted">Ada pertanyaan atau saran? Tulis di sini, nanti admin yang baca.</p>
    <?php tampil_flash(); ?>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <label for="nama">Nama</label>
      <input type="text" id="nama" name="nama" value="<?= e($nama_awal) ?>" required>
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($_POST["email"] ?? "") ?>" required>
      <label for="isi_pesan">Pesan</label>
      <textarea id="isi_pesan" name="isi_pesan" rows="5" required><?= e($_POST["isi_pesan"] ?? "") ?></textarea>
      <button type="submit" style="margin-top:16px">Kirim Pesan</button>
    </form>
  </div>
</main>
<?php include "includes/footer.php"; ?>
