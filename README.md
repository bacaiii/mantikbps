# Manajemen Publikasi Statistik (BPS)

Sistem Manajemen Publikasi Statistik adalah platform berbasis web (Laravel) yang dikembangkan untuk mengelola, memantau, dan mendigitalkan seluruh alur kerja penyusunan publikasi statistik di lingkungan Badan Pusat Statistik (BPS). Sistem ini memfasilitasi kolaborasi antara berbagai peran mulai dari penyusun, *reviewer*, pengawas (tenant/admin), hingga pimpinan untuk persetujuan akhir.

## Fitur Utama Sistem

Sistem ini membagi fungsionalitasnya berdasarkan hak akses (Role-Based Access Control) untuk memastikan alur kerja publikasi berjalan terstruktur.

### 1. Sistem Multi-Peran (Multi-Role)
- **Admin Sistem**: Bertugas mengelola master data sistem secara keseluruhan.
- **Admin Provinsi / KabKota (Tenant)**: Bertugas sebagai manajer publikasi di wilayahnya. Mengelola akun pengguna (pegawai), membuat template tim kerja, mengalokasikan tim untuk setiap publikasi, dan memonitor seluruh proses.
- **Pegawai (Anggota Tim)**: Berperan sebagai Penyusun atau Reviewer. Mengerjakan tugas sesuai alokasi, mengunggah draft, melakukan *review* dokumen, dan menyiapkan paket publikasi akhir.
- **Pimpinan**: Bertugas melakukan *approval* (persetujuan akhir) atas publikasi yang telah selesai melalui proses *review*, serta memantau publikasi yang sudah siap rilis.

### 2. Dashboard Monitoring & Statistik
Menampilkan metrik dan visualisasi grafik yang profesional (berbasis *Chart.js* dengan antarmuka UI/UX modern) untuk memantau status publikasi:
- Total publikasi secara keseluruhan.
- Publikasi berstatus **ARC (Advanced Release Calendar)** Proses & Selesai.
- Publikasi berstatus **Non-ARC** Proses & Selesai.

### 3. Manajemen Alur Kerja & Tim Publikasi
- **Alokasi Tim**: Admin dapat membuat dan menentukan tim kerja secara spesifik untuk suatu judul publikasi.
- **Tahapan Publikasi**: Memonitor pergerakan status publikasi dari tahap Penyusunan, Review (Konten, Layout, Infografis), hingga tahap Siap Rilis.

### 4. Sistem Review Dokumen (Document Review)
- Fitur kolaboratif yang memungkinkan *Reviewer* (Pegawai) memberikan catatan perbaikan (*notes*) terhadap dokumen yang diunggah oleh *Penyusun*.
- Tersedia fitur status perbaikan pada setiap catatan untuk melacak apakah masukan perbaikan sudah diselesaikan atau belum.
- Fitur *preview* dan *download* dokumen secara langsung melalui platform.

### 5. Knowledge Base & Pedoman Pemeriksaan
- Modul untuk mengelola pedoman standar pemeriksaan (Pedoman Konten, Layout, dsb).
- Berfungsi sebagai pustaka (*Knowledge Base*) pusat yang dapat diakses oleh seluruh pegawai kapan saja untuk menjaga standar kualitas publikasi BPS.

### 6. Rekapitulasi & Pelaporan
- Laporan (Report) dan *log history* aktivitas untuk transparansi.
- Fitur *export* rekap publikasi per bulan dalam format Excel / PDF.
- Rangkuman laporan kinerja untuk Publikasi "Siap Rilis" yang terintegrasi untuk dilaporkan ke atasan.

---

## Panduan Penggunaan Singkat

1. **Inisialisasi Proyek**: Admin BPS akan membuat *Record* Publikasi baru dan mengalokasikan Tim Kerja (memilih pegawai mana yang menjadi penyusun, editor, dsb).
2. **Penyusunan & Upload Draft**: Pegawai yang bertugas sebagai *Penyusun* masuk ke menu **Tugas Saya**, mengunggah *draft* dokumen awal, dan melengkapi SPRP.
3. **Proses Review**: Pegawai dengan peran *Reviewer* akan masuk ke modul **Review Dokumen**. Mereka membaca *draft* dan meninggalkan catatan perbaikan.
4. **Revisi**: *Penyusun* memperbaiki *draft* sesuai catatan dan mengunggah ulang dokumen revisinya, lalu memperbarui status catatan menjadi 'Selesai'.
5. **Approval Pimpinan**: Setelah *Reviewer* menyetujui hasil akhir, dokumen diteruskan ke menu **Persetujuan (Pimpinan)**. Pimpinan dapat meninjau (*preview*) dokumen dan menekan tombol *Setuju*.
6. **Rilis**: Dokumen masuk ke daftar **Publikasi Siap Rilis**, yang kemudian paket data akhirnya (beserta *cover* dan infografis) diunduh untuk diunggah ke website resmi BPS.
