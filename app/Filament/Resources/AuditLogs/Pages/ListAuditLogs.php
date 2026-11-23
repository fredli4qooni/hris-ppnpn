<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected ?string $heading = 'Penelusur Jejak Audit Permanen ';

    protected ?string $subheading = 'Pusat inspeksi akuntabilitas mutasi data kritis sistem. Seluruh rekaman bersifat mutlak hanya baca (read-only) dan dilindungi hukum.';

    /**
     * Absolute immutability: No create actions on the header.
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
