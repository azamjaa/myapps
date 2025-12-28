<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    /**
     * Override the form to use 'no_kp' (IC Number) instead of email
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNoKpFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    /**
     * Custom No. KP field (replacing email)
     */
    protected function getNoKpFormComponent(): Component
    {
        return TextInput::make('no_kp')
            ->label('No. Kad Pengenalan')
            ->placeholder('900101011234')
            ->required()
            ->maxLength(12)
            ->minLength(12)
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes([
                'tabindex' => 1,
                'inputmode' => 'numeric',
                'class' => 'text-center tracking-wider',
            ])
            ->helperText('Masukkan 12 digit No. KP tanpa sengkang (-)');
    }

    /**
     * Override password field for better UX
     */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Kata Laluan')
            ->password()
            ->revealable()
            ->required()
            ->autocomplete('current-password')
            ->extraInputAttributes([
                'tabindex' => 2,
            ]);
    }

    /**
     * Get credentials for authentication using no_kp
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'no_kp' => $data['no_kp'],
            'password' => $data['password'],
        ];
    }

    /**
     * Custom heading
     */
    public function getHeading(): string
    {
        return 'Portal MyApps KEDA';
    }

    /**
     * Custom subheading
     */
    public function getSubHeading(): string
    {
        return 'Single Sign-On untuk Semua Aplikasi';
    }
}



