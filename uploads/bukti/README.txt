1.1 Latar Belakang

Manajemen rantai pasok (supply chain management) memegang peranan vital dalam keberlangsungan bisnis ritel, khususnya pada usaha toko sembako yang memiliki perputaran barang sangat cepat. Dalam ekosistem ini, pemilihan supplier (pemasok) yang tepat menjadi faktor strategis yang menentukan kualitas produk, efisiensi biaya, dan kepuasan pelanggan akhir. Menurut Purba dan Asmarawati (2023), kesalahan dalam memilih supplier dapat berdampak fatal bagi toko sembako, seperti ketidakkonsistenan stok barang, kualitas produk yang buruk, hingga harga beli yang tidak kompetitif yang pada akhirnya menurunkan margin keuntungan.

Toko Sembako Basuki saat ini menghadapi tantangan dalam proses pengadaan barang. Proses pemilihan supplier masih dilakukan secara konvensional mengandalkan intuisi dan kebiasaan pemilik toko tanpa adanya standar evaluasi yang baku. Yassin (2024) dalam penelitiannya pada usaha ritel sejenis menyebutkan bahwa pemilihan supplier secara manual cenderung subjektif dan tidak transparan, sehingga sering kali mengabaikan kriteria penting lain seperti fleksibilitas pembayaran dan layanan purna jual. Hal ini mengakibatkan keputusan yang diambil sering kali tidak optimal, misalnya terpaku pada harga murah namun mengorbankan kualitas atau ketepatan waktu pengiriman.

Untuk mengatasi permasalahan subjektivitas tersebut, diperlukan sebuah Sistem Pendukung Keputusan (SPK) yang mampu mengolah data penilaian supplier secara objektif berdasarkan kriteria yang terukur. Kombinasi metode Analytical Hierarchy Process (AHP) dan Technique for Order Preference by Similarity to Ideal Solution (TOPSIS) dinilai efektif untuk menyelesaikan permasalahan ini. Sari dan Firmansyah (2023) menjelaskan bahwa metode AHP memiliki keunggulan dalam memecah kompleksitas kriteria menjadi bobot prioritas yang logis, sedangkan metode TOPSIS unggul dalam merangking alternatif supplier berdasarkan jarak terdekat dengan solusi ideal positif.

Integrasi kedua metode ini telah terbukti memberikan hasil akurasi yang tinggi dalam seleksi pemasok. Studi terbaru oleh Latif dan Wahyuning (2024) menunjukkan bahwa model hibrida AHP-TOPSIS mampu menyeimbangkan penilaian kriteria yang bersifat kualitatif dan kuantitatif, sehingga menghasilkan rekomendasi keputusan yang lebih valid dibandingkan metode tunggal. Berdasarkan urgensi tersebut, penelitian ini bertujuan membangun sistem berbasis web yang dapat membantu Toko Sembako Basuki dalam menentukan supplier terbaik secara cepat, akurat, dan terdokumentasi dengan baik.


1.2 Identifikasi Masalah

Berdasarkan latar belakang yang telah diuraikan, beberapa masalah yang dapat diidentifikasi adalah sebagai berikut:

Proses pemilihan supplier di Toko Sembako Basuki masih dilakukan secara manual dan subjektif berdasarkan intuisi pemilik, sehingga rentan terhadap kesalahan pengambilan keputusan.

Belum adanya standarisasi kriteria penilaian yang terukur dalam mengevaluasi kinerja supplier, yang menyebabkan ketidakkonsistenan dalam menjaga kualitas dan ketersediaan stok barang.

Pemilik toko mengalami kesulitan dalam membandingkan banyak supplier sekaligus dengan berbagai kriteria (harga, kualitas, tempo pembayaran) tanpa bantuan sistem komputasi yang memadai.


1.3 Rumusan Masalah

Berdasarkan identifikasi masalah di atas, maka rumusan masalah dalam penelitian ini adalah:

Bagaimana merancang dan membangun Sistem Pendukung Keputusan pemilihan supplier terbaik di Toko Sembako Basuki menggunakan kombinasi metode AHP dan TOPSIS berbasis web?

Bagaimana hasil implementasi metode AHP untuk pembobotan kriteria dan TOPSIS untuk perankingan alternatif dalam memberikan rekomendasi supplier yang objektif dan akurat?


1.4 Batasan Penelitian

Agar pembahasan lebih terarah dan tidak menyimpang dari tujuan, penulis menetapkan batasan penelitian sebagai berikut:

Penelitian dilakukan di Toko Sembako Basuki dengan objek penelitian adalah data supplier aktif yang bekerja sama dengan toko.

Metode yang digunakan adalah Analytical Hierarchy Process (AHP) untuk menentukan bobot kriteria dan Technique for Order Preference by Similarity to Ideal Solution (TOPSIS) untuk melakukan perankingan alternatif.

Kriteria yang digunakan dalam evaluasi meliputi: Harga, Kualitas Produk, Ketepatan Pengiriman, Pelayanan, dan Jangka Waktu Pembayaran.

Sistem dibangun berbasis web menggunakan bahasa pemrograman PHP dengan framework Laravel dan basis data MySQL.


1.5 Tujuan Penelitian

Tujuan yang ingin dicapai dari penelitian ini adalah:

Menghasilkan aplikasi Sistem Pendukung Keputusan berbasis web yang dapat mempermudah Toko Sembako Basuki dalam menyeleksi dan mengevaluasi kinerja supplier.

Menerapkan kombinasi metode AHP dan TOPSIS untuk memberikan rekomendasi pemilihan supplier yang objektif berdasarkan bobot kriteria yang telah ditentukan.

1.6 Manfaat Penelitian

Penelitian ini diharapkan dapat memberikan manfaat sebagai berikut:

1.6.1 Manfaat Teoritis

Memberikan kontribusi akademik dalam penerapan ilmu Sistem Pendukung Keputusan (SPK), khususnya integrasi metode AHP dan TOPSIS.

Menjadi referensi bagi penelitian selanjutnya yang mengangkat topik serupa mengenai manajemen rantai pasok dan sistem pengambilan keputusan pada UMKM.

1.6.2 Manfaat Praktis

Bagi Toko Sembako Basuki: Membantu pemilik toko mendapatkan supplier terbaik secara efisien, mengurangi risiko kerugian akibat barang buruk, dan menjaga stabilitas stok barang.

Bagi Penulis: Sebagai sarana mengimplementasikan ilmu yang diperoleh selama perkuliahan serta menambah wawasan mengenai pengembangan sistem informasi.

Bagi Universitas Pamulang: Menambah perbendaharaan karya ilmiah di perpustakaan yang dapat digunakan sebagai bahan acuan bagi mahasiswa lain.



1.7 Metodologi Penelitian

Untuk mencapai tujuan penelitian, penulis menggunakan metodologi yang sistematis dengan tahapan sebagai berikut:

Pengumpulan Data

Observasi: Mengamati langsung proses pengadaan barang dan pemilihan supplier yang berjalan di Toko Sembako Basuki.

Wawancara: Melakukan tanya jawab dengan pemilik toko untuk mengetahui kriteria yang diinginkan dan kendala yang dihadapi.

Studi Pustaka: Mengumpulkan landasan teori dari jurnal (minimal tahun 2023), buku, dan artikel ilmiah terkait SPK, AHP, TOPSIS, dan pemrograman web.

Analisis Sistem
Menganalisis kebutuhan sistem fungsional dan non-fungsional, serta merancang alur logika metode AHP dan TOPSIS.

Perancangan Sistem
Membuat desain sistem menggunakan Unified Modeling Language (UML) yang terdiri dari Use Case Diagram, Activity Diagram, Sequence Diagram, dan Class Diagram, serta merancang database dan antarmuka (interface).

Implementasi
Membangun aplikasi menggunakan bahasa pemrograman PHP (Framework Laravel) dan database MySQL sesuai rancangan.

Pengujian (Testing)
Melakukan pengujian sistem menggunakan metode Black Box Testing untuk memastikan fitur berjalan sesuai fungsinya dan memvalidasi hasil perhitungan metode.




1.8 Sistematika Penulisan

Penyusunan skripsi ini dibagi menjadi lima bab dengan sistematika sebagai berikut:

BAB I PENDAHULUAN
Bab ini membahas latar belakang masalah, identifikasi masalah, rumusan masalah, batasan penelitian, tujuan dan manfaat penelitian, metodologi penelitian, serta sistematika penulisan.

BAB II LANDASAN TEORI
Bab ini memuat tinjauan pustaka dari penelitian terdahulu yang relevan (diutamakan 5 tahun terakhir), serta landasan teori mengenai SPK, metode AHP, metode TOPSIS, dan tools pengembangan sistem seperti PHP, Laravel, dan MySQL.

BAB III ANALISA DAN PERANCANGAN
Bab ini menguraikan analisis sistem berjalan, analisis kebutuhan sistem usulan, serta perancangan sistem yang meliputi desain UML, desain database, desain algoritma metode, dan desain antarmuka.

BAB IV IMPLEMENTASI DAN PENGUJIAN
Bab ini berisi implementasi perangkat lunak dari hasil perancangan, tampilan antarmuka program, serta hasil pengujian sistem dan analisis akurasi keputusan.

BAB V PENUTUP
Bab ini berisi kesimpulan dari hasil penelitian yang menjawab rumusan masalah serta saran-saran untuk pengembangan sistem lebih lanjut.


