// keranjang.js: mengelola keranjang belanja di localStorage.
// CATATAN: harga di sini hanya untuk tampilan. Server (checkout) yang
// menentukan harga sesungguhnya -- localStorage tidak dipercaya.

const KUNCI_KERANJANG = "kopi-nusantara-keranjang";

function ambilKeranjang() {
  const data = localStorage.getItem(KUNCI_KERANJANG);
  return data ? JSON.parse(data) : [];
}

function simpanKeranjang(keranjang) {
  localStorage.setItem(KUNCI_KERANJANG, JSON.stringify(keranjang));
}

function tambahKeKeranjang(produk) {
  const keranjang = ambilKeranjang();
  const ada = keranjang.find(function (item) {
    return item.produk_id === produk.produk_id;
  });

  if (ada) {
    ada.jumlah += 1;
  } else {
    keranjang.push({
      produk_id: produk.produk_id,
      slug: produk.slug,
      nama: produk.nama,
      harga: produk.harga,
      jumlah: 1,
    });
  }

  simpanKeranjang(keranjang);
  perbaruiBadgeKeranjang();
}

function ubahJumlah(produkId, delta) {
  const keranjang = ambilKeranjang();
  const item = keranjang.find(function (i) {
    return i.produk_id === produkId;
  });
  if (!item) return;

  item.jumlah += delta;

  const keranjangBaru =
    item.jumlah <= 0
      ? keranjang.filter(function (i) {
          return i.produk_id !== produkId;
        })
      : keranjang;

  simpanKeranjang(keranjangBaru);
  renderKeranjang();
  perbaruiBadgeKeranjang();
}
function hapusDariKeranjang(produkId) {
  const keranjang = ambilKeranjang().filter(function (i) {
    return i.produk_id !== produkId;
  });
  simpanKeranjang(keranjang);
  renderKeranjang();
  perbaruiBadgeKeranjang();
}

function hitungTotalKeranjang() {
  return ambilKeranjang().reduce(function (total, item) {
    return total + item.harga * item.jumlah;
  }, 0);
}

function perbaruiBadgeKeranjang() {
  const badge = document.getElementById("badge-keranjang");
  if (!badge) return;

  const jumlahItem = ambilKeranjang().reduce(function (t, i) {
    return t + i.jumlah;
  }, 0);

  badge.textContent = jumlahItem;
  badge.hidden = jumlahItem === 0;
}

function inisialisasiKeranjang() {
  perbaruiBadgeKeranjang();
}
function hapusDariKeranjang(produkId) {
  const keranjang = ambilKeranjang().filter(function (i) {
    return i.produk_id !== produkId;
  });
  simpanKeranjang(keranjang);
  renderKeranjang();
  perbaruiBadgeKeranjang();
}

function hitungTotalKeranjang() {
  return ambilKeranjang().reduce(function (total, item) {
    return total + item.harga * item.jumlah;
  }, 0);
}

function perbaruiBadgeKeranjang() {
  const badge = document.getElementById("badge-keranjang");
  if (!badge) return;

  const jumlahItem = ambilKeranjang().reduce(function (t, i) {
    return t + i.jumlah;
  }, 0);

  badge.textContent = jumlahItem;
  badge.hidden = jumlahItem === 0;
}

function renderKeranjang() {
  const isiEl = document.getElementById("isi-keranjang");
  const totalEl = document.getElementById("total-keranjang");
  const keranjang = ambilKeranjang();

  if (keranjang.length === 0) {
    isiEl.innerHTML =
      '<div class="alert alert-light border mb-0" role="status">Keranjang masih kosong. <a class="alert-link" href="#produk">Pilih produk</a> untuk memulai.</div>';
    totalEl.textContent = rupiah(0);
    return;
  }

  isiEl.innerHTML = keranjang
    .map(function (item) {
      return ` 
      <div class="baris-keranjang row align-items-center g-2 border-bottom py-3"> 
        <span class="col-12 col-md-4 fw-semibold">${esc(item.nama)}</span> 
        <div class="col-6 col-md-3 d-flex align-items-center gap-2"> 
          <button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Kurangi jumlah ${esc(item.nama)}" data-aksi="kurang" data-id="${item.produk_id}">−</button> 
          <span aria-label="Jumlah">${item.jumlah}</span> 
          <button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Tambah jumlah ${esc(item.nama)}" data-aksi="tambah" data-id="${item.produk_id}">+</button> 
        </div> 
        <span class="col-6 col-md-3 text-end fw-semibold">${rupiah(item.harga * item.jumlah)}</span> 
        <div class="col-12 col-md-2 text-md-end"> 
          <button type="button" class="btn btn-sm btn-outline-danger" data-aksi="hapus" data-id="${item.produk_id}">Hapus</button> 
        </div> 
      </div> 
    `;
    })
    .join("");

  totalEl.textContent = rupiah(hitungTotalKeranjang());
}

function pasangAksiKeranjang() {
  document
    .getElementById("isi-keranjang")
    .addEventListener("click", function (e) {
      const tombol = e.target.closest("[data-aksi]");
      if (!tombol) return;

      const id = Number(tombol.dataset.id);
      const aksi = tombol.dataset.aksi;

      if (aksi === "tambah") ubahJumlah(id, 1);
      if (aksi === "kurang") ubahJumlah(id, -1);
      if (aksi === "hapus") hapusDariKeranjang(id);
    });
}

function pasangFormCheckout() {
  const form = document.getElementById("form-checkout");
  const statusEl = document.getElementById("status-checkout");

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    const keranjang = ambilKeranjang();
    if (keranjang.length === 0) {
      statusEl.textContent = "Keranjang masih kosong.";
      return;
    }

    const payload = {
      nama_pemesan: form.nama_pemesan.value.trim(),
      kontak: form.kontak.value.trim(),
      items: keranjang.map(function (item) {
        return { produk_id: item.produk_id, jumlah: item.jumlah };
      }),
    };

    statusEl.textContent = "Memproses pesanan...";

    try {
      const respons = await fetch("api/checkout.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      const hasil = await respons.json();

      if (!respons.ok) {
        statusEl.textContent = hasil.error || "Checkout gagal.";
        return;
      }

      simpanKeranjang([]);
      perbaruiBadgeKeranjang();
      tampilkanKonfirmasi(hasil);
    } catch (err) {
      statusEl.textContent = "Tidak dapat terhubung ke server.";
      console.error(err);
    }
  });
}

const NOMOR_ADMIN_WA = "6281228427862";

function tampilkanKonfirmasi(hasil) {
  // GANTI dengan nomor WhatsApp admin sesungguhnya
  const isiEl = document.getElementById("isi-keranjang");
  const totalEl = document.getElementById("total-keranjang");
  const formEl = document.getElementById("form-checkout");
  const statusEl = document.getElementById("status-checkout");
  formEl.hidden = true;
  totalEl.textContent = "";
  statusEl.textContent = "";
  const pesanWA = encodeURIComponent(
    "Halo Admin Kopi Nusantara, saya baru saja checkout.\n" +
      "Kode pesanan: " +
      hasil.kode_transaksi +
      "\n" +
      "Total: " +
      rupiah(hasil.total),
  );
  isiEl.innerHTML = ` 
    <div class="status" role="status"> 
      <p><strong>Pesanan berhasil dibuat!</strong></p> 
      <p>Kode pesanan Anda: <strong>${esc(hasil.kode_transaksi)}</strong></p> 
      <p>Total: <strong>${rupiah(hasil.total)}</strong></p> 
      <p> 
        <a class="btn" href="api/struk.php?kode=${esc(hasil.kode_transaksi)}" target="_blank">Lihat 
Struk (PDF)</a> 
        <a class="btn" href="api/struk.php?kode=${esc(hasil.kode_transaksi)}&unduh=1">Unduh Struk 
(PDF)</a> 
        <a class="btn" href="https://wa.me/${NOMOR_ADMIN_WA}?text=${pesanWA}" target="_blank">Kirim 
Konfirmasi via WhatsApp</a> 
      </p> 
    </div> 
  `;
}
