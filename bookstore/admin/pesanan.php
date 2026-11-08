<?php
require "../config/koneksi.php";
wajib_admin("../");

// Proses aksi admin: konfirmasi/tolak pembayaran, atau ubah status pesanan
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id   = (int)($_POST["id"] ?? 0);
    $aksi = $_POST["aksi"] ?? "";

    $s = $conn->prepare("SELECT status_pesanan, metode_bayar FROM orders WHERE id = ?");
    $s->bind_param("i", $id);
    $s->execute();
    $o = $s->get_result()->fetch_assoc();

    if ($o) {
        if ($aksi === "konfirmasi") {
            $u = $conn->prepare("UPDATE orders SET status_pembayaran = 'lunas' WHERE id = ?");
            $u->bind_param("i", $id);
            $u->execute();
            set_flash("Pembayaran pesanan #$id dikonfirmasi (lunas).");
        } elseif ($aksi === "tolak") {
            $u = $conn->prepare("UPDATE orders SET status_pembayaran = 'ditolak' WHERE id = ?");
            $u->bind_param("i", $id);
            $u->execute();
            set_flash("Bukti transfer pesanan #$id ditolak. User bisa unggah ulang.");
        } elseif ($aksi === "status") {
            $baru = $_POST["status_pesanan"] ?? "";
            if (!in_array($baru, ["diproses", "dikirim", "selesai", "dibatalkan"], true)) {
                set_flash("Status nggak valid.", "error");
            } elseif ($o["status_pesanan"] === "dibatalkan") {
                set_flash("Pesanan yang sudah dibatalkan nggak bisa diubah lagi.", "error");
            } else {
                $conn->begin_transaction();
                try {
                    $u = $conn->prepare("UPDATE orders SET status_pesanan = ? WHERE id = ?");
                    $u->bind_param("si", $baru, $id);
                    $u->execute();

                    if ($baru === "dibatalkan") {
                        // Dibatalkan: stok buku dikembalikan
                        $k = $conn->prepare("UPDATE books b JOIN order_items oi ON oi.book_id = b.id
                                             SET b.stok = b.stok + oi.jumlah WHERE oi.order_id = ?");
                        $k->bind_param("i", $id);
                        $k->execute();
                    }
                    // COD yang sudah selesai = uang sudah diterima
                    if ($baru === "selesai" && $o["metode_bayar"] === "cod") {
                        $l = $conn->prepare("UPDATE orders SET status_pembayaran = 'lunas' WHERE id = ?");
                        $l->bind_param("i", $id);
                        $l->execute();
                    }
                    $conn->commit();
                    if ($baru === "selesai") {
                        set_flash("Pesanan #$id selesai, jadi dipindahkan ke Riwayat. Antrian di bawahnya otomatis maju.");
                    } elseif ($baru === "dibatalkan") {
                        set_flash("Pesanan #$id dibatalkan dan dipindahkan ke Riwayat.");
                    } else {
                        set_flash("Status pesanan #$id diubah.");
                    }
                } catch (Throwable $ex) {
                    $conn->rollback();
                    set_flash("Gagal mengubah status: " . $ex->getMessage(), "error");
                }
            }
        }
    }
    header("Location: pesanan.php");
    exit;
}

// Halaman utama = antrian (masih berjalan). ?tampil=riwayat = yang sudah selesai atau dibatalkan.
$riwayat = (($_GET["tampil"] ?? "") === "riwayat");
$kondisi = $riwayat
    ? "o.status_pesanan IN ('selesai','dibatalkan')"
    : "o.status_pesanan IN ('diproses','dikirim')";

$daftar = $conn->query("SELECT o.*, u.nama AS nama_user, u.email
                        FROM orders o JOIN users u ON u.id = o.user_id
                        WHERE $kondisi
                        ORDER BY o.id ASC")->fetch_all(MYSQLI_ASSOC);

$daftar_item = [];
if ($daftar) {
    $ids = implode(",", array_map("intval", array_column($daftar, "id")));
    $r = $conn->query("SELECT oi.order_id, oi.jumlah, oi.harga, b.judul
                       FROM order_items oi JOIN books b ON b.id = oi.book_id
                       WHERE oi.order_id IN ($ids)");
    while ($row = $r->fetch_assoc()) {
        $daftar_item[$row["order_id"]][] = $row;
    }
}

$halaman_aktif = "pesanan";
$judul_halaman = "List Pesanan";
include "_header.php";
?>
<h1><?= $riwayat ? "Riwayat Pesanan" : "List Pesanan" ?></h1>
<p class="muted">
  <?php if ($riwayat): ?>
    Pesanan yang sudah selesai atau dibatalkan.
  <?php else: ?>
    Antrian pesanan yang masih berjalan. Pesanan yang sudah selesai pindah ke Riwayat.
  <?php endif; ?>
</p>
<p>
  <?php if ($riwayat): ?>
    <a class="btn garis kecil" href="pesanan.php">&larr; Kembali ke antrian</a>
  <?php else: ?>
    <a class="btn garis kecil" href="pesanan.php?tampil=riwayat">Lihat riwayat pesanan selesai</a>
  <?php endif; ?>
</p>
<?php tampil_flash(); ?>

<?php if (!$daftar): ?>
  <div class="kosong"><?= $riwayat ? "Belum ada pesanan yang selesai." : "Nggak ada antrian pesanan saat ini." ?></div>
<?php endif; ?>

<?php foreach ($daftar as $n => $o): ?>
  <div class="panel">
    <div class="aksi" style="justify-content:space-between">
      <h2 style="margin:0">
        <?php if ($riwayat): ?>
          Pesanan #<?= $o["id"] ?> &middot; <?= e($o["nama_user"]) ?>
        <?php else: ?>
          Antrian ke-<?= $n + 1 ?> &middot; <?= e($o["nama_user"]) ?> <span class="muted" style="font-weight:400;font-size:14px">(Pesanan #<?= $o["id"] ?>)</span>
        <?php endif; ?>
      </h2>
      <span class="muted"><?= date("d M Y H:i", strtotime($o["created_at"])) ?></span>
    </div>
    <p class="aksi" style="margin:10px 0">
      <?= label_pesanan($o["status_pesanan"]) ?>
      <?= label_bayar($o["status_pembayaran"]) ?>
      <span class="badge abu"><?= $o["metode_bayar"] === "cod" ? "COD" : "Transfer" ?></span>
    </p>

    <div class="dua-kolom">
      <div>
        <b>Isi pesanan</b>
        <ul style="margin:6px 0;padding-left:20px">
          <?php foreach ($daftar_item[$o["id"]] ?? [] as $it): ?>
            <li><?= e($it["judul"]) ?> &times; <?= $it["jumlah"] ?> (<?= rupiah($it["harga"]) ?>)</li>
          <?php endforeach; ?>
        </ul>
        <b>Total: <?= rupiah($o["total"]) ?></b>
      </div>
      <div>
        <b>Dikirim ke</b>
        <p style="margin:6px 0"><?= e($o["nama_penerima"]) ?> (<?= e($o["telepon"]) ?>)<br><?= nl2br(e($o["alamat"])) ?></p>
        <span class="muted">Akun: <?= e($o["email"]) ?></span>
      </div>
      <?php if ($o["metode_bayar"] === "transfer"): ?>
      <div>
        <b>Bukti transfer</b><br>
        <?php if ($o["bukti_transfer"]): ?>
          <a href="../uploads/<?= e($o["bukti_transfer"]) ?>" target="_blank"><img class="bukti" src="../uploads/<?= e($o["bukti_transfer"]) ?>" alt="Bukti transfer"></a>
          <span class="muted">Klik gambar untuk memperbesar</span>
        <?php else: ?>
          <span class="muted">Belum ada bukti.</span>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="aksi" style="margin-top:16px;padding-top:14px;border-top:1px solid #E2E6EE">
      <?php if ($o["metode_bayar"] === "transfer" && $o["status_pembayaran"] === "menunggu_verifikasi"): ?>
        <form method="post" class="sebaris">
          <input type="hidden" name="id" value="<?= $o["id"] ?>">
          <input type="hidden" name="aksi" value="konfirmasi">
          <button type="submit" class="btn hijau kecil">Konfirmasi Pembayaran</button>
        </form>
        <form method="post" class="sebaris" onsubmit="return confirm('Tolak bukti transfer ini?')">
          <input type="hidden" name="id" value="<?= $o["id"] ?>">
          <input type="hidden" name="aksi" value="tolak">
          <button type="submit" class="btn merah kecil">Tolak</button>
        </form>
      <?php endif; ?>

      <?php if ($o["status_pesanan"] !== "dibatalkan"): ?>
        <form method="post" class="aksi">
          <input type="hidden" name="id" value="<?= $o["id"] ?>">
          <input type="hidden" name="aksi" value="status">
          <select name="status_pesanan" style="width:auto;min-height:36px">
            <?php foreach (["diproses" => "Diproses", "dikirim" => "Dikirim", "selesai" => "Selesai", "dibatalkan" => "Dibatalkan"] as $v => $t): ?>
              <option value="<?= $v ?>" <?= $o["status_pesanan"] === $v ? "selected" : "" ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn garis kecil">Ubah Status</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php include "_footer.php"; ?>
