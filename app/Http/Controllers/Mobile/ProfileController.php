<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AttendancePhotoService;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Display the employee's self-service profile page (FR-102 / US-206).
     */
    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $employee = $user->employee()
            ->with(['position', 'supervisor', 'atasan', 'faceTemplate'])
            ->first();

        $daysRemaining = null;
        if ($employee?->contract_end_date) {
            $daysRemaining = (int) now()->startOfDay()->diffInDays($employee->contract_end_date, false);
        }

        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');
        $photoUrl = ($employee?->photo_path) ? $publicDisk->url($employee->photo_path) : null;

        return view('mobile.profile', [
            'user' => $user,
            'employee' => $employee,
            'daysRemaining' => $daysRemaining,
            'photoUrl' => $photoUrl,
        ]);
    }

    /**
     * Upload or replace the authenticated employee's profile photo.
     */
    public function updatePhoto(Request $request, AttendancePhotoService $photoService): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'photo.required' => 'Silakan pilih berkas foto terlebih dahulu.',
            'photo.image' => 'Berkas harus berupa gambar yang valid.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('mobile.profile')
                ->with('error', 'Data profil kepegawaian tidak ditemukan.');
        }

        $uploadedFile = $request->file('photo');
        $compressedBinary = $photoService->sanitizeAndStripExif($uploadedFile->get());

        $safeCode = Str::slug($employee->employee_code ?: (string) $employee->id, '_');
        $fileName = sprintf('%s_profile_%s_%s.jpg', $safeCode, now()->format('Ymd_His'), Str::random(6));
        $newPath = "profiles/{$fileName}";

        Storage::disk('public')->put($newPath, $compressedBinary);

        $oldPath = $employee->photo_path;
        if ($oldPath && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $employee->update(['photo_path' => $newPath]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'PROFILE_PHOTO_UPDATED',
            'entity_type' => $employee::class,
            'entity_id' => $employee->id,
            'old_values' => ['photo_path' => $oldPath],
            'new_values' => ['photo_path' => $newPath],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now('Asia/Jakarta'),
        ]);

        return redirect()->route('mobile.profile')
            ->with('success', 'Foto profil berhasil diperbarui.');
    }

    /**
     * Delete the authenticated employee's profile photo and revert to initials avatar.
     */
    public function destroyPhoto(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('mobile.profile')
                ->with('error', 'Data profil kepegawaian tidak ditemukan.');
        }

        $oldPath = $employee->photo_path;
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        if ($oldPath !== null) {
            $employee->update(['photo_path' => null]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'PROFILE_PHOTO_DELETED',
                'entity_type' => $employee::class,
                'entity_id' => $employee->id,
                'old_values' => ['photo_path' => $oldPath],
                'new_values' => ['photo_path' => null],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now('Asia/Jakarta'),
            ]);
        }

        return redirect()->route('mobile.profile')
            ->with('success', 'Foto profil berhasil dihapus.');
    }
}
