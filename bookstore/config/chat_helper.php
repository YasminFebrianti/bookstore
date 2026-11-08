<?php
// Fungsi-fungsi untuk fitur chat Admin <-> User.
// Satu pembeli = satu percakapan dengan admin. Semua pesan disimpan di tabel chat_messages.

// Bikin tabel chat otomatis kalau belum ada (jadi nggak perlu import SQL manual)
function pastikan_tabel_chat($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        pengirim ENUM('user','admin') NOT NULL,
        isi TEXT NOT NULL,
        dibaca TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
}

// Jumlah balasan admin yang belum dibaca oleh seorang pembeli (untuk angka kecil di menu)
function chat_belum_dibaca_user($conn, $user_id) {
    try {
        $st = $conn->prepare("SELECT COUNT(*) FROM chat_messages WHERE user_id = ? AND pengirim = 'admin' AND dibaca = 0");
        $st->bind_param("i", $user_id);
        $st->execute();
        $st->bind_result($jumlah);
        $st->fetch();
        $st->close();
        return (int)$jumlah;
    } catch (Throwable $ex) {
        return 0; // tabel chat belum dibuat
    }
}

// Jumlah pesan dari pembeli yang belum dibaca admin
function chat_belum_dibaca_admin($conn) {
    try {
        $r = $conn->query("SELECT COUNT(*) FROM chat_messages WHERE pengirim = 'user' AND dibaca = 0");
        return (int)$r->fetch_row()[0];
    } catch (Throwable $ex) {
        return 0;
    }
}

// Gaya tampilan gelembung chat
function chat_css() {
    echo '<style>
.chat-box{background:#fff;border:1px solid #E2E6EE;border-radius:16px;padding:16px;height:420px;overflow-y:auto;display:flex;flex-direction:column;gap:10px}
.gelembung{max-width:75%;padding:10px 14px;border-radius:14px;font-size:15px;word-wrap:break-word;overflow-wrap:anywhere}
.gelembung small{display:block;font-size:11px;opacity:.8;margin-top:4px}
.gelembung.saya{align-self:flex-end;background:#6D28D9;color:#fff;border-bottom-right-radius:4px}
.gelembung.lawan{align-self:flex-start;background:#EDE9FE;color:#1F1147;border-bottom-left-radius:4px}
.chat-form{display:flex;gap:8px;margin-top:12px;align-items:flex-end}
.chat-form textarea{flex:1;min-height:46px}
.chat-kosong{margin:auto;text-align:center;color:#4B5563;font-size:14px}
.templat{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.daftar-chat a{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;color:#111827}
.daftar-chat a:hover{background:#F5F3FF;text-decoration:none}
.daftar-chat a.aktif{background:#EDE9FE;font-weight:700}
</style>';
}

// Ubah daftar pesan jadi HTML gelembung. $saya = "user" atau "admin" (siapa yang sedang melihat)
function chat_html($daftar, $saya) {
    if (!$daftar) {
        return '<p class="chat-kosong">Belum ada pesan. Tulis pesan pertama di bawah ya.</p>';
    }
    $hasil = "";
    foreach ($daftar as $m) {
        $kelas = ($m["pengirim"] === $saya) ? "saya" : "lawan";
        $nama  = ($m["pengirim"] === "admin") ? "Admin" : "Pembeli";
        $hasil .= '<div class="gelembung ' . $kelas . '">' . nl2br(e($m["isi"]))
                . '<small>' . e($nama) . ' &middot; ' . date("d M H:i", strtotime($m["created_at"])) . '</small></div>';
    }
    return $hasil;
}
