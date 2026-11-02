<?php

namespace Database\Seeders;

/**
 * Original general psychotest questions (verbal, numeric, logic) for the
 * "Psikotes" category of every LPK.
 * Run with: php artisan db:seed --class=PsikotesQuestionSeeder
 */
class PsikotesQuestionSeeder extends BankQuestionSeeder
{
    public const CATEGORY = 'Psikotes';

    /**
     * Each item: [question, correct answer, [four wrong answers]].
     *
     * @var list<array{0: string, 1: string, 2: list<string>}>
     */
    public const QUESTIONS = [
        // Sinonim (persamaan kata)
        ['Sinonim kata AKURAT adalah...', 'Tepat', ['Cepat', 'Kasar', 'Ragu', 'Luas']],
        ['Sinonim kata ABSTRAK adalah...', 'Maya', ['Nyata', 'Konkret', 'Jelas', 'Padat']],
        ['Sinonim kata ADAPTASI adalah...', 'Penyesuaian', ['Perubahan', 'Penolakan', 'Perpindahan', 'Pengurangan']],
        ['Sinonim kata KONTRAS adalah...', 'Berlawanan', ['Serupa', 'Selaras', 'Sejajar', 'Seimbang']],
        ['Sinonim kata FIKTIF adalah...', 'Khayalan', ['Nyata', 'Benar', 'Resmi', 'Sah']],
        ['Sinonim kata MUTAKHIR adalah...', 'Terbaru', ['Kuno', 'Biasa', 'Langka', 'Mahal']],
        ['Sinonim kata LAZIM adalah...', 'Biasa', ['Aneh', 'Jarang', 'Langka', 'Baru']],
        ['Sinonim kata NISBI adalah...', 'Relatif', ['Mutlak', 'Pasti', 'Tetap', 'Nyata']],
        ['Sinonim kata KENDALA adalah...', 'Hambatan', ['Kemudahan', 'Bantuan', 'Tujuan', 'Hasil']],
        ['Sinonim kata SEKULER adalah...', 'Duniawi', ['Agamis', 'Rohani', 'Suci', 'Kuno']],
        ['Sinonim kata FRIKSI adalah...', 'Perpecahan', ['Persatuan', 'Kesepakatan', 'Perdamaian', 'Kerja sama']],
        ['Sinonim kata ELASTIS adalah...', 'Lentur', ['Kaku', 'Keras', 'Rapuh', 'Padat']],
        ['Sinonim kata PROGRESIF adalah...', 'Maju', ['Mundur', 'Tetap', 'Lambat', 'Kolot']],
        ['Sinonim kata INSOMNIA adalah...', 'Tidak bisa tidur', ['Mengantuk', 'Mimpi buruk', 'Pingsan', 'Kelelahan']],
        ['Sinonim kata KAMUFLASE adalah...', 'Penyamaran', ['Pengungkapan', 'Pertunjukan', 'Perlindungan', 'Pengintaian']],
        ['Sinonim kata OTONOMI adalah...', 'Pemerintahan sendiri', ['Ketergantungan', 'Penjajahan', 'Kerja sama', 'Kekuasaan pusat']],
        ['Sinonim kata RESESI adalah...', 'Kemunduran ekonomi', ['Kemajuan ekonomi', 'Kenaikan gaji', 'Perdagangan bebas', 'Pajak tinggi']],
        ['Sinonim kata VAKUM adalah...', 'Kosong', ['Penuh', 'Sibuk', 'Ramai', 'Padat']],
        ['Sinonim kata SPORADIS adalah...', 'Kadang-kadang', ['Terus-menerus', 'Serentak', 'Teratur', 'Selalu']],
        ['Sinonim kata DEDUKSI adalah...', 'Penyimpulan dari umum ke khusus', ['Penyimpulan dari khusus ke umum', 'Penambahan', 'Pengurangan', 'Pengamatan']],

        // Antonim (lawan kata)
        ['Antonim kata OPTIMIS adalah...', 'Pesimis', ['Yakin', 'Semangat', 'Percaya diri', 'Tegas']],
        ['Antonim kata STATIS adalah...', 'Dinamis', ['Tetap', 'Diam', 'Stabil', 'Pasif']],
        ['Antonim kata EKSPOR adalah...', 'Impor', ['Kirim', 'Jual', 'Dagang', 'Produksi']],
        ['Antonim kata APATIS adalah...', 'Peduli', ['Acuh', 'Malas', 'Diam', 'Pasif']],
        ['Antonim kata SKEPTIS adalah...', 'Yakin', ['Ragu', 'Curiga', 'Bimbang', 'Cemas']],
        ['Antonim kata AMATIR adalah...', 'Profesional', ['Pemula', 'Awam', 'Pelajar', 'Magang']],
        ['Antonim kata MAYOR adalah...', 'Minor', ['Utama', 'Besar', 'Pokok', 'Penting']],
        ['Antonim kata KONKRET adalah...', 'Abstrak', ['Nyata', 'Jelas', 'Pasti', 'Padat']],
        ['Antonim kata SURPLUS adalah...', 'Defisit', ['Lebih', 'Untung', 'Laba', 'Sisa']],
        ['Antonim kata HETEROGEN adalah...', 'Homogen', ['Beragam', 'Campuran', 'Majemuk', 'Berbeda']],
        ['Antonim kata KONSTAN adalah...', 'Berubah-ubah', ['Tetap', 'Stabil', 'Seimbang', 'Teratur']],
        ['Antonim kata EKSTERNAL adalah...', 'Internal', ['Luar', 'Asing', 'Umum', 'Global']],
        ['Antonim kata VERTIKAL adalah...', 'Horizontal', ['Tegak', 'Lurus', 'Miring', 'Tinggi']],
        ['Antonim kata MAKSIMUM adalah...', 'Minimum', ['Tertinggi', 'Optimal', 'Terbanyak', 'Puncak']],
        ['Antonim kata OBJEKTIF adalah...', 'Subjektif', ['Netral', 'Adil', 'Jujur', 'Faktual']],
        ['Antonim kata SEMENTARA adalah...', 'Permanen', ['Singkat', 'Sesaat', 'Darurat', 'Fana']],
        ['Antonim kata PROTAGONIS adalah...', 'Antagonis', ['Tokoh utama', 'Pahlawan', 'Pemeran', 'Figuran']],
        ['Antonim kata SINTESIS adalah...', 'Analisis', ['Gabungan', 'Paduan', 'Kesimpulan', 'Rangkuman']],
        ['Antonim kata FANA adalah...', 'Abadi', ['Sementara', 'Rapuh', 'Lemah', 'Hilang']],
        ['Antonim kata GRATIS adalah...', 'Berbayar', ['Murah', 'Cuma-cuma', 'Hadiah', 'Bonus']],

        // Analogi (padanan hubungan)
        ['DOKTER : RUMAH SAKIT = GURU : ...', 'Sekolah', ['Murid', 'Buku', 'Kapur', 'Pelajaran']],
        ['PENA : MENULIS = PISAU : ...', 'Memotong', ['Dapur', 'Tajam', 'Besi', 'Masak']],
        ['AIR : HAUS = MAKANAN : ...', 'Lapar', ['Kenyang', 'Piring', 'Nasi', 'Dapur']],
        ['PADI : PETANI = IKAN : ...', 'Nelayan', ['Laut', 'Jala', 'Perahu', 'Pasar']],
        ['MATA : MELIHAT = TELINGA : ...', 'Mendengar', ['Suara', 'Musik', 'Kepala', 'Berbicara']],
        ['BURUNG : SAYAP = IKAN : ...', 'Sirip', ['Air', 'Sisik', 'Insang', 'Kolam']],
        ['MOBIL : BENSIN = MANUSIA : ...', 'Makanan', ['Rumah', 'Pakaian', 'Kendaraan', 'Uang']],
        ['PANAS : API = DINGIN : ...', 'Es', ['Hujan', 'Angin', 'Malam', 'Air']],
        ['KAKI : SEPATU = KEPALA : ...', 'Topi', ['Rambut', 'Mata', 'Bantal', 'Sisir']],
        ['SUHU : TERMOMETER = BERAT : ...', 'Timbangan', ['Meteran', 'Jam', 'Kilogram', 'Penggaris']],
        ['PILOT : PESAWAT = MASINIS : ...', 'Kereta api', ['Kapal', 'Bus', 'Stasiun', 'Rel']],
        ['SEMEN : TEMBOK = BENANG : ...', 'Kain', ['Jarum', 'Gunting', 'Penjahit', 'Mesin jahit']],
        ['BUKU : PERPUSTAKAAN = UANG : ...', 'Bank', ['Dompet', 'Gaji', 'Pasar', 'Kasir']],
        ['SINGA : KARNIVORA = SAPI : ...', 'Herbivora', ['Omnivora', 'Mamalia', 'Peternak', 'Rumput']],
        ['DETIK : MENIT = MENIT : ...', 'Jam', ['Hari', 'Waktu', 'Jarum', 'Minggu']],

        // Deret angka & huruf
        ['Lanjutkan deret: 2, 4, 6, 8, ...', '10', ['9', '11', '12', '16']],
        ['Lanjutkan deret: 3, 6, 12, 24, ...', '48', ['36', '30', '42', '27']],
        ['Lanjutkan deret: 1, 4, 9, 16, 25, ...', '36', ['30', '34', '49', '32']],
        ['Lanjutkan deret: 5, 10, 8, 16, 14, ...', '28', ['12', '18', '24', '32']],
        ['Lanjutkan deret: 1, 1, 2, 3, 5, 8, ...', '13', ['11', '12', '10', '16']],
        ['Lanjutkan deret: 100, 95, 85, 70, ...', '50', ['55', '60', '45', '65']],
        ['Lanjutkan deret: 2, 3, 5, 7, 11, ...', '13', ['12', '15', '14', '17']],
        ['Lanjutkan deret: 1, 8, 27, 64, ...', '125', ['100', '81', '128', '216']],
        ['Lanjutkan deret: 81, 27, 9, 3, ...', '1', ['0', '2', '1/2', '3']],
        ['Lanjutkan deret: 4, 7, 12, 19, 28, ...', '39', ['37', '36', '40', '35']],
        ['Lanjutkan deret: 2, 6, 18, 54, ...', '162', ['108', '72', '154', '216']],
        ['Lanjutkan deret: 10, 13, 11, 14, 12, ...', '15', ['10', '13', '16', '9']],
        ['Lanjutkan deret: 1, 2, 6, 24, 120, ...', '720', ['240', '600', '144', '480']],
        ['Lanjutkan deret: 7, 14, 21, 28, ...', '35', ['32', '36', '42', '34']],
        ['Lanjutkan deret: 3, 5, 9, 17, 33, ...', '65', ['49', '66', '64', '57']],
        ['Lanjutkan deret: 64, 32, 16, 8, ...', '4', ['2', '6', '0', '1']],
        ['Lanjutkan deret: 1, 3, 7, 15, 31, ...', '63', ['47', '62', '64', '61']],
        ['Lanjutkan deret: 2, 5, 11, 23, ...', '47', ['46', '35', '45', '48']],
        ['Lanjutkan deret: 20, 18, 15, 11, 6, ...', '0', ['1', '2', '-1', '3']],
        ['Lanjutkan deret: 1, 2, 4, 7, 11, 16, ...', '22', ['21', '20', '23', '32']],
        ['Lanjutkan deret: 5, 7, 10, 14, 19, ...', '25', ['24', '23', '26', '28']],
        ['Lanjutkan deret: 0, 3, 8, 15, 24, ...', '35', ['33', '34', '36', '31']],
        ['Lanjutkan deret huruf: A, C, E, G, ...', 'I', ['H', 'J', 'K', 'F']],
        ['Lanjutkan deret huruf: B, E, H, K, ...', 'N', ['M', 'L', 'O', 'P']],
        ['Lanjutkan deret huruf: Z, X, V, T, ...', 'R', ['S', 'Q', 'U', 'P']],

        // Hitungan dasar
        ['25% dari 240 adalah...', '60', ['48', '50', '72', '64']],
        ['Hasil dari 15 x 12 adalah...', '180', ['170', '160', '190', '150']],
        ['Hasil dari 1/4 + 1/2 adalah...', '3/4', ['2/6', '1/6', '2/4', '1']],
        ['Harga 5 pensil Rp7.500. Berapa harga 8 pensil?', 'Rp12.000', ['Rp11.000', 'Rp12.500', 'Rp10.500', 'Rp13.500']],
        ['Sebuah mobil melaju 60 km/jam selama 3,5 jam. Jarak yang ditempuh adalah...', '210 km', ['180 km', '200 km', '215 km', '240 km']],
        ['Hasil dari 0,75 x 80 adalah...', '60', ['56', '64', '75', '45']],
        ['Akar kuadrat dari 144 adalah...', '12', ['14', '11', '16', '72']],
        ['Rata-rata dari 6, 8, 10, dan 12 adalah...', '9', ['8', '10', '9,5', '8,5']],
        ['Harga baju Rp150.000 mendapat diskon 20%. Harga yang harus dibayar adalah...', 'Rp120.000', ['Rp130.000', 'Rp125.000', 'Rp110.000', 'Rp30.000']],
        ['Pekerjaan diselesaikan 3 orang dalam 12 hari. Jika dikerjakan 6 orang dengan kecepatan sama, selesai dalam...', '6 hari', ['24 hari', '4 hari', '8 hari', '9 hari']],
        ['Hasil dari 2³ + 3² adalah...', '17', ['13', '15', '12', '36']],
        ['Pukul 08.45 ditambah 2 jam 30 menit menjadi pukul...', '11.15', ['10.75', '11.05', '11.25', '10.15']],
        ['Keliling persegi dengan sisi 7 cm adalah...', '28 cm', ['14 cm', '21 cm', '49 cm', '35 cm']],
        ['Luas persegi panjang dengan panjang 8 cm dan lebar 5 cm adalah...', '40 cm²', ['26 cm²', '13 cm²', '45 cm²', '35 cm²']],
        ['Hasil dari 1.000 - 375 adalah...', '625', ['735', '635', '725', '675']],
        ['Uang Rp50.000 dibagi untuk A dan B dengan perbandingan 2 : 3. Bagian A adalah...', 'Rp20.000', ['Rp30.000', 'Rp25.000', 'Rp15.000', 'Rp10.000']],
        ['Jika x + 7 = 15, maka nilai x adalah...', '8', ['7', '22', '9', '6']],
        ['Hasil dari 40% x 50 + 25% x 80 adalah...', '40', ['35', '45', '30', '50']],
        ['Andi membawa Rp50.000 dan membeli 3 buku seharga Rp12.500 per buku. Sisa uangnya adalah...', 'Rp12.500', ['Rp15.000', 'Rp10.000', 'Rp37.500', 'Rp17.500']],
        ['Hasil dari 144 : 12 x 3 adalah...', '36', ['4', '48', '24', '12']],

        // Logika
        ['Semua karyawan memakai seragam. Budi adalah karyawan. Kesimpulannya...', 'Budi memakai seragam', ['Budi tidak memakai seragam', 'Semua yang memakai seragam adalah Budi', 'Budi mungkin memakai seragam', 'Tidak dapat disimpulkan']],
        ['Jika hujan, jalan menjadi basah. Hari ini jalan tidak basah. Kesimpulannya...', 'Hari ini tidak hujan', ['Hari ini hujan', 'Jalan sedang diperbaiki', 'Besok akan hujan', 'Tidak dapat disimpulkan']],
        ['Semua mamalia bernapas dengan paru-paru. Paus adalah mamalia. Kesimpulannya...', 'Paus bernapas dengan paru-paru', ['Paus bernapas dengan insang', 'Paus bukan mamalia', 'Sebagian paus bernapas dengan paru-paru', 'Tidak dapat disimpulkan']],
        ['Sebagian pegawai kantor A bisa berbahasa Inggris. Rina adalah pegawai kantor A. Kesimpulannya...', 'Rina mungkin bisa berbahasa Inggris', ['Rina pasti bisa berbahasa Inggris', 'Rina pasti tidak bisa berbahasa Inggris', 'Semua pegawai bisa berbahasa Inggris', 'Rina bukan pegawai kantor A']],
        ['Andi lebih tinggi dari Budi. Budi lebih tinggi dari Citra. Siapa yang paling pendek?', 'Citra', ['Andi', 'Budi', 'Andi dan Citra sama', 'Tidak dapat ditentukan']],
        ['Dina lebih tua dari Eka. Fani lebih muda dari Eka. Siapa yang paling tua?', 'Dina', ['Eka', 'Fani', 'Eka dan Fani sama', 'Tidak dapat ditentukan']],
        ['Jika lulus tes, Sari diterima bekerja. Sari tidak diterima bekerja. Kesimpulannya...', 'Sari tidak lulus tes', ['Sari lulus tes', 'Sari tidak mengikuti tes', 'Sari akan diterima tahun depan', 'Tidak dapat disimpulkan']],
        ['Semua peserta yang lulus mendapat sertifikat. Joko tidak mendapat sertifikat. Kesimpulannya...', 'Joko tidak lulus', ['Joko lulus', 'Joko kehilangan sertifikat', 'Sertifikat Joko belum dicetak', 'Tidak dapat disimpulkan']],
        ['Tidak ada ikan yang hidup di darat. Lele adalah ikan. Kesimpulannya...', 'Lele tidak hidup di darat', ['Lele hidup di darat', 'Sebagian lele hidup di darat', 'Lele bukan ikan', 'Tidak dapat disimpulkan']],
        ['Rumah Ani di sebelah kiri rumah Beni. Rumah Cici di sebelah kanan rumah Beni. Rumah siapa yang berada di tengah?', 'Beni', ['Ani', 'Cici', 'Ani dan Cici', 'Tidak dapat ditentukan']],
        ['Semua yang rajin belajar akan pintar. Tono tidak pintar. Kesimpulannya...', 'Tono tidak rajin belajar', ['Tono rajin belajar', 'Tono akan pintar', 'Semua orang tidak pintar', 'Tidak dapat disimpulkan']],
        ['Jika toko buka, lampu toko menyala. Lampu toko menyala. Kesimpulannya...', 'Tidak dapat dipastikan toko buka', ['Toko pasti buka', 'Toko pasti tutup', 'Lampu rusak', 'Toko buka 24 jam']],
        ['Ayah lahir tahun 1975. Ibu 3 tahun lebih muda dari Ayah. Kakak lahir saat Ibu berusia 24 tahun. Kakak lahir tahun...', '2002', ['1999', '2001', '2005', '1996']],
        ['Hari ini hari Rabu. Sepuluh hari lagi adalah hari...', 'Sabtu', ['Jumat', 'Minggu', 'Kamis', 'Senin']],
        ['Lima orang mengantre. Ali di depan Budi, Budi di depan Dodi, Cika di belakang Dodi, dan Eko paling belakang. Siapa yang berada di urutan ketiga dari depan?', 'Dodi', ['Budi', 'Cika', 'Ali', 'Eko']],
        ['Semua A adalah B. Semua B adalah C. Kesimpulannya...', 'Semua A adalah C', ['Semua C adalah A', 'Sebagian A bukan C', 'Tidak ada A yang C', 'Tidak dapat disimpulkan']],
        ['Sebagian buah rasanya manis. Semua yang manis disukai anak-anak. Kesimpulannya...', 'Sebagian buah disukai anak-anak', ['Semua buah disukai anak-anak', 'Tidak ada buah yang disukai anak-anak', 'Semua yang disukai anak-anak adalah buah', 'Tidak dapat disimpulkan']],
        ['Jika 3 mesin membuat 3 barang dalam 3 menit, berapa lama 100 mesin membuat 100 barang?', '3 menit', ['100 menit', '33 menit', '300 menit', '1 menit']],
        ['Toni berangkat pukul 07.00 dan tiba 2 jam 15 menit kemudian. Rapat dimulai pukul 09.30. Toni tiba...', '15 menit lebih awal', ['Tepat waktu', '15 menit terlambat', '30 menit lebih awal', '45 menit terlambat']],
        ['Dalam kotak ada 4 bola merah dan 6 bola biru. Berapa bola minimal yang harus diambil tanpa melihat agar pasti mendapat 2 bola berwarna sama?', '3', ['2', '5', '7', '4']],

        // Kata/angka yang tidak sesuai kelompok
        ['Manakah yang tidak termasuk kelompoknya: Apel, Mangga, Jeruk, Wortel, Pisang?', 'Wortel', ['Apel', 'Mangga', 'Jeruk', 'Pisang']],
        ['Manakah yang tidak termasuk kelompoknya: Merah, Biru, Kuning, Meja, Hijau?', 'Meja', ['Merah', 'Biru', 'Kuning', 'Hijau']],
        ['Manakah yang tidak termasuk kelompoknya: Palu, Obeng, Gergaji, Tang, Piring?', 'Piring', ['Palu', 'Obeng', 'Gergaji', 'Tang']],
        ['Manakah yang tidak termasuk kelompoknya: Sapi, Kambing, Kerbau, Elang, Kuda?', 'Elang', ['Sapi', 'Kambing', 'Kerbau', 'Kuda']],
        ['Manakah yang tidak termasuk kelompoknya: Senin, Selasa, Maret, Kamis, Jumat?', 'Maret', ['Senin', 'Selasa', 'Kamis', 'Jumat']],
        ['Manakah yang tidak termasuk kelompoknya: 2, 4, 6, 9, 10?', '9', ['2', '4', '6', '10']],
        ['Manakah yang tidak termasuk kelompoknya: Gitar, Biola, Piano, Kuas, Drum?', 'Kuas', ['Gitar', 'Biola', 'Piano', 'Drum']],
        ['Manakah yang tidak termasuk kelompoknya: Jakarta, Bandung, Surabaya, Malaysia, Medan?', 'Malaysia', ['Jakarta', 'Bandung', 'Surabaya', 'Medan']],
        ['Manakah yang tidak termasuk kelompoknya: Segitiga, Persegi, Lingkaran, Kubus, Trapesium?', 'Kubus', ['Segitiga', 'Persegi', 'Lingkaran', 'Trapesium']],
        ['Manakah yang tidak termasuk kelompoknya: Dokter, Perawat, Bidan, Apoteker, Montir?', 'Montir', ['Dokter', 'Perawat', 'Bidan', 'Apoteker']],
    ];
}
