<?php

namespace Database\Seeders;

use App\Models\ViolationCatalog;
use Illuminate\Database\Seeder;

class ViolationCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds 45 official violation clauses based on Lampiran II SPK-20/WPB.08/PPK/2025.
     */
    public function run(): void
    {
        $clauses = [
            // Kategori A: Kehadiran (A1 - A14)
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A1',
                'description' => 'Terlambat hingga 15 menit (dikecualikan: kecelakaan, kendala kendaraan, keluarga sakit, takziah, bencana dengan bukti). Wajib ganti jam 15 menit.',
                'points' => 0,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A2',
                'description' => 'Terlambat/PSW 15 menit sampai 1 jam (dengan pengecualian yang sama).',
                'points' => 5,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A3',
                'description' => 'Terlambat/PSW lebih dari 1 jam.',
                'points' => 10,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A4',
                'description' => 'Izin meninggalkan area kerja saat istirahat untuk keperluan selain Ishoma (jadwal istirahat 1 jam; Ishoma tidak perlu izin).',
                'points' => 0,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A5',
                'description' => 'Meninggalkan area kerja tanpa izin pada jam kerja atau jam istirahat selain Ishoma.',
                'points' => 25,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A6',
                'description' => 'Terlambat kembali ke area kerja setelah istirahat.',
                'points' => 3,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A7',
                'description' => 'Izin meninggalkan tugas untuk jangka waktu tertentu (kecuali keluarga inti sakit/wafat).',
                'points' => 3,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A8',
                'description' => 'Izin meninggalkan tugas sampai jam pulang (kecuali keluarga inti sakit/wafat).',
                'points' => 5,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A9',
                'description' => 'Tidak masuk 1 hari dengan mengajukan cuti terlebih dahulu (maks. 3 hari). Kuota cuti tahunan berkurang 1 hari.',
                'points' => 0,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A10',
                'description' => 'Tidak masuk 1 hari dengan surat keterangan dokter (maks. 3 hari).',
                'points' => 0,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A11',
                'description' => 'Tidak masuk 1 hari dengan izin di luar cuti tahunan (maks. 3 hari).',
                'points' => 3,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A12',
                'description' => 'Tidak masuk tanpa keterangan (Alpa).',
                'points' => 20,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A13',
                'description' => 'Lupa absen dengan bukti akurat (dengan surat keterangan).',
                'points' => 0,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEHADIRAN_A,
                'clause_number' => 'A14',
                'description' => 'Lupa absen tanpa bukti akurat.',
                'points' => 3,
            ],

            // Kategori B: Kewajiban (B1 - B15)
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B1',
                'description' => 'Tidak setia dan taat kepada Pancasila, UUD 1945, NKRI, dan Pemerintah.',
                'points' => 100,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B2',
                'description' => 'Tidak menaati ketentuan peraturan perundang-undangan.',
                'points' => 100,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B3',
                'description' => 'Tidak menjunjung tinggi kehormatan negara, pemerintah, dan martabat satuan kerja.',
                'points' => 100,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B4',
                'description' => 'Tidak menggunakan dan memelihara barang milik negara dengan sebaik-baiknya.',
                'points' => 50,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B5',
                'description' => 'Tidak mengajukan izin tertulis kepada pejabat berwenang bila tidak masuk/pulang sebelum waktunya/terlambat.',
                'points' => 10,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B6',
                'description' => 'Tidak melaporkan pelaksanaan pekerjaan secara periodik kepada Pihak Pertama.',
                'points' => 10,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B7',
                'description' => 'Tidak melaksanakan dan menyelesaikan pekerjaan sesuai jadwal dalam Kontrak.',
                'points' => 15,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B8',
                'description' => 'Tidak melaksanakan pekerjaan secara cermat, akurat, dan bertanggung jawab sesuai Kontrak.',
                'points' => 20,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B9',
                'description' => 'Tidak memberikan keterangan yang diperlukan untuk pemeriksaan oleh Pihak Pertama.',
                'points' => 15,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B10',
                'description' => 'Tidak menyerahkan hasil pekerjaan sesuai jadwal dan tempat dalam Kontrak.',
                'points' => 30,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B11',
                'description' => 'Tidak mengambil langkah memadai melindungi lingkungan tempat kerja dan membatasi perusakan/gangguan.',
                'points' => 50,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B12',
                'description' => 'Tidak menghindari pertentangan kepentingan (conflict of interest).',
                'points' => 40,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B13',
                'description' => 'Tidak melaksanakan kewajiban pembayaran BPJS Kesehatan, BPJS Ketenagakerjaan, dan perpajakan serta melaporkannya.',
                'points' => 15,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B14',
                'description' => 'Tidak mematuhi ketentuan kehadiran, kewajiban, dan kedisiplinan pada lampiran Kontrak.',
                'points' => 10,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEWAJIBAN_B,
                'clause_number' => 'B15',
                'description' => 'Tidak menaati ketentuan atau peraturan yang berlaku di Kanwil DJPb Provinsi Lampung.',
                'points' => 50,
            ],

            // Kategori C: Kedisiplinan (C1 - C16)
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C1',
                'description' => 'Menerima gratifikasi dalam pelaksanaan tugas layanan kebersihan (pelapor gratifikasi mendapat poin penghargaan 15 selama 1 tahun).',
                'points' => 100,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C2',
                'description' => 'Bermain judi di area Kementerian Keuangan.',
                'points' => 100,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C3',
                'description' => 'Mengonsumsi alkohol dan narkoba.',
                'points' => 100,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C4',
                'description' => 'Melakukan tindakan kriminal dan asusila.',
                'points' => 100,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C5',
                'description' => 'Pemalsuan dokumen.',
                'points' => 40,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C6',
                'description' => 'Kesaksian/informasi palsu.',
                'points' => 50,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C7',
                'description' => 'Membocorkan rahasia jabatan atau kode etik (langsung dan media sosial).',
                'points' => 50,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C8',
                'description' => 'Penyalahgunaan media sosial yang berpengaruh negatif terhadap Kementerian Keuangan.',
                'points' => 10,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C9',
                'description' => 'Penyalahgunaan wewenang.',
                'points' => 15,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C10',
                'description' => 'Menjadi perantara untuk keuntungan pribadi/orang lain dengan menggunakan kewenangan orang lain.',
                'points' => 40,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C11',
                'description' => 'Memiliki, menjual, membeli, menggadaikan, menyewakan, atau meminjamkan barang/surat berharga milik negara secara tidak sah.',
                'points' => 15,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C12',
                'description' => 'Bekerja rangkap di tempat lain pada jam kerja yang disepakati.',
                'points' => 30,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C13',
                'description' => 'Menghalangi berjalannya tugas kedinasan.',
                'points' => 30,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C14',
                'description' => 'Berkelahi.',
                'points' => 50,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C15',
                'description' => 'Menyebarkan informasi tidak benar yang berpotensi merugikan Kementerian Keuangan.',
                'points' => 10,
            ],
            [
                'category' => ViolationCatalog::CATEGORY_KEDISIPLINAN_C,
                'clause_number' => 'C16',
                'description' => 'Kegiatan bersama atasan/sejawat/pihak lain untuk keuntungan pribadi/golongan yang merugikan negara secara langsung atau tidak langsung.',
                'points' => 10,
            ],
        ];

        foreach ($clauses as $clause) {
            ViolationCatalog::updateOrCreate(
                ['clause_number' => $clause['clause_number']],
                [
                    'category' => $clause['category'],
                    'description' => $clause['description'],
                    'points' => $clause['points'],
                    'is_active' => true,
                ]
            );
        }
    }
}
