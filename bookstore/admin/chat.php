<?php
require "../config/koneksi.php";
require_once "../config/chat_helper.php";
wajib_admin("../");
pastikan_tabel_chat($conn);

// Pembeli yang sedang dibuka percakapannya
$pilih = (int)($_GET["user"] ?? 0);
$pembeli = null;
if ($pilih > 0) {
    $s = $conn->prepare("SELECT id, nama, email FROM users WHERE id = ? AND role = 'user'");
    $s->bind_param("i", $pilih);
    $s->execute();
    $pembeli = $s->get_result()->fetch_assoc();
}

// Admin kirim balasan
if ($_SERVER["REQUEST_METHOD"] === "POST" && $pembeli) {
    $isi = trim($_POST["isi"] ?? "");
    if ($isi !== "" && mb_strlen($isi) <= 1000) {
        $ins = $conn->prepare("INSERT INTO chat_messages (user_id, pengirim, isi) VALUES (?, 'admin', ?)");
        $ins->bind_param("is", $pembeli["id"], $isi);
        $ins->execute();
    }
    header("Location: chat.php?user=" . $pembeli["id"]);
    exit;
}

$html_chat = "";
if ($pembeli) {
    // Pesan pembeli yang dibuka = dianggap sudah dibaca admin
    $tandai = $conn->prepare("UPDATE chat_messages SET dibaca = 1 WHERE user_id = ? AND pengirim = 'user' AND dibaca = 0");
    $tandai->bind_param("i", $pembeli["id"]);
    $tandai->execute();

    $ambil = $conn->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY id ASC");
    $ambil->bind_param("i", $pembeli["id"]);
    $ambil->execute();
    $daftar = $ambil->get_result()->fetch_all(MYSQLI_ASSOC);
    $html_chat = chat_html($daftar, "admin");

    if (isset($_GET["ajax"])) {
        echo $html_chat;
        exit;
    }
}

// Daftar pembeli: yang pernah chat atau pernah memesan. Yang ada pesan baru ditaruh paling atas.
$daftar_pembeli = $conn->query("SELECT u.id, u.nama, u.email,
        (SELECT COUNT(*) FROM chat_messages c WHERE c.user_id = u.id AND c.pengirim = 'user' AND c.dibaca = 0) AS belum,
        (SELECT MAX(c.id) FROM chat_messages c WHERE c.user_id = u.id) AS terakhir
    FROM users u
    WHERE u.role = 'user'
      AND (EXISTS (SELECT 1 FROM chat_messages c WHERE c.user_id = u.id)
           OR EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id))
    ORDER BY belum DESC, terakhir DESC, u.nama ASC")->fetch_all(MYSQLI_ASSOC);

$halaman_aktif = "chat";
$judul_halaman = "Chat";
include "_header.php";
chat_css();
?>
<h1>Chat dengan Pembeli</h1>
<p class="muted">Pilih pembeli di sebelah kiri, lalu balas pesannya.</p>

<div class="tata-letak">
  <aside class="samping panel daftar-chat" style="flex:1 1 240px;max-width:320px">
    <h2 style="font-size:16px">Pembeli</h2>
    <?php if (!$daftar_pembeli): ?>
      <p class="muted">Belum ada pembeli yang chat atau memesan.</p>
    <?php endif; ?>
    <?php foreach ($daftar_pembeli as $p): ?>
      <a href="chat.php?user=<?= $p["id"] ?>" class="<?= ($pembeli && $pembeli["id"] == $p["id"]) ? "aktif" : "" ?>">
        <span><?= e($p["nama"]) ?></span>
        <?php if ($p["belum"] > 0): ?><span class="angka"><?= (int)$p["belum"] ?></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </aside>

  <div class="utama" style="flex:999 1 420px;min-width:0">
    <?php if (!$pembeli): ?>
      <div class="kosong">Belum ada percakapan yang dipilih.</div>
    <?php else: ?>
      <h2 style="margin-bottom:10px;font-size:18px"><?= e($pembeli["nama"]) ?> <span class="muted">&lt;<?= e($pembeli["email"]) ?>&gt;</span></h2>
      <div class="chat-box" id="kotak-chat"><?= $html_chat ?></div>

      <form method="post" class="chat-form">
        <textarea name="isi" id="isi" rows="2" maxlength="1000" placeholder="Tulis balasan..." aria-label="Tulis balasan" required></textarea>
        <button type="submit">Kirim</button>
      </form>

      <div class="templat">
        <button type="button" class="btn garis kecil" data-teks="Terima kasih sudah pesan buku di Rumah Baca!">Terima kasih sudah pesan</button>
        <button type="button" class="btn garis kecil" data-teks="Pesananmu sedang kami proses ya.">Pesanan diproses</button>
        <button type="button" class="btn garis kecil" data-teks="Pesananmu sudah kami kirim. Mohon ditunggu ya.">Pesanan dikirim</button>
        <button type="button" class="btn garis kecil" data-teks="Pembayaranmu sudah kami terima. Terima kasih!">Pembayaran diterima</button>
      </div>

      <script>
      const kotak = document.getElementById('kotak-chat');
      const isi = document.getElementById('isi');
      let terakhir = <?= json_encode($html_chat) ?>;
      function turun() { kotak.scrollTop = kotak.scrollHeight; }
      turun();

      // Tombol template: isi kolom ketik, admin tinggal klik Kirim
      document.querySelectorAll('[data-teks]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
          isi.value = tombol.getAttribute('data-teks');
          isi.focus();
        });
      });

      // Segarkan isi chat tiap 5 detik tanpa mengganggu kolom ketik
      setInterval(function () {
        fetch('chat.php?user=<?= (int)$pembeli["id"] ?>&ajax=1', { credentials: 'same-origin' })
          .then(function (r) { return r.text(); })
          .then(function (html) {
            if (html !== terakhir) {
              const dekat = kotak.scrollHeight - kotak.scrollTop - kotak.clientHeight < 80;
              terakhir = html;
              kotak.innerHTML = html;
              if (dekat) turun();
            }
          });
      }, 5000);
      </script>
    <?php endif; ?>
  </div>
</div>
<?php include "_footer.php"; ?>
