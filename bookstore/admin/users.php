<?php
require "../config/koneksi.php";
wajib_admin("../");

$daftar = $conn->query("SELECT u.id, u.nama, u.email, u.role, u.created_at,
                               (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS jumlah_pesanan
                        FROM users u ORDER BY u.id DESC")->fetch_all(MYSQLI_ASSOC);

$halaman_aktif = "users";
$judul_halaman = "List User";
include "_header.php";
?>
<h1>List User</h1>
<p class="muted">Semua akun yang sudah terdaftar.</p>

<div class="panel">
  <div class="tabel-wrap">
  <table>
    <thead><tr><th>No</th><th>Nama</th><th>Email</th><th>Role</th><th>Jumlah pesanan</th><th>Daftar sejak</th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $n => $u): ?>
      <tr>
        <td><?= $n + 1 ?></td>
        <td><b><?= e($u["nama"]) ?></b></td>
        <td><?= e($u["email"]) ?></td>
        <td><span class="badge <?= $u["role"] === "admin" ? "biru" : "abu" ?>"><?= e($u["role"]) ?></span></td>
        <td><?= $u["jumlah_pesanan"] ?></td>
        <td><?= date("d M Y", strtotime($u["created_at"])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php include "_footer.php"; ?>
