<?php

namespace App\Filament\Resources\OvertimeMasterOrders\Pages;

use App\Filament\Resources\OvertimeMasterOrders\OvertimeMasterOrderResource;
use App\Models\MonthlyPeriodClosing;
use App\Models\OvertimeSetting;
use App\Models\User;
use App\Services\OvertimeService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ListOvertimeMasterOrders extends ListRecords
{
    protected static string $resource = OvertimeMasterOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_monthly_spkl')
                ->label('Buat Draf SPKL Bulan Ini')
                ->icon(Heroicon::OutlinedDocumentPlus)
                ->color('primary')
                ->schema([
                    Select::make('period_month')
                        ->label('Bulan')
                        ->options(MonthlyPeriodClosing::MONTH_NAMES)
                        ->default(now('Asia/Jakarta')->month)
                        ->required(),

                    Select::make('period_year')
                        ->label('Tahun')
                        ->options([2025 => '2025', 2026 => '2026', 2027 => '2027'])
                        ->default(now('Asia/Jakarta')->year)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = Auth::user();
                    $order = app(OvertimeService::class)->getOrCreateMonthlyMasterOrder(
                        (int) $data['period_year'],
                        (int) $data['period_month'],
                        $user
                    );

                    Notification::make()
                        ->title('Draf SPKL Siap')
                        ->body("SPKL {$order->letter_number} untuk periode {$order->getPeriodLabel()} tersedia.")
                        ->success()
                        ->send();
                }),

            Action::make('overtime_settings')
                ->label('Pengaturan Lembur')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->visible(fn (): bool => (bool) Auth::user()?->hasRole('admin'))
                ->fillForm(fn (): array => OvertimeSetting::current()->toArray())
                ->schema([
                    TextInput::make('unit_code')->label('Kode Unit Surat')->required(),
                    TextInput::make('satker_name')->label('Nama Satuan Kerja')->required(),
                    TextInput::make('proposer_name')->label('Nama Pengusul Default (Admin)')->required(),
                    TextInput::make('signer_name')->label('Nama Penandatangan Default (Atasan)')->required(),
                    TextInput::make('signer_title')->label('Jabatan Penandatangan')->required(),
                    TextInput::make('signer_nip')->label('NIP Penandatangan'),
                    TextInput::make('spkl_sequence_start')->label('Nomor Urut Awal SPKL')->numeric()->required(),
                    TextInput::make('sptpl_sequence_start')->label('Nomor Urut Awal SPTPL (Migrasi)')->numeric()->required(),
                    Textarea::make('spkl_template_body')->label('Templat Teks SPKL')->rows(5)->required(),
                ])
                ->action(function (array $data): void {
                    OvertimeSetting::current()->update($data);

                    Notification::make()
                        ->title('Pengaturan Lembur Disimpan')
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
