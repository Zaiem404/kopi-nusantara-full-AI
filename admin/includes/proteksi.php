<?php 
// Disertakan di AWAL setiap halaman admin, SEBELUM HTML apa pun dikirim. 
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        "httponly" => true,
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "samesite" => "Lax",
    ]);
    session_start();
}

function tokenCsrf(): string {
    return $_SESSION["csrf_token"] ??= bin2hex(random_bytes(32));
}

function periksaCsrf(): void {
    $token = $_POST["csrf_token"] ?? "";
    if (!is_string($token) || !hash_equals(tokenCsrf(), $token)) {
        http_response_code(403);
        exit("Permintaan ditolak.");
    }
}

if (!isset($_SESSION["admin_id"])) { 
header("Location: login.php"); 
exit; 
}
