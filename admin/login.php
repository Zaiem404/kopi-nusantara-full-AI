<?php 
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(["httponly" => true, "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off", "samesite" => "Lax"]);
    session_start();
}
require __DIR__ . "/../api/lib/json_store.php"; 
 
$pesanGalat = ""; 
 
if ($_SERVER["REQUEST_METHOD"] === "POST") { 
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || !isset($_SESSION["login_csrf"]) || !hash_equals($_SESSION["login_csrf"], $token)) {
        http_response_code(403);
        exit("Permintaan ditolak.");
    }
    $limitPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "kopi-login-" . hash("sha256", $_SERVER["REMOTE_ADDR"] ?? "unknown");
    $limitHandle = fopen($limitPath, "c+");
    if ($limitHandle === false || !flock($limitHandle, LOCK_EX)) {
        http_response_code(503);
        exit("Login sementara tidak tersedia.");
    }
    $attempts = array_filter(array_map("intval", explode("\n", stream_get_contents($limitHandle))), fn($time) => $time > time() - 900);
    if (count($attempts) >= 10) {
        flock($limitHandle, LOCK_UN);
        fclose($limitHandle);
        http_response_code(429);
        exit("Terlalu banyak percobaan login. Coba lagi dalam 15 menit.");
    }
    $attempts[] = time();
    rewind($limitHandle);
    ftruncate($limitHandle, 0);
    fwrite($limitHandle, implode("\n", $attempts));
    fflush($limitHandle);
    flock($limitHandle, LOCK_UN);
    fclose($limitHandle);
    $username = trim($_POST["username"] ?? ""); 
    $sandi    = $_POST["sandi"] ?? ""; 
 
    $dataPengguna = bacaJson(__DIR__ . "/../data/pengguna.json"); 
 
    $admin = null; 
    foreach ($dataPengguna as $p) { 
        if ($p["username"] === $username) { 
            $admin = $p; 
            break; 
        } 
    } 
 
    if ($admin && password_verify($sandi, $admin["kata_sandi"])) { 
        session_regenerate_id(true);
        $_SESSION["admin_id"]   = $admin["id"]; 
        $_SESSION["admin_nama"] = $admin["nama"]; 
        header("Location: dashboard.php"); 
        exit; 
    } 
 
    $pesanGalat = "Username atau kata sandi salah."; 
} 
?> 
<!DOCTYPE html> 
<html lang="id"> 
<head> 
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <title>Login Admin - Kopi Nusantara</title> 
  <link rel="stylesheet" href="css/admin.css"> 
</head> 
<body class="halaman-login"> 
  <form method="post" class="form-login"> 
    <h1>Login Admin</h1>
    <?php $_SESSION["login_csrf"] = bin2hex(random_bytes(32)); ?>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["login_csrf"], ENT_QUOTES, "UTF-8") ?>">
    <?php if ($pesanGalat): ?> 
      <p class="pesan-galat"><?= htmlspecialchars($pesanGalat) ?></p> 
    <?php endif; ?> 
    <label>Username <input type="text" name="username" required autofocus></label> 
    <label>Kata Sandi <input type="password" name="sandi" required></label> 
    <button type="submit" class="btn">Masuk</button> 
  </form> 
</body> 
</html>
