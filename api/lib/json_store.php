<?php 
// Versi "ketat": mengunci berkas SEPANJANG proses baca-ubah-tulis, 
// dipakai khusus untuk operasi yang benar-benar tidak boleh tertimpa 
// proses lain (mis. checkout yang mengurangi stok -- lihat Modul 5F). 
 
function bacaJson(string $path): array { 
    $lock = fopen($path . ".lock", "c");
    if ($lock === false || !flock($lock, LOCK_SH)) throw new RuntimeException("Gagal mengunci berkas data.");
    try {
        if (!is_file($path) || !is_readable($path)) throw new RuntimeException("Berkas data tidak dapat dibaca.");
        $teks = file_get_contents($path);
        if ($teks === false) throw new RuntimeException("Gagal membaca berkas data.");
        $data = json_decode($teks, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) throw new RuntimeException("Berkas data JSON rusak.");
        return $data;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
} 
 
function tulisJson(string $path, array $data): bool { 
    $teks = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $lock = fopen($path . ".lock", "c");
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException("Gagal mengunci berkas data.");
    try {
        $sementara = tempnam(dirname($path), ".json-");
        if ($sementara === false) throw new RuntimeException("Gagal membuat berkas sementara.");
        try {
            if (file_put_contents($sementara, $teks, LOCK_EX) !== strlen($teks) || !rename($sementara, $path)) {
                throw new RuntimeException("Gagal menyimpan berkas data.");
            }
        } finally {
            if (is_file($sementara)) unlink($sementara);
        }
        return true;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

function bukaJsonTerkunci(string $path, &$handle): array { 
    $handle = fopen($path . ".lock", "c");
    if ($handle === false || !flock($handle, LOCK_EX)) throw new RuntimeException("Gagal mengunci berkas data.");
    try {
        if (!is_file($path) || !is_readable($path)) throw new RuntimeException("Berkas data tidak dapat dibaca.");
        $teks = file_get_contents($path);
        if ($teks === false) throw new RuntimeException("Gagal membaca berkas data.");
        $data = json_decode($teks, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) throw new RuntimeException("Berkas data JSON rusak.");
        return $data;
    } catch (Throwable $e) {
        flock($handle, LOCK_UN);
        fclose($handle);
        throw $e;
    }
} 
 
function tutupJsonTerkunci($handle, array $data): void { 
    try {
        $path = stream_get_meta_data($handle)["uri"];
        $path = substr($path, 0, -5); // lepaskan akhiran .lock
        $teks = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $sementara = tempnam(dirname($path), ".json-");
        if ($sementara === false) throw new RuntimeException("Gagal membuat berkas sementara.");
        try {
            if (file_put_contents($sementara, $teks, LOCK_EX) !== strlen($teks) || !rename($sementara, $path)) {
                throw new RuntimeException("Gagal menyimpan berkas data.");
            }
        } finally {
            if (is_file($sementara)) unlink($sementara);
        }
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

function buatKodeTransaksi(): string { 
    return "KN-" . strtoupper(bin2hex(random_bytes(4))); }
