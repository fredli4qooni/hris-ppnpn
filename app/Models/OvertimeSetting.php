<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvertimeSetting extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'unit_code',
        'satker_name',
        'proposer_name',
        'signer_name',
        'signer_title',
        'signer_nip',
        'spkl_sequence_start',
        'sptpl_sequence_start',
        'spkl_template_body',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'spkl_sequence_start' => 'integer',
            'sptpl_sequence_start' => 'integer',
        ];
    }

    /**
     * Retrieve or initialize the active overtime configuration record.
     */
    public static function current(): self
    {
        $existing = self::query()->first();

        if ($existing) {
            return $existing;
        }

        return self::create([
            'unit_code' => 'WPB.0801',
            'satker_name' => 'Kantor Wilayah Direktorat Jenderal Perbendaharaan Provinsi Lampung',
            'proposer_name' => 'Mas Yudha',
            'signer_name' => 'Pak Dedi',
            'signer_title' => 'Kepala Bagian Umum',
            'signer_nip' => '196908021989121002',
            'spkl_sequence_start' => 1,
            'sptpl_sequence_start' => 1,
            'spkl_template_body' => self::defaultSpklTemplate(),
        ]);
    }

    /**
     * Default editable SPKL template body matching SE-89/PB/2018.
     */
    public static function defaultSpklTemplate(): string
    {
        return "Menunjuk Surat Edaran Direktur Jenderal Perbendaharaan Nomor SE-89/PB/2018 tentang Pedoman Administrasi Kehadiran dan Pelaporan Pelaksanaan Kerja Lembur di Lingkungan Direktorat Jenderal Perbendaharaan, kami memerintahkan pegawai yang namanya tercantum dalam Surat Pernyataan Perintah Lembur (SPTPL) sebagaimana tercantum dalam lampiran Surat Perintah ini untuk melaksanakan kerja lembur pada periode {period_label}.\n\nSegala biaya yang timbul berkenaan dengan pelaksanaan kerja lembur tersebut dibebankan pada DIPA {satker_name} Tahun {year}.\n\nDemikian untuk dilaksanakan dengan penuh tanggung jawab dengan tetap menjaga integritas dan Kode Etik Pegawai.";
    }
}
