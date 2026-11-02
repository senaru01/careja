<?php

namespace Database\Seeders;

/**
 * Original general knowledge questions for the "Pengetahuan Umum" category
 * of every LPK. Only stable facts are used (no office holders or statistics
 * that change over time).
 * Run with: php artisan db:seed --class=PengetahuanUmumQuestionSeeder
 */
class PengetahuanUmumQuestionSeeder extends BankQuestionSeeder
{
    public const CATEGORY = 'Pengetahuan Umum';

    /**
     * Each item: [question, correct answer, [four wrong answers]].
     *
     * @var list<array{0: string, 1: string, 2: list<string>}>
     */
    public const QUESTIONS = [
        // Pancasila & kewarganegaraan
        ['Dasar negara Republik Indonesia adalah...', 'Pancasila', ['UUD 1945', 'Bhinneka Tunggal Ika', 'GBHN', 'Ketetapan MPR']],
        ['Bunyi sila ketiga Pancasila adalah...', 'Persatuan Indonesia', ['Ketuhanan Yang Maha Esa', 'Kemanusiaan yang adil dan beradab', 'Kerakyatan yang dipimpin oleh hikmat kebijaksanaan dalam permusyawaratan/perwakilan', 'Keadilan sosial bagi seluruh rakyat Indonesia']],
        ['Lambang sila pertama Pancasila adalah...', 'Bintang', ['Rantai', 'Pohon beringin', 'Kepala banteng', 'Padi dan kapas']],
        ['Lambang sila kedua Pancasila adalah...', 'Rantai', ['Bintang', 'Pohon beringin', 'Kepala banteng', 'Padi dan kapas']],
        ['Lambang sila keempat Pancasila adalah...', 'Kepala banteng', ['Bintang', 'Rantai', 'Pohon beringin', 'Padi dan kapas']],
        ['Semboyan Bhinneka Tunggal Ika memiliki arti...', 'Berbeda-beda tetapi tetap satu', ['Bersatu kita teguh', 'Satu nusa satu bangsa', 'Gotong royong', 'Merdeka atau mati']],
        ['Semboyan Bhinneka Tunggal Ika diambil dari kitab...', 'Sutasoma', ['Negarakertagama', 'Pararaton', 'Arjunawiwaha', 'Bharatayudha']],
        ['Proklamasi kemerdekaan Indonesia dibacakan pada tanggal...', '17 Agustus 1945', ['18 Agustus 1945', '17 Agustus 1944', '1 Juni 1945', '28 Oktober 1928']],
        ['Teks proklamasi kemerdekaan Indonesia dibacakan oleh...', 'Ir. Soekarno', ['Mohammad Hatta', 'Sutan Sjahrir', 'Ahmad Soebardjo', 'Sayuti Melik']],
        ['Naskah proklamasi kemerdekaan diketik oleh...', 'Sayuti Melik', ['Ahmad Soebardjo', 'Soekarni', 'Fatmawati', 'Wikana']],
        ['Bendera pusaka Merah Putih pertama kali dijahit oleh...', 'Fatmawati', ['R.A. Kartini', 'Cut Nyak Dhien', 'Dewi Sartika', 'Martha Christina Tiahahu']],
        ['Hari Lahir Pancasila diperingati setiap tanggal...', '1 Juni', ['17 Agustus', '1 Oktober', '28 Oktober', '20 Mei']],
        ['Hari Kesaktian Pancasila diperingati setiap tanggal...', '1 Oktober', ['1 Juni', '30 September', '10 November', '17 Agustus']],
        ['Sumpah Pemuda diikrarkan pada tanggal...', '28 Oktober 1928', ['20 Mei 1908', '17 Agustus 1945', '28 Oktober 1945', '1 Juni 1945']],
        ['Hari Kebangkitan Nasional diperingati setiap tanggal...', '20 Mei', ['2 Mei', '10 November', '21 April', '1 Juni']],
        ['Hari Pahlawan diperingati setiap tanggal...', '10 November', ['17 Agustus', '20 Mei', '1 Oktober', '22 Desember']],
        ['Hari Pendidikan Nasional diperingati setiap tanggal...', '2 Mei', ['20 Mei', '25 November', '21 April', '1 Mei']],
        ['Pencipta lagu kebangsaan Indonesia Raya adalah...', 'W.R. Supratman', ['Ismail Marzuki', 'C. Simanjuntak', 'Kusbini', 'H. Mutahar']],
        ['Lembaga negara yang berwenang mengubah dan menetapkan Undang-Undang Dasar adalah...', 'MPR', ['DPR', 'DPD', 'Mahkamah Agung', 'Presiden']],
        ['Lembaga negara yang berwenang menguji undang-undang terhadap UUD 1945 adalah...', 'Mahkamah Konstitusi', ['Mahkamah Agung', 'Komisi Yudisial', 'DPR', 'BPK']],
        ['Lembaga negara yang bertugas memeriksa pengelolaan keuangan negara adalah...', 'BPK', ['BPS', 'OJK', 'Bank Indonesia', 'KPU']],
        ['Masa jabatan Presiden Republik Indonesia dalam satu periode adalah...', '5 tahun', ['4 tahun', '6 tahun', '7 tahun', '10 tahun']],

        // Geografi Indonesia
        ['Ibu kota negara Indonesia yang dibangun di Kalimantan Timur diberi nama...', 'Nusantara', ['Palangka Raya', 'Balikpapan', 'Samarinda', 'Banjarbaru']],
        ['Puncak gunung tertinggi di Indonesia adalah...', 'Puncak Jaya', ['Gunung Semeru', 'Gunung Kerinci', 'Gunung Rinjani', 'Gunung Merapi']],
        ['Gunung tertinggi di Pulau Jawa adalah...', 'Gunung Semeru', ['Gunung Merapi', 'Gunung Slamet', 'Gunung Lawu', 'Gunung Bromo']],
        ['Danau terbesar di Indonesia adalah...', 'Danau Toba', ['Danau Poso', 'Danau Singkarak', 'Danau Sentani', 'Danau Maninjau']],
        ['Sungai terpanjang di Indonesia adalah...', 'Sungai Kapuas', ['Sungai Mahakam', 'Sungai Musi', 'Sungai Barito', 'Bengawan Solo']],
        ['Ibu kota Provinsi Jawa Tengah adalah...', 'Semarang', ['Surakarta', 'Magelang', 'Yogyakarta', 'Pekalongan']],
        ['Ibu kota Provinsi Sumatera Utara adalah...', 'Medan', ['Padang', 'Pekanbaru', 'Banda Aceh', 'Pematangsiantar']],
        ['Ibu kota Provinsi Sulawesi Selatan adalah...', 'Makassar', ['Manado', 'Kendari', 'Palu', 'Parepare']],
        ['Ibu kota Provinsi Kalimantan Timur adalah...', 'Samarinda', ['Balikpapan', 'Banjarmasin', 'Pontianak', 'Tarakan']],
        ['Ibu kota Provinsi Bali adalah...', 'Denpasar', ['Singaraja', 'Gianyar', 'Tabanan', 'Kuta']],
        ['Ibu kota Provinsi Nusa Tenggara Barat adalah...', 'Mataram', ['Kupang', 'Bima', 'Sumbawa Besar', 'Ende']],
        ['Ibu kota Provinsi Riau adalah...', 'Pekanbaru', ['Tanjung Pinang', 'Batam', 'Dumai', 'Jambi']],
        ['Selat yang memisahkan Pulau Jawa dan Pulau Sumatra adalah...', 'Selat Sunda', ['Selat Bali', 'Selat Malaka', 'Selat Makassar', 'Selat Lombok']],
        ['Selat yang memisahkan Pulau Jawa dan Pulau Bali adalah...', 'Selat Bali', ['Selat Sunda', 'Selat Lombok', 'Selat Madura', 'Selat Karimata']],
        ['Candi Borobudur terletak di provinsi...', 'Jawa Tengah', ['Jawa Timur', 'DI Yogyakarta', 'Jawa Barat', 'Bali']],
        ['Garis khayal yang membagi bumi menjadi belahan utara dan selatan disebut...', 'Khatulistiwa', ['Garis bujur', 'Garis balik utara', 'Meridian', 'Garis tanggal internasional']],
        ['Kota di Indonesia yang terkenal dengan Tugu Khatulistiwa adalah...', 'Pontianak', ['Padang', 'Palu', 'Manado', 'Samarinda']],
        ['Secara geografis, Indonesia terletak di antara dua benua, yaitu...', 'Asia dan Australia', ['Asia dan Afrika', 'Asia dan Eropa', 'Australia dan Afrika', 'Asia dan Amerika']],
        ['Indonesia diapit oleh dua samudra, yaitu...', 'Samudra Hindia dan Samudra Pasifik', ['Samudra Atlantik dan Samudra Pasifik', 'Samudra Hindia dan Samudra Atlantik', 'Samudra Arktik dan Samudra Pasifik', 'Samudra Hindia dan Samudra Arktik']],
        ['Jumlah zona waktu di Indonesia adalah...', '3', ['2', '4', '5', '1']],

        // Sains dasar
        ['Planet yang paling dekat dengan Matahari adalah...', 'Merkurius', ['Venus', 'Bumi', 'Mars', 'Jupiter']],
        ['Planet terbesar di tata surya adalah...', 'Jupiter', ['Saturnus', 'Uranus', 'Neptunus', 'Bumi']],
        ['Rumus kimia air adalah...', 'H₂O', ['CO₂', 'O₂', 'NaCl', 'H₂SO₄']],
        ['Gas yang dibutuhkan manusia untuk bernapas adalah...', 'Oksigen', ['Karbon dioksida', 'Nitrogen', 'Hidrogen', 'Helium']],
        ['Gas yang diserap tumbuhan untuk fotosintesis adalah...', 'Karbon dioksida', ['Oksigen', 'Nitrogen', 'Hidrogen', 'Amonia']],
        ['Proses tumbuhan hijau membuat makanan dengan bantuan cahaya matahari disebut...', 'Fotosintesis', ['Respirasi', 'Transpirasi', 'Evaporasi', 'Germinasi']],
        ['Satuan internasional (SI) untuk gaya adalah...', 'Newton', ['Joule', 'Watt', 'Pascal', 'Kilogram']],
        ['Satuan kuat arus listrik adalah...', 'Ampere', ['Volt', 'Ohm', 'Watt', 'Joule']],
        ['Satuan daya listrik adalah...', 'Watt', ['Volt', 'Ampere', 'Ohm', 'Coulomb']],
        ['Pada tekanan udara normal, air mendidih pada suhu...', '100 °C', ['90 °C', '80 °C', '120 °C', '0 °C']],
        ['Organ tubuh yang berfungsi memompa darah ke seluruh tubuh adalah...', 'Jantung', ['Paru-paru', 'Hati', 'Ginjal', 'Lambung']],
        ['Sel darah yang berfungsi melawan kuman penyakit adalah...', 'Sel darah putih', ['Sel darah merah', 'Keping darah', 'Plasma darah', 'Hemoglobin']],
        ['Vitamin yang dapat dibentuk kulit dengan bantuan sinar matahari adalah...', 'Vitamin D', ['Vitamin A', 'Vitamin B', 'Vitamin C', 'Vitamin K']],
        ['Penemu telepon adalah...', 'Alexander Graham Bell', ['Thomas Alva Edison', 'James Watt', 'Nikola Tesla', 'Guglielmo Marconi']],
        ['Hukum gravitasi dikemukakan oleh...', 'Isaac Newton', ['Albert Einstein', 'Galileo Galilei', 'Archimedes', 'Michael Faraday']],
        ['Perubahan wujud benda dari padat langsung menjadi gas disebut...', 'Menyublim', ['Mencair', 'Menguap', 'Membeku', 'Mengembun']],
        ['Perubahan wujud benda dari cair menjadi padat disebut...', 'Membeku', ['Mencair', 'Menguap', 'Menyublim', 'Mengembun']],
        ['Hewan yang memakan tumbuhan dan daging disebut...', 'Omnivora', ['Herbivora', 'Karnivora', 'Insektivora', 'Detritivora']],
        ['Lapisan atmosfer yang melindungi bumi dari sinar ultraviolet adalah lapisan...', 'Ozon', ['Troposfer', 'Ionosfer', 'Eksosfer', 'Termosfer']],
        ['Alat untuk mengukur suhu badan adalah...', 'Termometer', ['Barometer', 'Higrometer', 'Speedometer', 'Anemometer']],

        // Internasional & umum
        ['Mata uang negara Jepang adalah...', 'Yen', ['Won', 'Yuan', 'Ringgit', 'Baht']],
        ['Ibu kota negara Australia adalah...', 'Canberra', ['Sydney', 'Melbourne', 'Perth', 'Brisbane']],
        ['Ibu kota negara Malaysia adalah...', 'Kuala Lumpur', ['Johor Bahru', 'Penang', 'Malaka', 'Ipoh']],
        ['Organisasi kerja sama negara-negara di Asia Tenggara disebut...', 'ASEAN', ['APEC', 'NATO', 'OPEC', 'Uni Eropa']],
        ['ASEAN didirikan pada tahun...', '1967', ['1945', '1955', '1961', '1975']],
        ['Markas besar Perserikatan Bangsa-Bangsa (PBB) berada di kota...', 'New York', ['Jenewa', 'Paris', 'London', 'Washington D.C.']],
        ['Negara yang dijuluki Negeri Sakura adalah...', 'Jepang', ['Korea Selatan', 'Tiongkok', 'Thailand', 'Taiwan']],
        ['Negara yang dijuluki Negeri Gajah Putih adalah...', 'Thailand', ['India', 'Myanmar', 'Laos', 'Kamboja']],
        ['Benua terluas di dunia adalah...', 'Asia', ['Afrika', 'Amerika', 'Eropa', 'Australia']],
        ['Samudra terluas di dunia adalah...', 'Samudra Pasifik', ['Samudra Atlantik', 'Samudra Hindia', 'Samudra Arktik', 'Samudra Selatan']],
        ['Bahasa resmi negara Brasil adalah...', 'Portugis', ['Spanyol', 'Inggris', 'Prancis', 'Italia']],
        ['Menara Eiffel terletak di kota...', 'Paris', ['London', 'Roma', 'Berlin', 'Madrid']],
        ['Olimpiade musim panas diselenggarakan setiap...', '4 tahun sekali', ['2 tahun sekali', '3 tahun sekali', '5 tahun sekali', 'Setiap tahun']],
        ['Jumlah pemain satu tim sepak bola di lapangan adalah...', '11 orang', ['10 orang', '9 orang', '12 orang', '7 orang']],
        ['Satu lusin berisi...', '12 buah', ['10 buah', '20 buah', '24 buah', '144 buah']],
        ['Satu kodi berisi...', '20 buah', ['12 buah', '10 buah', '24 buah', '100 buah']],
        ['Satu rim kertas berisi...', '500 lembar', ['100 lembar', '250 lembar', '1.000 lembar', '144 lembar']],

        // K3 & dunia kerja
        ['K3 dalam dunia kerja merupakan singkatan dari...', 'Keselamatan dan Kesehatan Kerja', ['Keamanan dan Kenyamanan Kerja', 'Kesehatan, Kebersihan, dan Keamanan', 'Kedisiplinan dan Kerapian Kerja', 'Kerja Keras dan Kerja Cerdas']],
        ['APD dalam K3 merupakan singkatan dari...', 'Alat Pelindung Diri', ['Alat Pengaman Darurat', 'Alat Pemadam Darurat', 'Alat Pertolongan Dasar', 'Alat Pengukur Daya']],
        ['APAR merupakan singkatan dari...', 'Alat Pemadam Api Ringan', ['Alat Pelindung Api Ringan', 'Alat Pengaman Arus Rendah', 'Alat Penyelamat Area Rawan', 'Alat Pendeteksi Asap Ruangan']],
        ['APD yang wajib dipakai untuk melindungi kepala di area proyek konstruksi adalah...', 'Helm keselamatan', ['Kacamata pelindung', 'Sarung tangan', 'Sepatu keselamatan', 'Masker']],
        ['Rambu K3 berwarna merah dengan garis coret umumnya menandakan...', 'Larangan', ['Kewajiban', 'Peringatan', 'Kondisi aman', 'Informasi umum']],
        ['Rambu K3 berwarna biru umumnya menandakan...', 'Kewajiban yang harus dilakukan', ['Larangan', 'Peringatan bahaya', 'Jalur evakuasi', 'Lokasi P3K']],
        ['Rambu K3 berwarna kuning dengan bentuk segitiga umumnya menandakan...', 'Peringatan bahaya', ['Larangan', 'Kewajiban', 'Kondisi aman', 'Arah keluar']],
        ['Rambu K3 berwarna hijau umumnya menandakan...', 'Kondisi aman atau jalur evakuasi', ['Larangan', 'Kewajiban', 'Peringatan bahaya', 'Area terbatas']],
        ['Kebakaran akibat korsleting listrik sebaiknya TIDAK dipadamkan dengan...', 'Air', ['APAR CO₂', 'APAR serbuk kimia kering', 'Memutus aliran listrik terlebih dahulu', 'APAR jenis gas']],
        ['Tindakan pertama yang benar saat melihat rekan kerja tersengat listrik adalah...', 'Memutus sumber listrik terlebih dahulu', ['Langsung menarik tangannya', 'Menyiramnya dengan air', 'Memberinya minum', 'Meninggalkan lokasi']],
        ['Cara mengangkat beban berat yang benar adalah...', 'Menekuk lutut dengan punggung tetap tegak', ['Membungkuk dengan kaki lurus', 'Memutar badan sambil mengangkat', 'Mengangkat secepat mungkin', 'Menjauhkan beban dari badan']],
        ['Budaya kerja 5R (Ringkas, Rapi, Resik, Rawat, Rajin) diadaptasi dari metode Jepang yang disebut...', '5S', ['Kaizen', 'Kanban', 'Just in Time', 'Six Sigma']],
        ['Dokumen yang memuat hak dan kewajiban pekerja serta pengusaha disebut...', 'Perjanjian kerja', ['Slip gaji', 'Surat lamaran', 'Ijazah', 'Surat keterangan sehat']],
        ['Badan penyelenggara jaminan sosial ketenagakerjaan di Indonesia adalah...', 'BPJS Ketenagakerjaan', ['BPJS Kesehatan', 'Taspen', 'Asabri', 'OJK']],
        ['Hari Buruh Internasional diperingati setiap tanggal...', '1 Mei', ['2 Mei', '1 Juni', '17 Agustus', '10 November']],

        // Sejarah
        ['Kerajaan Hindu tertua di Indonesia adalah...', 'Kutai', ['Tarumanegara', 'Majapahit', 'Sriwijaya', 'Kediri']],
        ['Kerajaan maritim besar yang berpusat di Sumatra pada abad ke-7 adalah...', 'Sriwijaya', ['Majapahit', 'Mataram', 'Singasari', 'Demak']],
        ['Mahapatih Majapahit yang terkenal dengan Sumpah Palapa adalah...', 'Gajah Mada', ['Hayam Wuruk', 'Ken Arok', 'Raden Wijaya', 'Airlangga']],
        ['Organisasi Budi Utomo berdiri pada tahun...', '1908', ['1912', '1928', '1945', '1905']],
        ['Tokoh emansipasi wanita yang berasal dari Jepara adalah...', 'R.A. Kartini', ['Dewi Sartika', 'Cut Nyak Dhien', 'Fatmawati', 'Nyi Ageng Serang']],
        ['Pendiri perguruan Taman Siswa adalah...', 'Ki Hajar Dewantara', ['K.H. Ahmad Dahlan', 'Dr. Soetomo', 'H.O.S. Tjokroaminoto', 'Douwes Dekker']],
        ['Presiden pertama Republik Indonesia adalah...', 'Ir. Soekarno', ['Mohammad Hatta', 'Soeharto', 'B.J. Habibie', 'Sutan Sjahrir']],
        ['Wakil Presiden pertama Republik Indonesia adalah...', 'Mohammad Hatta', ['Ir. Soekarno', 'Sri Sultan Hamengkubuwono IX', 'Adam Malik', 'Sutan Sjahrir']],
        ['Tokoh yang dijuluki Bapak Koperasi Indonesia adalah...', 'Mohammad Hatta', ['Ir. Soekarno', 'Ki Hajar Dewantara', 'Dr. Soetomo', 'Ahmad Soebardjo']],
        ['Pertempuran besar 10 November 1945 terjadi di kota...', 'Surabaya', ['Bandung', 'Semarang', 'Medan', 'Ambarawa']],
        ['Peristiwa dibawanya Soekarno dan Hatta oleh golongan muda menjelang proklamasi dikenal sebagai peristiwa...', 'Rengasdengklok', ['Bandung Lautan Api', 'Serangan Umum 1 Maret', 'Pertempuran Ambarawa', 'Peristiwa Madiun']],
    ];
}
