<?php
require "../config/koneksi.php";
wajib_user("../");

$root = "../";
$judul_halaman = "Pesanan Saya";
$user_id = $_SESSION["user_id"];

// Upload ulang bukti transfer (kalau sebelumnya ditolak admin)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $order_id = (int)($_POST["order_id"] ?? 0);

    $c = $conn->prepare("SELECT bukti_transfer FROM orders WHERE id = ? AND user_id = ? AND metode_bayar = 'transfer' AND status_pembayaran = 'ditolak'");
    $c->bind_param("ii", $order_id, $user_id);
    $c->execute();
    $lama = $c->get_result()->fetch_assoc();

    if ($lama) {
        [$baru, $err] = upload_gambar($_FILES["bukti"] ?? null, "bukti");
        if ($err) {
            set_flash($err, "error");
        } elseif (!$baru) {
            set_flash("Pilih gambar bukti transfernya dulu.", "error");
        } else {
            $u = $conn->prepare("UPDATE orders SET bukti_transfer = ?, status_pembayaran = 'menunggu_verifikasi' WHERE id = ? AND user_id = ?");
            $u->bind_param("sii", $baru, $order_id, $user_id);
            $u->execute();
            hapus_file_upload($lama["bukti_transfer"]);
            set_flash("Bukti transfer baru terkirim. Tunggu verifikasi admin ya.");
        }
    }
    header("Location: pesanan.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Ambil isi tiap pesanan
$daftar_item = [];
if ($pesanan) {
    $ids = implode(",", array_map("intval", array_column($pesanan, "id")));
    $r = $conn->query("SELECT oi.order_id, oi.jumlah, oi.harga, b.judul
                       FROM order_items oi JOIN books b ON b.id = oi.book_id
                       WHERE oi.order_id IN ($ids)");
    while ($row = $r->fetch_assoc()) {
        $daftar_item[$row["order_id"]][] = $row;
    }
}

include "../includes/header.php";
?>
<main class="wadah halaman">
  <h1>Pesanan Saya</h1>
  <?php tampil_flash(); ?>

  <?php if (!$pesanan): ?>
    <div class="kosong">Kamu belum punya pesanan. <a href="../index.php">Yuk belanja dulu</a>.</div>
  <?php endif; ?>

  <?php foreach ($pesanan as $p): ?>
    <div class="panel">
      <div class="aksi" style="justify-content:space-between">
        <h2 style="margin:0">Pesanan #<?= $p["id"] ?></h2>
        <span class="muted"><?= date("d M Y H:i", strtotime($p["created_at"])) ?></span>
      </div>
      <p class="aksi" style="margin:10px 0">
        <?= label_pesanan($p["status_pesanan"]) ?>
        <?= label_bayar($p["status_pembayaran"]) ?>
        <span class="badge abu"><?= $p["metode_bayar"] === "cod" ? "COD" : "Transfer" ?></span>
      </p>

      <ul style="margin:8px 0;padding-left:20px">
        <?php foreach ($daftar_item[$p["id"]] ?? [] as $it): ?>
          <li><?= e($it["judul"]) ?> &times; <?= $it["jumlah"] ?> (<?= rupiah($it["harga"]) ?>)</li>
        <?php endforeach; ?>
      </ul>
      <p style="margin:6px 0"><b>Total: <?= rupiah($p["total"]) ?></b></p>
      <p class="muted" style="margin:6px 0">Dikirim ke: <?= e($p["nama_penerima"]) ?>, <?= e($p["telepon"]) ?><br><?= nl2br(e($p["alamat"])) ?></p>

      <?php if ($p["metode_bayar"] === "transfer" && $p["bukti_transfer"]): ?>
        <p class="muted" style="margin:10px 0 4px">Bukti transfer:</p>
        <a href="../uploads/<?= e($p["bukti_transfer"]) ?>" target="_blank"><img class="bukti" src="../uploads/<?= e($p["bukti_transfer"]) ?>" alt="Bukti transfer"></a>
      <?php endif; ?>

      <?php if ($p["status_pembayaran"] === "ditolak"): ?>
        <form method="post" enctype="multipart/form-data" style="margin-top:12px;max-width:380px">
          <div class="alert error">Bukti transfermu ditolak admin. Unggah ulang bukti yang benar ya.</div>
          <input type="hidden" name="order_id" value="<?= $p["id"] ?>">
          <input type="file" name="bukti" accept="image/png,image/jpeg,image/webp" required>
          <button type="submit" style="margin-top:10px">Kirim Ulang Bukti</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</main>
<?php include "../includes/footer.php"; ?>
