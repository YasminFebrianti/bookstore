<?php
require "../config/koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"]);
    $password = $_POST["password"];

    // 1. Cari user berdasarkan email
    $stmt = $conn->prepare("SELECT id, nama, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // 2. Cocokkan password dengan hash di database
    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["nama"]    = $user["nama"];
        $_SESSION["role"]    = $user["role"];

        // 3. Arahkan sesuai role
        if ($user["role"] === "admin") {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../index.php");
        }
        exit;
    } else {
        $error = "Email atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masuk - Rumah Baca</title>
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="card">
    <h1>Selamat datang kembali</h1>
    <p class="sub">Masukkan email dan password akunmu.</p>

    <?php tampil_flash(); ?>
    <?php if (isset($_GET["daftar"])): ?>
      <div class="alert ok">Pendaftaran berhasil, silakan masuk.</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST["email"] ?? ($_GET["email"] ?? "")) ?>" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password" required>

      <button type="submit">Masuk</button>
    </form>

    <p class="foot">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
  </div>
</div>
</body>
</html>
