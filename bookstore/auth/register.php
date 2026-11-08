<?php
require "../config/koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama     = trim($_POST["nama"]);
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];
    $konfirm  = $_POST["konfirmasi"];

    // 1. Validasi input
    if ($nama === "" || $email === "" || $password === "") {
        $error = "Semua kolom wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($password !== $konfirm) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        // 2. Cek apakah email sudah dipakai
        $cek = $conn->prepare("SELECT id, nama, password, role FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();
        $ada = $cek->get_result()->fetch_assoc();

        if ($ada) {
            // Email sudah punya akun. Kalau password-nya cocok, langsung masukkan ke akunnya.
            if (password_verify($password, $ada["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = $ada["id"];
                $_SESSION["nama"]    = $ada["nama"];
                $_SESSION["role"]    = $ada["role"];
                set_flash("Email ini sudah terdaftar, jadi kamu langsung masuk ke akunmu.");
                header("Location: " . ($ada["role"] === "admin" ? "../admin/dashboard.php" : "../user/pesanan.php"));
                exit;
            }
            $error = "Email ini sudah terdaftar, tapi password-nya beda dengan yang tersimpan.";
            $email_ada = true;
        } else {
            // 3. Simpan user baru (password di-hash, role otomatis 'user')
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $nama, $email, $hash);
            $ins->execute();

            header("Location: login.php?daftar=berhasil");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar - Rumah Baca</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="card">
    <h1>Buat akun baru</h1>
    <p class="sub">Daftar untuk mulai belanja buku.</p>

    <?php if ($error): ?>
      <div class="alert error">
        <?= htmlspecialchars($error) ?>
        <?php if (!empty($email_ada)): ?>
          <br>Kalau ini akunmu, <a href="login.php?email=<?= urlencode($email) ?>"><b>masuk di sini</b></a> untuk melihat status pesananmu di menu Pesanan Saya.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <label for="nama">Nama lengkap</label>
      <input type="text" id="nama" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>

      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <label for="konfirmasi">Konfirmasi password</label>
      <input type="password" id="konfirmasi" name="konfirmasi" required>

      <button type="submit">Daftar</button>
    </form>

    <p class="foot">Sudah punya akun? <a href="login.php">Masuk</a></p>
  </div>
</div>
</body>
</html>
