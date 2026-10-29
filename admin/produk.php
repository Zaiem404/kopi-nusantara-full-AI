<?php 
require __DIR__ . "/includes/proteksi.php"; 
require __DIR__ . "/../api/lib/json_store.php"; 
 
$pathProduk = __DIR__ . "/../data/produk.json"; 
$aksi = $_GET["aksi"] ?? "daftar"; 
 
// ===== SAVE: PROSES SIMPAN (dari form tambah ATAU ubah) ===== 
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    periksaCsrf();
    if (($_POST["aksi"] ?? "simpan") === "hapus") {
        $idHapus = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
        if (!$idHapus) { http_response_code(400); exit("ID produk tidak valid."); }
        $data = bukaJsonTerkunci($pathProduk, $handle);
        $transaksi = bacaJson(__DIR__ . "/../data/transaksi.json");
        foreach ($transaksi as $t) {
            foreach ($t["items"] ?? [] as $item) {
                if (($item["produk_id"] ?? null) === $idHapus) {
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    header("Location: produk.php?pesan=" . urlencode("Produk tidak bisa dihapus karena tercatat dalam transaksi."));
                    exit;
                }
            }
        }
        $data["produk"] = array_values(array_filter($data["produk"], fn($p) => $p["id"] !== $idHapus));
        tutupJsonTerkunci($handle, $data);
        header("Location: produk.php?pesan=" . urlencode("Produk berhasil dihapus."));
        exit;
    }
    $id        = $_POST["id"] ?? null; 
    $slug      = trim($_POST["slug"]); 
    $nama      = trim($_POST["nama"]); 
    $asal      = trim($_POST["asal"]); 
    $kategori  = trim($_POST["kategori"]); 
    $harga     = (int) $_POST["harga"]; 
    $stok      = (int) $_POST["stok"]; 
    $deskripsi = trim($_POST["deskripsi"]); 
    $gambar    = trim($_POST["gambar"] ?? $_POST["foto"] ?? "");
    $alt       = trim($_POST["alt"]); 
 
    $data = bukaJsonTerkunci($pathProduk, $handle); 
 
    if ($id) { 
        // UBAH: cari indeksnya di dalam array, timpa elemen itu saja 
        foreach ($data["produk"] as $i => $p) { 
            if ($p["id"] === (int) $id) { 
                $data["produk"][$i] = [ 
                    "id" => (int) $id, "slug" => $slug, "nama" => $nama, "asal" => $asal, 
                    "kategori" => $kategori, "harga" => $harga, "stok" => $stok, 
                    "deskripsi" => $deskripsi, "gambar" => $gambar, "alt" => $alt,
                ]; 
                break; 
            } 
        } 
        $pesan = "Produk berhasil diperbarui."; 
    } else { 
        // TAMBAH: cari id tertinggi yang sudah ada, tambah array baru dengan id berikutnya 
        $idBaru = 1; 
        foreach ($data["produk"] as $p) { 
            if ($p["id"] >= $idBaru) $idBaru = $p["id"] + 1; 
        } 
        $data["produk"][] = [ 
            "id" => $idBaru, "slug" => $slug, "nama" => $nama, "asal" => $asal, 
            "kategori" => $kategori, "harga" => $harga, "stok" => $stok, 
            "deskripsi" => $deskripsi, "gambar" => $gambar, "alt" => $alt,
        ]; 
        $pesan = "Produk berhasil ditambahkan."; 
    } 
 
    tutupJsonTerkunci($handle, $data); 
 
    header("Location: produk.php?pesan=" . urlencode($pesan)); 
    exit; 
} 
 
// ===== HAPUS ===== 
if ($aksi === "hapus") { http_response_code(405); exit("Gunakan formulir untuk menghapus produk."); }
 
// ===== AMBIL DATA UNTUK FORM UBAH DAN DAFTAR ===== 
$dataBaca = bacaJson($pathProduk); 
 
$produkEdit = null; 
if ($aksi === "edit" && isset($_GET["id"])) { 
    foreach ($dataBaca["produk"] as $p) { 
        if ($p["id"] === (int) $_GET["id"]) { 
            $produkEdit = $p; 
            break; 
        } 
    } 
} 
 
$daftarProduk = $dataBaca["produk"]; 
?> 
<!DOCTYPE html> 
<html lang="id"> 
<head> 
  <meta charset="UTF-8"> 
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <title>Kelola Produk - Admin Kopi Nusantara</title> 
  <link rel="stylesheet" href="css/admin.css"> 
  <link href="https://cdn.jsdelivr.net/npm/simple-datatables@6/dist/style.css" rel="stylesheet"> 
</head> 
<body> 
  <header class="admin-header"> 
    <h1>Kelola Produk</h1> 
    <p>Halo, <?= htmlspecialchars($_SESSION["admin_nama"]) ?> &middot; <a 
href="logout.php">Keluar</a></p> 
  </header> 
  <nav class="admin-menu"> 
    <a href="dashboard.php">Beranda</a> 
    <a href="produk.php" class="active">Produk</a> 
    <a href="pengguna.php">Pengguna</a> 
    <a href="transaksi.php">Transaksi</a> 
  </nav> 
 
  <main class="admin-content"> 
    <?php if (isset($_GET["pesan"])): ?> 
      <p class="status"><?= htmlspecialchars($_GET["pesan"]) ?></p> 
    <?php endif; ?> 
 
    <?php if ($aksi === "tambah" || $aksi === "edit"): ?> 
      <h2><?= $aksi === "edit" ? "Ubah Produk" : "Tambah Produk" ?></h2> 
      <form method="post" class="form-admin">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, "UTF-8") ?>">
        <?php if ($produkEdit): ?> 
          <input type="hidden" name="id" value="<?= $produkEdit["id"] ?>"> 
        <?php endif; ?> 
        <label>Slug <input type="text" name="slug" required value="<?= htmlspecialchars($produkEdit["slug"] ?? "") ?>"></label> 
        <label>Nama <input type="text" name="nama" required value="<?= htmlspecialchars($produkEdit["nama"] ?? "") ?>"></label> 
        <label>Asal <input type="text" name="asal" required value="<?= htmlspecialchars($produkEdit["asal"] ?? "") ?>"></label> 
        <label>Kategori <input type="text" name="kategori" required value="<?= htmlspecialchars($produkEdit["kategori"] ?? "") ?>"></label> 
        <label>Harga <input type="number" name="harga" required value="<?= htmlspecialchars($produkEdit["harga"] ?? "") ?>"></label> 
        <label>Stok <input type="number" name="stok" required value="<?= htmlspecialchars($produkEdit["stok"] ?? 0) ?>"></label> 
        <label>Deskripsi <textarea name="deskripsi" rows="3"><?= htmlspecialchars($produkEdit["deskripsi"] ?? "") ?></textarea></label> 
        <label>Path gambar <input type="text" name="gambar" value="<?= htmlspecialchars($produkEdit["gambar"] ?? $produkEdit["foto"] ?? "", ENT_QUOTES, "UTF-8") ?>" placeholder="img/nama-berkas.png"></label>
        <label>Teks alternatif foto <input type="text" name="alt" value="<?= htmlspecialchars($produkEdit["alt"] ?? "") ?>"></label> 
        <button type="submit" class="btn">Simpan</button> 
        <a href="produk.php" class="btn btn-batal">Batal</a> 
      </form> 
 
    <?php else: ?> 
      <a href="produk.php?aksi=tambah" class="btn">+ Tambah Produk</a> 
      <table id="tabel-produk" class="tabel-admin"> 
        <thead> 
          <tr><th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr> 
        </thead> 
        <tbody> 
          <?php foreach ($daftarProduk as $p): ?> 
            <tr> 
              <td><?= htmlspecialchars($p["nama"]) ?></td> 
              <td><?= htmlspecialchars($p["kategori"]) ?></td> 
              <td>Rp <?= number_format($p["harga"], 0, ",", ".") ?></td> 
              <td><?= $p["stok"] ?></td> 
              <td> 
                <a href="produk.php?aksi=edit&id=<?= $p["id"] ?>">Ubah</a> 
                <form method="post" onsubmit="return confirm('Hapus produk ini?')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(tokenCsrf(), ENT_QUOTES, "UTF-8") ?>">
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= (int) $p["id"] ?>">
                  <button type="submit" class="btn btn-batal">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?> 
        </tbody> 
      </table> 
    <?php endif; ?> 
  </main> 
 
  <script src="https://cdn.jsdelivr.net/npm/simple-datatables@6"></script> 
  <script src="patch/patch-datatable.js"></script>
  <script src="js/admin.js"></script> 
</body> 
</html>
