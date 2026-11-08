<?php
// Koneksi ke database + mulai session (dipakai di semua halaman)
$host = "localhost";
$user = "root";
$pass = "";          // XAMPP default: password kosong
$db   = "bookstore";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/helper.php";
