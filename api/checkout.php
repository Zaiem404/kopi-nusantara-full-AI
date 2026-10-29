<?php 
require __DIR__ . "/lib/json_store.php"; 
 
header("Content-Type: application/json"); 
 
$raw = file_get_contents("php://input");
$payload = $raw === false || strlen($raw) > 65536 ? null : json_decode($raw, true);

if (!is_array($payload) || !isset($payload["items"]) || !is_array($payload["items"]) || $payload["items"] === [] || count($payload["items"]) > 50) { 
    http_response_code(400); 
    echo json_encode(["error" => "Data pesanan tidak lengkap."]); 
    exit; 
} 
 
$namaPemesan = trim($payload["nama_pemesan"] ?? ""); 
$kontak      = trim($payload["kontak"] ?? ""); 
$items       = $payload["items"]; 
 
if ($namaPemesan === "" || strlen($namaPemesan) > 120 || $kontak === "" || strlen($kontak) > 40) { 
    http_response_code(400); 
    echo json_encode(["error" => "Nama dan kontak wajib diisi."]); 
    exit; 
} 
 
$pathProduk    = __DIR__ . "/../data/produk.json"; 
$pathTransaksi = __DIR__ . "/../data/transaksi.json"; 
 
// Kunci produk.json SEPANJANG proses checkout -- dibaca di sini, 
// baru ditulis ulang di akhir (atau tidak sama sekali jika validasi gagal). 
$handleProduk = null;
$handleTransaksi = null;
$handleTransaksiTerkunci = false;
$dataTransaksiLama = null;
try { 
    $dataProduk = bukaJsonTerkunci($pathProduk, $handleProduk);
    if (!isset($dataProduk["produk"]) || !is_array($dataProduk["produk"])) throw new RuntimeException("Data produk tidak valid.");
    $total   = 0; 
    $rincian = []; 
    $jumlahPerProduk = [];

    foreach ($items as $item) { 
        if (!is_array($item) || !isset($item["produk_id"], $item["jumlah"]) || filter_var($item["produk_id"], FILTER_VALIDATE_INT) === false || filter_var($item["jumlah"], FILTER_VALIDATE_INT) === false) throw new RuntimeException("Rincian pesanan tidak valid.");
        $produkId = (int) $item["produk_id"];
        $jumlah   = (int) $item["jumlah"];
        if ($produkId <= 0 || $jumlah <= 0 || $jumlah > 100) throw new RuntimeException("Jumlah atau produk tidak valid.");
        $jumlahPerProduk[$produkId] = ($jumlahPerProduk[$produkId] ?? 0) + $jumlah;
        if ($jumlahPerProduk[$produkId] > 100) throw new RuntimeException("Jumlah pesanan terlalu besar.");
 
        // Cari produk yang cocok di dalam array -- pengganti "WHERE id = ..." 
        $indeks = null; 
        foreach ($dataProduk["produk"] as $i => $p) { 
            if ($p["id"] === $produkId) { $indeks = $i; break; } 
        } 
 
        if ($indeks === null) { 
            throw new InvalidArgumentException("Produk tidak ditemukan.");
        } 
        // Harga diambil dari BERKAS, bukan dari data yang dikirim klien. 
        $hargaAsli = (int) $dataProduk["produk"][$indeks]["harga"]; 
        $rincianIndeks = null;
        foreach ($rincian as $ri => $r) if ($r["produk_id"] === $produkId) { $rincianIndeks = $ri; break; }
        if ($rincianIndeks !== null) $rincian[$rincianIndeks]["jumlah"] += $jumlah;
        else $rincian[] = ["indeks" => $indeks, "produk_id" => $produkId, "jumlah" => $jumlah, "harga" => $hargaAsli];

        $total += $hargaAsli * $jumlah;
    } 
 
    // ===== Seluruh item lolos validasi -- BARU di sini mulai menulis ===== 
 
    foreach ($rincian as $r) { 
        if ($dataProduk["produk"][$r["indeks"]]["stok"] < $r["jumlah"]) throw new InvalidArgumentException("Stok {$dataProduk['produk'][$r['indeks']]['nama']} tidak mencukupi (tersisa {$dataProduk['produk'][$r['indeks']]['stok']}).");
        $dataProduk["produk"][$r["indeks"]]["stok"] -= $r["jumlah"]; 
    } 
 
    $kodeTransaksi = buatKodeTransaksi(); 
    $dataTransaksi = bukaJsonTerkunci($pathTransaksi, $handleTransaksi);
    $handleTransaksiTerkunci = true;
    $dataTransaksiLama = $dataTransaksi;
    if (!array_is_list($dataTransaksi)) throw new RuntimeException("Data transaksi tidak valid.");
 
    $idBaru = 1; 
    foreach ($dataTransaksi as $t) { 
        if ($t["id"] >= $idBaru) $idBaru = $t["id"] + 1; 
    } 
 
    $dataTransaksi[] = [ 
        "id"             => $idBaru, 
        "kode_transaksi" => $kodeTransaksi, 
        "nama_pemesan"   => $namaPemesan, 
        "kontak"         => $kontak, 
        "total"          => $total, 
        "status"         => "baru", 
        "dibuat_pada"    => date("Y-m-d H:i:s"), 
        "items"          => array_map(function ($r) use ($dataProduk) {
            return [ 
                "produk_id"       => $r["produk_id"], 
                "jumlah"          => $r["jumlah"], 
                "harga_saat_beli" => $r["harga"],
                "nama_produk"    => $dataProduk["produk"][$r["indeks"]]["nama"],
            ]; 
        }, $rincian), 
    ]; 
 
    tutupJsonTerkunci($handleTransaksi, $dataTransaksi);
    $handleTransaksiTerkunci = false;
    tutupJsonTerkunci($handleProduk, $dataProduk);
    $handleProduk = null;
 
    echo json_encode([ 
        "kode_transaksi" => $kodeTransaksi, 
        "total"          => $total, 
    ]); 
} catch (Throwable $e) {
    if ($handleTransaksiTerkunci && is_resource($handleTransaksi)) {
        flock($handleTransaksi, LOCK_UN);
        fclose($handleTransaksi);
    }
    if (is_resource($handleProduk)) {
        flock($handleProduk, LOCK_UN);
        fclose($handleProduk);
    }
    // Jika stok gagal disimpan setelah transaksi, pulihkan data transaksi sebelumnya.
    if (is_array($dataTransaksiLama)) {
        try {
            $rollback = bukaJsonTerkunci($pathTransaksi, $handleRollback);
            tutupJsonTerkunci($handleRollback, $dataTransaksiLama);
        } catch (Throwable $rollbackError) {
            error_log("Checkout rollback gagal: " . $rollbackError->getMessage());
        }
    }
    error_log("Checkout gagal: " . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
    echo json_encode(["error" => $e instanceof InvalidArgumentException ? $e->getMessage() : "Pesanan gagal diproses. Silakan coba lagi."]);
}
