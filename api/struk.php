<?php 
require __DIR__ . "/../vendor/autoload.php"; 
require __DIR__ . "/lib/json_store.php"; 
 
$kode = $_GET["kode"] ?? ""; 
 
$dataTransaksi = bacaJson(__DIR__ . "/../data/transaksi.json"); 
 
// Cari transaksi yang kode_transaksi-nya cocok -- pengganti "WHERE kode_transaksi = ..." 
$transaksi = null; 
foreach ($dataTransaksi as $t) { 
    if ($t["kode_transaksi"] === $kode) { 
        $transaksi = $t; 
        break; 
    } 
} 
 
if (!$transaksi) { 
    http_response_code(404); 
    die("Transaksi tidak ditemukan."); 
} 
 
$dataProduk = bacaJson(__DIR__ . "/../data/produk.json"); 
 
// Mencari nama produk berdasarkan id -- pengganti "JOIN produk" 
function cariNamaProduk(array $dataProduk, int $produkId): string { 
    foreach ($dataProduk["produk"] as $p) { 
        if ($p["id"] === $produkId) { 
            return $p["nama"]; 
        } 
    } 
    return "(produk tidak ditemukan)"; 
} 
 
// FPDF memakai Latin-1, bukan UTF-8 -- setiap teks dinamis dibungkus fungsi ini. 
function teks(string $s): string { 
    return iconv("UTF-8", "ISO-8859-1//TRANSLIT", $s); 
} 
 
function rupiahPdf(int $angka): string {
    return "Rp " . number_format($angka, 0, ",", ".");
}

$pdf = new FPDF();
$pdf->AddPage();

// Logo di tengah: posisi x dihitung dari lebar halaman, bukan angka tetap,
// supaya tetap center walau ukuran halaman/logo diubah.
$lebarLogo = 22;
$pdf->Image(
    __DIR__ . "/../img/logo.png",
    ($pdf->GetPageWidth() - $lebarLogo) / 2,
    10,
    $lebarLogo
);

// Judul turun ke bawah logo supaya tidak bertumpuk dengan gambar.
$pdf->SetY(36);
$pdf->SetFont("Arial", "B", 16);
$pdf->Cell(0, 8, teks("Struk Kopi Nusantara"), 0, 1, "C");

// Garis pemisah tipis selebar area cetak (margin kiri/kanan 10 mm).
$pdf->SetDrawColor(180, 180, 180);
$pdf->Line(10, $pdf->GetY(), $pdf->GetPageWidth() - 10, $pdf->GetY());
$pdf->SetDrawColor(0, 0, 0);
$pdf->Ln(5);

// Label dan titik dua dipisah kolom agar titik duanya rata vertikal.
$pdf->SetFont("Arial", "", 11);
$labelTransaksi = ["Kode Transaksi", "Nama Pemesan", "Kontak", "Tanggal"];
$nilaiTransaksi = [
    $transaksi["kode_transaksi"],
    $transaksi["nama_pemesan"],
    $transaksi["kontak"],
    $transaksi["dibuat_pada"],
];
foreach ($labelTransaksi as $i => $label) {
    // 30 mm = label terpanjang ("Nama Pemesan" +/- 28 mm) + sedikit jeda.
    $pdf->Cell(30, 7, teks($label), 0, 0);
    $pdf->Cell(4, 7, ":", 0, 0, "C");
    $pdf->Cell(0, 7, teks($nilaiTransaksi[$i]), 0, 1);
}
$pdf->Ln(4);

// Lebar kolom dijumlahkan 190 mm = lebar A4 (210) - margin 10 mm kiri/kanan.
$lebarProduk   = 95;
$lebarJumlah   = 25;
$lebarHarga    = 35;
$lebarSubtotal = 35;

$pdf->SetFont("Arial", "B", 10);
$pdf->SetFillColor(238, 238, 238);   // abu-abu muda untuk baris judul tabel
$pdf->Cell($lebarProduk, 8, teks("Produk"), 1, 0, "L", true);
$pdf->Cell($lebarJumlah, 8, teks("Jumlah"), 1, 0, "C", true);
$pdf->Cell($lebarHarga, 8, teks("Harga"), 1, 0, "R", true);
$pdf->Cell($lebarSubtotal, 8, teks("Subtotal"), 1, 1, "R", true);

$pdf->SetFont("Arial", "", 10);
foreach ($transaksi["items"] as $d) {
    $nama     = $d["nama_produk"] ?? cariNamaProduk($dataProduk, $d["produk_id"]);
    $subtotal = $d["harga_saat_beli"] * $d["jumlah"];
    $pdf->Cell($lebarProduk, 8, teks($nama), 1, 0, "L");
    $pdf->Cell($lebarJumlah, 8, (string) $d["jumlah"], 1, 0, "C");
    $pdf->Cell($lebarHarga, 8, teks(rupiahPdf($d["harga_saat_beli"])), 1, 0, "R");
    $pdf->Cell($lebarSubtotal, 8, teks(rupiahPdf($subtotal)), 1, 1, "R");
}

$pdf->SetFont("Arial", "B", 10);
$pdf->Cell($lebarProduk + $lebarJumlah + $lebarHarga, 8, teks("Total"), 1, 0, "R", true);
$pdf->Cell($lebarSubtotal, 8, teks(rupiahPdf((int) $transaksi["total"])), 1, 1, "R", true);

// Catatan penutup
$pdf->Ln(6);
$pdf->SetFont("Arial", "I", 9);
$pdf->Cell(0, 6, teks("Terima kasih telah memesan di Kopi Nusantara."), 0, 1, "C");

$namaBerkas = "struk-" . $transaksi["kode_transaksi"] . ".pdf";
$dest = isset($_GET["unduh"]) ? "D" : "I";
$pdf->Output($dest, $namaBerkas);
