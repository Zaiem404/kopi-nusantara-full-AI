<?php 

//latihan 2 : no 4

require __DIR__ . "/lib/json_store.php"; 
header("Content-Type: application/json"); 
$data = bacaJson(__DIR__ . "/../data/produk.json");

//latihan 2
$kategori = $_GET["kategori"] ?? ""; //jika isian kosong maka tidak ada erory

if ($kategori !== "") {
    $hasil = array_filter($data["produk"], function ($p) use ($kategori){
        return $p["kategori"] === $kategori;
    });
    $data ["produk"] = array_values($hasil);
}
echo json_encode($data);