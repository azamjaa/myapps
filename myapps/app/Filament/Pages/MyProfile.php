<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use App\Models\Audit;
use Illuminate\Support\Facades\Auth;

class MyProfile extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Profil Saya';
    protected static ?string $title = 'Profil Saya';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.my-profile';

    public function getStaf()
    {
        return Auth::user()->load(['jawatan', 'gred', 'bahagian', 'status']);
    }

    public function getActivityFeed()
    {
        return Audit::where('id_pengguna', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function staffInfolist(Infolist $infolist): Infolist
    {
        $staf = $this->getStaf();

        return $infolist
            ->record($staf)
            ->schema([
                Section::make('Maklumat Peribadi')
                    ->description('Maklumat asas peribadi anda')
                    ->icon('heroicon-m-user')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('gambar')
                            ->label('Foto')
                            ->circular()
                            ->defaultImageUrl(asset('images/default-avatar.png'))
                            ->size(150)
                            ->columnSpanFull(),

                        TextEntry::make('no_staf')
                            ->label('No. Staf')
                            ->icon('heroicon-m-identification')
                            ->color('primary')
                            ->weight('bold'),

                        TextEntry::make('no_kp')
                            ->label('No. Kad Pengenalan')
                            ->icon('heroicon-m-credit-card')
                            ->copyable()
                            ->copyMessage('No. K/P disalin!')
                            ->copyMessageDuration(1500),

                        TextEntry::make('nama')
                            ->label('Nama Penuh')
                            ->icon('heroicon-m-user')
                            ->size('lg')
                            ->weight('bold')
                            ->columnSpanFull(),

                        TextEntry::make('emel')
                            ->label('Emel')
                            ->icon('heroicon-m-envelope')
                            ->copyable()
                            ->url(fn($record) => "mailto:{$record->emel}"),

                        TextEntry::make('telefon')
                            ->label('Telefon')
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->url(fn($record) => "tel:{$record->telefon}"),

                        TextEntry::make('birthday')
                            ->label('Tarikh Lahir')
                            ->icon('heroicon-m-cake')
                            ->date('d/m/Y'),

                        TextEntry::make('age')
                            ->label('Umur')
                            ->suffix(' tahun')
                            ->icon('heroicon-m-calendar'),
                    ]),

                Section::make('Maklumat Pekerjaan')
                    ->description('Maklumat jawatan dan penempatan')
                    ->icon('heroicon-m-briefcase')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('jawatan.nama_jawatan')
                            ->label('Jawatan')
                            ->icon('heroicon-m-briefcase')
                            ->color('primary')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('gred.nama_gred')
                            ->label('Gred')
                            ->icon('heroicon-m-academic-cap')
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('bahagian.nama_bahagian')
                            ->label('Bahagian')
                            ->icon('heroicon-m-building-office')
                            ->color('info'),

                        TextEntry::make('status.status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'Masih Bekerja' => 'success',
                                'Bersara' => 'warning',
                                'Berhenti' => 'danger',
                                default => 'secondary',
                            })
                            ->icon(fn ($state) => match ($state) {
                                'Masih Bekerja' => 'heroicon-m-check-circle',
                                'Bersara' => 'heroicon-m-clock',
                                'Berhenti' => 'heroicon-m-x-circle',
                                default => 'heroicon-m-question-mark-circle',
                            }),
                    ]),

                Section::make('SSOT API Endpoint')
                    ->description('Endpoint API untuk maklumat anda')
                    ->icon('heroicon-m-code-bracket')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('no_kp')
                            ->label('API URL')
                            ->formatStateUsing(fn($state) => url('/api/v1/staf/' . $state))
                            ->copyable()
                            ->copyMessage('API URL disalin!')
                            ->icon('heroicon-m-link')
                            ->color('success')
                            ->columnSpanFull(),

                        ViewEntry::make('api_example')
                            ->label('')
                            ->view('filament.infolists.api-example'),
                    ]),
            ]);
    }
}

