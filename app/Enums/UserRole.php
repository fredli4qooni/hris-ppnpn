<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case ATASAN = 'atasan';
    case KARYAWAN = 'karyawan';

    /**
     * Human-readable role label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator Sistem',
            self::SUPERVISOR => 'Supervisor Lapangan',
            self::ATASAN => 'Pejabat Penyetuju (Atasan)',
            self::KARYAWAN => 'Pegawai PPNPN',
        };
    }

    /**
     * Determine if this role has backoffice panel access.
     */
    public function canAccessFilament(): bool
    {
        return in_array($this, [self::ADMIN, self::SUPERVISOR, self::ATASAN], true);
    }
}
