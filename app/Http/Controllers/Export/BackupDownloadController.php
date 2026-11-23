<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\SystemBackupRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupDownloadController extends Controller
{
    /**
     * Download encrypted / zipped backup file (Strictly Admin).
     */
    public function download(Request $request, SystemBackupRecord $record): BinaryFileResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasRole('admin')) {
            abort(403, 'Akses ditolak. Pengunduhan arsip cadangan DRP hanya diperkenankan bagi Administrator Sistem.');
        }

        if (! File::exists($record->file_path)) {
            abort(404, 'Berkas arsip cadangan tidak ditemukan di media penyimpanan server.');
        }

        return response()->download($record->file_path, $record->filename, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
