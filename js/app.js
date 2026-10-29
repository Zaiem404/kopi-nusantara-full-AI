// app.js: logika tampilan. Data diambil dari data/produk.json

// ---------- Elemen yang dipakai ----------
let menuEl = document.getElementById("menu");
let filterEl = document.getElementById("filter-produk");
let jumlahEl = document.getElementById("jumlah-produk");
let daftarEl = document.getElementById("daftar-produk");
let formKontak = document.getElementById("form-kontak");
let statusEl = document.getElementById("form-status");

// ---------- State ----------
let filterKategori = "semua";
let SITE, MENU, PRODUCTS;

// ---------- Fungsi bantu ----------
function rupiah(angka) {
  return "Rp " + angka.toLocaleString("id-ID");
}

// Mengubah karakter khusus menjadi aman (mencegah XSS)
function esc(teks) {
  return String(teks).replace(/[&<>"']/g, function (c) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[c];
  });
}

//------------Fungsi JSON ------------

async function muatData() {
  const respons = await fetch("api/produk.php");

  if (!respons.ok) {
    throw new Error("Gagal memuat data (HTTP " + respons.status + ")");
  }

  return await respons.json();
}

async function mulai() {
  const app = document.querySelector(".site-content");
  const isiAsli = app.innerHTML; // simpan kerangka asli sebelum diganti pesan loading;

  app.innerHTML = '<p class="status" role="status">Memuat data...</p>';

  try {
    const data = await muatData();
    SITE = data.site;
    window.siteConfig = SITE;
    MENU = data.menu;
    PRODUCTS = data.produk;

    app.innerHTML = isiAsli; // kembalikan kerangka asli (section beranda, produk, kontak)

    // Ambil ulang elemen setelah kerangka dikembalikan
    inisialisasiElemen();
    document.getElementById("tahun").textContent = new Date().getFullYear();
    renderSite();
    renderMenu();
    renderFilter();
    renderProduk();
    renderKeranjang();
    pasangEventFilter();
    pasangAksiKeranjang();
    perbaruiBadgeKeranjang();
    pasangTombolKeranjang();
    inisialisasiKeranjang();
    pasangFormCheckout();

    if (location.hash === "#keranjang") {
      requestAnimationFrame(() => {
        document.getElementById("keranjang")?.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      });
    }
  } catch (err) {
    app.innerHTML =
      '<div class="status error" role="alert"><strong>Data tidak dapat dimuat.</strong><p>' +
      esc(err.message) +
      "</p><p>Pastikan halaman dibuka lewat server lokal (http://), bukan langsung dari berkas.</p></div>";
    console.error(err);
  }
}

// ---------- Site ----------
function renderSite() {
  if (!SITE) return;
  const emailEl = document.getElementById("email-kontak");
  if (emailEl && SITE.email) emailEl.textContent = SITE.email;
  const judulEl = document.querySelector(".site-title");
  if (judulEl && SITE.nama) judulEl.textContent = SITE.nama;
  document.title = SITE.nama || document.title;
}

// ---------- Menu ----------
function renderMenu() {
  menuEl.innerHTML = MENU.map(function (m) {
    return `<li><a href="#${m.id}">${esc(m.label)}</a></li>`;
  }).join("");
}

// ---------- Produk ----------
// function kartuProduk(p) {
//   return `
//     <article class="card">
//       <img src="${esc(p.gambar)}" alt="${esc(p.nama)}" class="product-photo">
//       <h3>${esc(p.nama)}</h3>
//       <p class="meta">${esc(p.asal)} &middot; ${esc(p.kategori)}</p>
//       <p>${esc(p.deskripsi)}</p>
//       <p class="price">${rupiah(p.harga)}</p>
//     </article>
//   `;
// }

function kartuProduk(p) {
  return ` 
    <article class="card"> 
      <img src="${esc(p.gambar)}" alt="${esc(p.alt)}" class="product-photo"> 
      <h3>${esc(p.nama)}</h3> 
      <p class="meta">${esc(p.asal)} &middot; ${esc(p.kategori)}</p> 
      <p>${esc(p.deskripsi)}</p> 
      <p class="price">${rupiah(p.harga)}</p> 
      <button class="btn btn-keranjang" 
              data-id="${p.id}" data-slug="${esc(p.slug)}" 
              data-nama="${esc(p.nama)}" data-harga="${p.harga}"> 
        + Keranjang 
      </button> 
    </article> 
  `;
}

function renderProduk() {
  const daftar =
    filterKategori === "semua"
      ? PRODUCTS
      : PRODUCTS.filter(function (p) {
          return p.kategori === filterKategori;
        });

  jumlahEl.textContent = "Menampilkan " + daftar.length + " produk";
  daftarEl.innerHTML = daftar.map(kartuProduk).join("");
}

// tambah keranjang tombol
function pasangTombolKeranjang() {
  daftarEl.addEventListener("click", function (e) {
    const tombol = e.target.closest(".btn-keranjang");
    if (!tombol) return;

    tambahKeKeranjang({
      produk_id: Number(tombol.dataset.id),
      slug: tombol.dataset.slug,
      nama: tombol.dataset.nama,
      harga: Number(tombol.dataset.harga),
    });

    tombol.textContent = "\u2713 Ditambahkan";
    setTimeout(function () {
      tombol.textContent = "+ Keranjang";
    }, 800);
  });
}

function renderFilter() {
  const kategori = ["semua", "arabika", "robusta"];
  filterEl.innerHTML = kategori
    .map(function (k) {
      const aktif = k === filterKategori ? "active" : "";
      return `<button class="filter ${aktif}" data-kategori="${k}">${k}</button>`;
    })
    .join("");
}

// Event delegation: listener dipasang pada induk yang tidak berubah
/* filterEl.addEventListener("click", function (e) {
  const tombol = e.target.closest("[data-kategori]");
  if (!tombol) return;

  filterKategori = tombol.dataset.kategori;
  renderFilter();
  renderProduk();
}); */

function cariSlug() {
  // 1. Mengambil referensi elemen DOM dari antarmuka HTML
  const elemenInputSlug = document.getElementById("inputSlug");
  const divHasilCari = document.getElementById("hasilCari");

  // Pengaman sistem jika elemen HTML tidak ditemukan di dokumen aktif
  if (!elemenInputSlug || !divHasilCari) {
    console.error("Elemen DOM target tidak ditemukan pada halaman ini.");
    return;
  }

  // 2. Sanitasi Input: Menghapus spasi ekstra di awal/akhir dan mengubah ke huruf kecil semua.
  const inputSlug = elemenInputSlug.value.trim().toLowerCase();

  // 3. Validasi Kondisional: Mencegah eksekusi jika kolom input kosong
  if (inputSlug === "") {
    divHasilCari.textContent = "Silakan masukkan kata kunci!";
    return;
  }

  // Pengaman: data belum selesai dimuat
  if (!PRODUCTS) {
    divHasilCari.textContent = "Data masih dimuat, silakan coba sesaat lagi.";
    return;
  }

  // 4. Proses Pencarian Data menggunakan metode built-in Array.prototype.find()
  const hasilPencarianSlug = PRODUCTS.find(function (kopi) {
    return kopi.slug === inputSlug;
  });

  // 5. Render Output Dinamis ke antarmuka HTML berdasarkan ketersediaan data
  if (hasilPencarianSlug) {
      divHasilCari.replaceChildren();
      const detail = document.createElement("p");
      detail.textContent = `Produk ditemukan. Nama: ${hasilPencarianSlug.nama}. Asal: ${hasilPencarianSlug.asal}. Kategori: ${hasilPencarianSlug.kategori}. Harga: ${rupiah(hasilPencarianSlug.harga)}. Deskripsi: ${hasilPencarianSlug.deskripsi}`;
      divHasilCari.append(detail);
    console.info("Output HTML: ", inputSlug, "ditemukan");
  } else {
    divHasilCari.textContent = `Produk dengan slug "${inputSlug}" tidak ditemukan.`;
    console.warn("Output HTML: ", inputSlug, "tidak ditemukan");
  }
}

function pasangEventFilter() {
  filterEl.addEventListener("click", function (e) {
    const tombol = e.target.closest("[data-kategori]");
    if (!tombol) return;

    filterKategori = tombol.dataset.kategori;
    renderFilter();
    renderProduk();
  });
}

// ---------- Contoh fetch API dari luar ----------
// API publik: SampleAPIs Coffee (gratis, tanpa API key, CORS terbuka)
// Dokumen: https://sampleapis.com/api-list/coffee
// Bentuk data: [{ id, title, description, ingredients[], image }]

function inisialisasiElemen() {
  menuEl = document.getElementById("menu");
  filterEl = document.getElementById("filter-produk");
  jumlahEl = document.getElementById("jumlah-produk");
  daftarEl = document.getElementById("daftar-produk");
  formKontak = document.getElementById("form-kontak");
  statusEl = document.getElementById("form-status");
}

// ---------- Inisialisasi ----------
document.getElementById("tahun").textContent = new Date().getFullYear();
mulai();
