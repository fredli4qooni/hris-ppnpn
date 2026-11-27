<?php

namespace App\Filament\Resources\OvertimeMasterOrders\Pages;

use App\Filament\Resources\OvertimeMasterOrders\OvertimeMasterOrderResource;
use App\Models\OvertimeMasterOrder;
use App\Models\User;
use App\Services\OvertimeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateOvertimeMasterOrder extends CreateRecord
{
    protected static string $resource = OvertimeMasterOrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $user */
        $user = Auth::user();

        $order = app(OvertimeService::class)->getOrCreateMonthlyMasterOrder(
            (int) $data['period_year'],
            (int) $data['period_month'],
            $user
        );

        $order->update([
            'letter_date' => $data['letter_date'] ?? $order->letter_date,
            'signer_name' => $data['signer_name'] ?? $order->signer_name,
            'signer_title' => $data['signer_title'] ?? $order->signer_title,
            'signer_nip' => $data['signer_nip'] ?? $order->signer_nip,
            'template_body' => $data['template_body'] ?? $order->template_body,
            'signed_pdf_path' => $data['signed_pdf_path'] ?? $order->signed_pdf_path,
            'status' => $data['status'] ?? OvertimeMasterOrder::STATUS_DRAFT,
            'notes' => $data['notes'] ?? null,
        ]);

        return $order->refresh();
    }
}
