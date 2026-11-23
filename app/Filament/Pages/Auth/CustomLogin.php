<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

class CustomLogin extends BaseLogin
{
    /**
     * Get the form component for the username/email input.
     */
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username / NIP / Email')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    /**
     * Transform form data into credentials array supporting both username and email.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = trim((string) ($data['login'] ?? ''));
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $loginType => $login,
            'password' => $data['password'] ?? '',
        ];
    }

    /**
     * Authenticate and handle mandatory first-time password change.
     */
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response && Filament::auth()->check()) {
            /** @var User $user */
            $user = Filament::auth()->user();

            // Redirect if must change password
            if ($user->must_change_password) {
                session()->flash('warning', 'Demi keamanan, Anda wajib mengganti kata sandi pada login pertama kali.');

                $this->redirect(route('password.first-change'), navigate: false);

                return null;
            }
        }

        return $response;
    }
}
