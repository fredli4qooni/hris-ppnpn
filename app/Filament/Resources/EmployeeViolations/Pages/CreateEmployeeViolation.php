<?php

namespace App\Filament\Resources\EmployeeViolations\Pages;

use App\Filament\Resources\EmployeeViolations\EmployeeViolationResource;
use App\Models\EmployeeViolation;
use App\Models\MonthlyPeriodClosing;
use App\Models\ViolationCatalog;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateEmployeeViolation extends CreateRecord
{
    protected static string $resource = EmployeeViolationResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['supervisor_id'] = Auth::id();
        $data['status'] = EmployeeViolation::STATUS_ACTIVE;

        if (! empty($data['incident_date'])) {
            $incidentDate = Carbon::parse($data['incident_date']);
            if (MonthlyPeriodClosing::isPeriodLocked($incidentDate->year, $incidentDate->month)) {
                throw ValidationException::withMessages([
                    'incident_date' => 'Periode bulan insiden pelanggaran ini telah dikunci (READ ONLY).',
                ]);
            }
        }

        if (empty($data['points']) && ! empty($data['violation_catalog_id'])) {
            $catalog = ViolationCatalog::find($data['violation_catalog_id']);
            $data['points'] = $catalog?->points ?? 0;
        }

        return $data;
    }
}
