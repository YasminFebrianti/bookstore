<?php
require "../config/koneksi.php";
wajib_user("../");

$root = "../";
$judul_halaman = "Checkout";
$user_id = $_SESSION["user_id"];
$error = "";

// Ambil isi keranjang
$stmt = $conn->prepare("SELECT c.id AS cart_id, c.jumlah, b.id AS book_id, b.judul, b.harga, b.stok
                        FROM carts c JOIN books b ON b.id = c.book_id
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$item) {
    set_flash("Keranjangmu masih kosong.", "error");
    header("Location: keranjang.php");
    exit;
}

$total = 0;
foreach ($item as $i) {
    $total += $i["harga"] * $i["jumlah"];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama   = trim($_POST["nama_penerima"] ?? "");
    $telp   = trim($_POST["telepon"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");
    $metode = $_POST["metode_bayar"] ?? "";

    if ($nama === "" || $telp === "" || $alamat === "") {
        $error = "Nama, telepon, dan alamat wajib diisi.";
    } elseif (!in_array($metode, ["cod", "transfer"], true)) {
        $error = "Pilih metode pembayaran dulu.";
    }

    // Kalau transfer, bukti transfer wajib diunggah
    $bukti = null;
    if (!$error && $metode === "transfer") {
        [$bukti, $err_upload] = upload_gambar($_FILES["bukti"] ?? null, "bukti");
        if ($err_upload) {
            $error = $err_upload;
        } elseif (!$bukti) {
            $error = "Untuk transfer, unggah bukti transfernya ya.";
        }
    }

    if (!$error) {
        $conn->begin_transaction();
        try {
            // Kunci stok tiap buku & pastikan masih cukup
            foreach ($item as $i) {
                $s = $conn->prepare("SELECT stok, judul FROM books WHERE id = ? FOR UPDATE");
                $s->bind_param("i", $i["book_id"]);
                $s->execute();
                $b = $s->get_result()->fetch_assoc();
                if (!$b || $b["stok"] < $i["jumlah"]) {
                    throw new Exception("Stok \"" . $i["judul"] . "\" tidak cukup. Coba cek keranjangmu lagi.");
                }
            }

            $status_bayar = ($metode === "transfer") ? "menunggu_verifikasi" : "menunggu";

            $o = $conn->prepare("INSERT INTO orders (user_id, nama_penerima, telepon, alamat, total, metode_bayar, bukti_transfer, status_pembayaran)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $o->bind_param("isssisss", $user_id, $nama, $telp, $alamat, $total, $metode, $bukti, $status_bayar);
            $o->execute();
            $order_id = $conn->insert_id;

            foreach ($item as $i) {
                $oi = $conn->prepare("INSERT INTO order_items (order_id, book_id, jumlah, harga) VALUES (?, ?, ?, ?)");
                $oi->bind_param("iiii", $order_id, $i["book_id"], $i["jumlah"], $i["harga"]);
                $oi->execute();

                $ks = $conn->prepare("UPDATE books SET stok = stok - ? WHERE id = ?");
                $ks->bind_param("ii", $i["jumlah"], $i["book_id"]);
                $ks->execute();
            }

            $h = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
            $h->bind_param("i", $user_id);
            $h->execute();

            $conn->commit();
            set_flash("Pesanan #" . $order_id . " berhasil dibuat. Terima kasih!");
            header("Location: pesanan.php");
            exit;
        } catch (Throwable $ex) {
            $conn->rollback();
            hapus_file_upload($bukti);
            $error = $ex->getMessage();
        }
    }
}

include "../includes/header.php";
?>
<main class="wadah halaman">
  <h1>Checkout</h1>
  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="tata-letak">
    <div class="utama">
      <div class="panel">
        <h2>Alamat pengiriman</h2>
        <div class="dua-kolom">
          <div>
            <label for="nama_penerima">Nama penerima</label>
            <input type="text" id="nama_penerima" name="nama_penerima" value="<?= e($_POST["nama_penerima"] ?? $_SESSION["nama"]) ?>" required>
          </div>
          <div>
            <label for="telepon">Nomor telepon</label>
            <input type="tel" id="telepon" name="telepon" value="<?= e($_POST["telepon"] ?? "") ?>" placeholder="08xxxxxxxxxx" required>
          </div>
        </div>
        <label for="alamat">Alamat lengkap</label>
        <textarea id="alamat" name="alamat" rows="3" placeholder="Jalan, nomor rumah, kota, kode pos" required><?= e($_POST["alamat"] ?? "") ?></textarea>
      </div>

      <div class="panel">
        <h2>Metode pembayaran</h2>
        <?php $metode_dipilih = $_POST["metode_bayar"] ?? "cod"; ?>
        <label class="pilihan-bayar">
          <input type="radio" name="metode_bayar" value="cod" <?= $metode_dipilih === "cod" ? "checked" : "" ?>>
          <span><b>COD (Payment at Delivery)</b><br><span class="muted">Bayar ke kurir pas buku sampai.</span></span>
        </label>
        <label class="pilihan-bayar">
          <input type="radio" name="metode_bayar" value="transfer" <?= $metode_dipilih === "transfer" ? "checked" : "" ?>>
          <span><b>Transfer bank</b><br><span class="muted">Transfer dulu, lalu unggah bukti transfernya.</span></span>
        </label>

        <div class="kotak-transfer" id="kotak-transfer" style="display:none">
          <p style="margin:0 0 8px">Transfer ke: <b><?= e(INFO_REKENING) ?></b><br>Nominal: <b><?= rupiah($total) ?></b></p>
          <label for="bukti" style="margin-top:8px">Bukti transfer (JPG/PNG/WEBP, maks 2 MB)</label>
          <input type="file" id="bukti" name="bukti" accept="image/png,image/jpeg,image/webp">
        </div>
      </div>
    </div>

    <aside class="samping panel">
      <h2>Ringkasan pesanan</h2>
      <?php foreach ($item as $i): ?>
        <div class="baris-total"><span><?= e($i["judul"]) ?> &times; <?= $i["jumlah"] ?></span><b><?= rupiah($i["harga"] * $i["jumlah"]) ?></b></div>
      <?php endforeach; ?>
      <div class="baris-total besar"><span>Total</span><span><?= rupiah($total) ?></span></div>
      <button type="submit" class="penuh" style="width:100%;margin-top:16px;min-height:50px">Buat Pesanan</button>
      <p style="text-align:center"><a href="keranjang.php">Kembali ke keranjang</a></p>
    </aside>
  </form>
</main>

<script>
// Munculkan kotak upload bukti hanya kalau pilih Transfer
const radios = document.querySelectorAll('input[name="metode_bayar"]');
const kotak = document.getElementById('kotak-transfer');
function atur() {
  const pilih = document.querySelector('input[name="metode_bayar"]:checked');
  kotak.style.display = (pilih && pilih.value === 'transfer') ? 'block' : 'none';
}
radios.forEach(r => r.addEventListener('change', atur));
atur();
</script>
<?php include "../includes/footer.php"; ?>
