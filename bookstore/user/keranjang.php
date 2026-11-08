<?php
require "../config/koneksi.php";
wajib_user("../");

$root = "../";
$judul_halaman = "Keranjang";
$user_id = $_SESSION["user_id"];

// Aksi: ubah jumlah / hapus item
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cart_id = (int)($_POST["cart_id"] ?? 0);
    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "hapus") {
        $d = $conn->prepare("DELETE FROM carts WHERE id = ? AND user_id = ?");
        $d->bind_param("ii", $cart_id, $user_id);
        $d->execute();
        set_flash("Buku dihapus dari keranjang.");
    } elseif ($aksi === "ubah") {
        $jumlah = max(1, (int)($_POST["jumlah"] ?? 1));
        // Batasi sesuai stok
        $s = $conn->prepare("SELECT b.stok FROM carts c JOIN books b ON b.id = c.book_id WHERE c.id = ? AND c.user_id = ?");
        $s->bind_param("ii", $cart_id, $user_id);
        $s->execute();
        $row = $s->get_result()->fetch_assoc();
        if ($row) {
            if ($jumlah > $row["stok"]) {
                $jumlah = (int)$row["stok"];
                set_flash("Jumlah disesuaikan dengan stok yang ada.", "error");
            }
            $u = $conn->prepare("UPDATE carts SET jumlah = ? WHERE id = ? AND user_id = ?");
            $u->bind_param("iii", $jumlah, $cart_id, $user_id);
            $u->execute();
        }
    }
    header("Location: keranjang.php");
    exit;
}

$stmt = $conn->prepare("SELECT c.id AS cart_id, c.jumlah, b.id, b.judul, b.penulis, b.harga, b.stok, b.gambar, k.nama_kategori
                        FROM carts c
                        JOIN books b ON b.id = c.book_id
                        JOIN categories k ON k.id = b.category_id
                        WHERE c.user_id = ?
                        ORDER BY c.id DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = 0;
foreach ($item as $i) {
    $total += $i["harga"] * $i["jumlah"];
}

include "../includes/header.php";
?>
<main class="wadah halaman">
  <h1>Keranjang</h1>
  <?php tampil_flash(); ?>

  <?php if (!$item): ?>
    <div class="kosong">Keranjangmu masih kosong. <a href="../index.php">Yuk cari buku dulu</a>.</div>
  <?php else: ?>
  <div class="tata-letak">
    <div class="utama panel">
      <div class="tabel-wrap">
      <table>
        <thead><tr><th>Buku</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($item as $i): ?>
          <tr>
            <td>
              <b><?= e($i["judul"]) ?></b><br>
              <span class="muted"><?= e($i["penulis"]) ?></span>
            </td>
            <td><?= rupiah($i["harga"]) ?></td>
            <td>
              <form method="post" class="aksi">
                <input type="hidden" name="aksi" value="ubah">
                <input type="hidden" name="cart_id" value="<?= $i["cart_id"] ?>">
                <input type="number" name="jumlah" value="<?= $i["jumlah"] ?>" min="1" max="<?= (int)$i["stok"] ?>" style="width:80px">
                <button type="submit" class="btn garis kecil">Ubah</button>
              </form>
            </td>
            <td><b><?= rupiah($i["harga"] * $i["jumlah"]) ?></b></td>
            <td>
              <form method="post" class="sebaris" onsubmit="return confirm('Hapus buku ini dari keranjang?')">
                <input type="hidden" name="aksi" value="hapus">
                <input type="hidden" name="cart_id" value="<?= $i["cart_id"] ?>">
                <button type="submit" class="btn merah kecil">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
    <aside class="samping panel">
      <h2>Ringkasan</h2>
      <div class="baris-total"><span>Jumlah buku</span><b><?= array_sum(array_column($item, "jumlah")) ?></b></div>
      <div class="baris-total besar"><span>Total</span><span><?= rupiah($total) ?></span></div>
      <a class="btn penuh" style="margin-top:16px" href="checkout.php">Lanjut ke Checkout</a>
      <p style="text-align:center"><a href="../index.php">Lanjut belanja</a></p>
    </aside>
  </div>
  <?php endif; ?>
</main>
<?php include "../includes/footer.php"; ?>
