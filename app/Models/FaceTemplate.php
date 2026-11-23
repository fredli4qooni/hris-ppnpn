<?php

namespace App\Models;

use App\Services\BiometricVerificationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceTemplate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'encrypted_vector',
        'sample_count',
        'model_version',
        'enrolled_by_admin_id',
        'enrolled_at',
    ];

    /**
     * The attributes that should be hidden for serialization (UU PDP No. 27/2022).
     *
     * @var list<string>
     */
    protected $hidden = [
        'encrypted_vector',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sample_count' => 'integer',
            'enrolled_at' => 'datetime',
        ];
    }

    /**
     * Employee who owns this face template.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Admin who enrolled or authorized this template.
     */
    public function enrolledByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by_admin_id');
    }

    /**
     * Decrypt the biometric vector into a 128-float array.
     *
     * @return list<float>
     */
    public function getDecryptedVector(): array
    {
        return app(BiometricVerificationService::class)->decryptVector($this->encrypted_vector);
    }

    /**
     * Encrypt and store the biometric vector.
     *
     * @param  list<float>  $vector
     */
    public function setEncryptedVector(array $vector): void
    {
        $this->encrypted_vector = app(BiometricVerificationService::class)->encryptVector($vector);
    }
}
