<?php 
// Skrip SEKALI JALAN: membuat akun admin pertama. 
// Jalankan hanya lewat CLI. Hapus skrip ini setelah akun awal dibuat.
if (PHP_SAPI !== "cli") { http_response_code(404); exit; }
require __DIR__ . "/../api/lib/json_store.php";
$path = __DIR__ . "/../data/pengguna.json";
$password = getenv("ADMIN_INITIAL_PASSWORD");
if (!is_string($password) || strlen($password) < 12) {
    fwrite(STDERR, "Set ADMIN_INITIAL_PASSWORD (minimal 12 karakter) sebelum menjalankan skrip.\n");
    exit(1);
}
$data = bukaJsonTerkunci($path, $handle);
if ($data !== []) {
    flock($handle, LOCK_UN);
    fclose($handle);
    fwrite(STDERR, "Akun admin sudah ada; skrip berhenti tanpa perubahan.\n");
    exit(1);
}
$idBaru = 1; 
foreach ($data as $p) { 
if ($p["id"] >= $idBaru) $idBaru = $p["id"] + 1; 
} 
$data[] = [ 
"id"         
=> $idBaru,  "nama"       => "Admin Kopi Nusantara", 
    "username"   => "admin", 
    "kata_sandi" => password_hash($password, PASSWORD_DEFAULT),
]; 
 
tutupJsonTerkunci($handle, $data); 
 
echo "Admin pertama dibuat. Username: admin\n"; 
echo "SEGERA hapus atau ganti nama berkas ini setelah dijalankan.\n";
