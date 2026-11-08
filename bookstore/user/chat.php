<?php
require "../config/koneksi.php";
require_once "../config/chat_helper.php";
wajib_user("../");
pastikan_tabel_chat($conn);

$user_id = $_SESSION["user_id"];

// Kirim pesan baru (lalu balik ke halaman chat supaya nggak terkirim dobel kalau di-refresh)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $isi = trim($_POST["isi"] ?? "");
    if ($isi !== "" && mb_strlen($isi) <= 1000) {
        $ins = $conn->prepare("INSERT INTO chat_messages (user_id, pengirim, isi) VALUES (?, 'user', ?)");
        $ins->bind_param("is", $user_id, $isi);
        $ins->execute();
    }
    header("Location: chat.php");
    exit;
}

// Balasan admin yang dibuka = dianggap sudah dibaca
$tandai = $conn->prepare("UPDATE chat_messages SET dibaca = 1 WHERE user_id = ? AND pengirim = 'admin' AND dibaca = 0");
$tandai->bind_param("i", $user_id);
$tandai->execute();

$ambil = $conn->prepare("SELECT * FROM chat_messages WHERE user_id = ? ORDER BY id ASC");
$ambil->bind_param("i", $user_id);
$ambil->execute();
$daftar = $ambil->get_result()->fetch_all(MYSQLI_ASSOC);
$html_chat = chat_html($daftar, "user");

// Permintaan penyegaran otomatis: cukup kirim isi chat-nya saja
if (isset($_GET["ajax"])) {
    echo $html_chat;
    exit;
}

$root = "../";
$judul_halaman = "Chat dengan Admin";
include "../includes/header.php";
chat_css();
?>
<main class="wadah halaman">
  <h1>Chat dengan Admin</h1>
  <p class="muted" style="margin-top:-10px">Tanya soal pesananmu di sini. Balasan admin muncul otomatis.</p>

  <div class="chat-box" id="kotak-chat"><?= $html_chat ?></div>

  <form method="post" class="chat-form">
    <textarea name="isi" rows="2" maxlength="1000" placeholder="Tulis pesan..." aria-label="Tulis pesan" required></textarea>
    <button type="submit">Kirim</button>
  </form>
</main>

<script>
// Segarkan isi chat tiap 5 detik tanpa mengganggu kolom ketik
const kotak = document.getElementById('kotak-chat');
let terakhir = <?= json_encode($html_chat) ?>;
function turun() { kotak.scrollTop = kotak.scrollHeight; }
turun();
setInterval(function () {
  fetch('chat.php?ajax=1', { credentials: 'same-origin' })
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
<?php include "../includes/footer.php"; ?>
