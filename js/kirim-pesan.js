function dataPesanKontak() {
  const nama = formKontak.nama.value.trim();
  const pesan = formKontak.pesan.value.trim();
  if (nama === "" || pesan === "") {
    statusEl.textContent = "Nama dan pesan wajib diisi.";
    return null;
  }
  return { nama, pesan };
}

function kirimPesanEmail(event) {
  event.preventDefault();
  const data = dataPesanKontak();
  if (!data) return;
  const tujuan = window.siteConfig?.email;
  if (!tujuan || !tujuan.includes("@") || tujuan.endsWith(".test")) {
    statusEl.textContent = "Alamat email resmi belum dikonfigurasi. Silakan hubungi kami lewat WhatsApp.";
    return;
  }
  const subject = encodeURIComponent("Pesan kontak dari " + data.nama);
  const body = encodeURIComponent(data.pesan);
  window.location.href = `mailto:${encodeURIComponent(tujuan)}?subject=${subject}&body=${body}`;
  statusEl.textContent = "Aplikasi email dibuka. Pesan belum terkirim sampai Anda menekan Kirim di aplikasi email.";
}

function kirimPesanWA(wa) {
  if (wa) wa.preventDefault();
  const data = dataPesanKontak();
  if (!data) return;

  const nomor = "6281228427862";
  const urlWhatsApp =
    "https://wa.me/" +
    nomor +
    "?text=" +
    encodeURIComponent(
      "Pesan dari " + data.nama + "\n\n" + "Isi Pesan: " + data.pesan,
    );
  window.open(urlWhatsApp, "_blank", "noopener,noreferrer");
  statusEl.textContent = "WhatsApp dibuka. Pesan belum terkirim sampai Anda menekan Kirim di WhatsApp.";
}
