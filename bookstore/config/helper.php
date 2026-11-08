<?php
// Fungsi-fungsi bantuan yang dipakai di banyak halaman.

// >>> UBAH DI SINI: info rekening buat metode Transfer <<<
const INFO_REKENING = "[Nama Bank] 1234567890 a.n. [Nama Pemilik]";

// Biar teks aman ditampilkan di HTML (anti XSS)
function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// 85000 -> "Rp 85.000"
function rupiah($angka) {
    return "Rp " . number_format((int)$angka, 0, ',', '.');
}

// Pesan sekali tampil (misal "Buku berhasil ditambah")
function set_flash($pesan, $tipe = "ok") {
    $_SESSION["flash"] = ["pesan" => $pesan, "tipe" => $tipe];
}
function tampil_flash() {
    if (!empty($_SESSION["flash"])) {
        $f = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        echo '<div class="alert ' . e($f["tipe"]) . '">' . e($f["pesan"]) . '</div>';
    }
}

// Wajib login (User atau Admin)
function wajib_login($root = "../") {
    if (!isset($_SESSION["user_id"])) {
        set_flash("Silakan masuk dulu ya.", "error");
        header("Location: {$root}auth/login.php");
        exit;
    }
}

// Wajib login sebagai User (pembeli)
function wajib_user($root = "../") {
    wajib_login($root);
    if ($_SESSION["role"] !== "user") {
        set_flash("Fitur ini khusus akun User (pembeli).", "error");
        header("Location: {$root}index.php");
        exit;
    }
}

// Wajib login sebagai Admin
function wajib_admin($root = "../") {
    if (($_SESSION["role"] ?? "") !== "admin") {
        header("Location: {$root}auth/login.php");
        exit;
    }
}

// Upload gambar (buku / bukti transfer).
// Hasil: [nama_file, pesan_error]
//  - kalau nggak ada file yang dipilih: [null, null]
//  - kalau gagal: [null, "pesan error"]
function upload_gambar($file, $awalan) {
    if (!isset($file) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($file["error"] !== UPLOAD_ERR_OK) {
        return [null, "Upload gagal (kode " . $file["error"] . ")."];
    }
    if ($file["size"] > 2 * 1024 * 1024) {
        return [null, "Ukuran gambar maksimal 2 MB."];
    }
    $info = @getimagesize($file["tmp_name"]);
    if (!$info) {
        return [null, "File harus berupa gambar."];
    }
    $daftar = [IMAGETYPE_JPEG => "jpg", IMAGETYPE_PNG => "png", IMAGETYPE_WEBP => "webp"];
    if (!isset($daftar[$info[2]])) {
        return [null, "Format gambar harus JPG, PNG, atau WEBP."];
    }
    $nama = $awalan . "_" . bin2hex(random_bytes(8)) . "." . $daftar[$info[2]];
    $tujuan = __DIR__ . "/../uploads/" . $nama;
    if (!move_uploaded_file($file["tmp_name"], $tujuan)) {
        return [null, "Gagal menyimpan gambar. Cek folder uploads."];
    }
    return [$nama, null];
}

function hapus_file_upload($nama) {
    if ($nama) {
        $path = __DIR__ . "/../uploads/" . basename($nama);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

// Tampilan sampul buku: pakai gambar kalau ada, kalau nggak ada pakai blok warna
function sampul($buku, $root = "", $tinggi = 220) {
    if (!empty($buku["gambar"])) {
        return '<img src="' . $root . 'uploads/' . e($buku["gambar"]) . '" alt="Sampul ' . e($buku["judul"]) . '" style="width:100%;height:' . $tinggi . 'px;object-fit:cover;display:block">';
    }
    $warna = ["#3730A3", "#0F766E", "#9A3412", "#1E293B"];
    $w = $warna[$buku["id"] % 4];
    return '<div style="height:' . $tinggi . 'px;background:' . $w . ';color:#fff;padding:16px;font-weight:700;font-size:13px;letter-spacing:.06em;text-transform:uppercase">' . e($buku["nama_kategori"] ?? "Buku") . '</div>';
}

// Label status biar enak dibaca
function label_bayar($s) {
    $peta = [
        "menunggu" => ["Menunggu pembayaran", "kuning"],
        "menunggu_verifikasi" => ["Menunggu verifikasi", "kuning"],
        "lunas" => ["Lunas", "hijau"],
        "ditolak" => ["Bukti ditolak", "merah"],
    ];
    $p = $peta[$s] ?? [$s, "abu"];
    return '<span class="badge ' . $p[1] . '">' . e($p[0]) . '</span>';
}
function label_pesanan($s) {
    $peta = [
        "diproses" => ["Diproses", "kuning"],
        "dikirim" => ["Dikirim", "biru"],
        "selesai" => ["Selesai", "hijau"],
        "dibatalkan" => ["Dibatalkan", "merah"],
    ];
    $p = $peta[$s] ?? [$s, "abu"];
    return '<span class="badge ' . $p[1] . '">' . e($p[0]) . '</span>';
}
