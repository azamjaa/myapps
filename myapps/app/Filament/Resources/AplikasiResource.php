<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AplikasiResource\Pages;
use App\Filament\Resources\AplikasiResource\RelationManagers;
use App\Models\Aplikasi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AplikasiResource extends Resource
{
    protected static ?string $model = Aplikasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id_kategori')
                    ->numeric(),
                Forms\Components\TextInput::make('nama_aplikasi')
                    ->maxLength(100),
                Forms\Components\DateTimePicker::make('tarikh_daftar'),
                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('url')
                    ->maxLength(255)
                    ->default('#'),
                Forms\Components\TextInput::make('warna_bg')
                    ->maxLength(20)
                    ->default('bg-white'),
                Forms\Components\TextInput::make('sso_comply')
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('status')
                    ->numeric()
                    ->default(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_kategori')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_aplikasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tarikh_daftar')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('warna_bg')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sso_comply')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAplikasis::route('/'),
            'create' => Pages\CreateAplikasi::route('/create'),
            'view' => Pages\ViewAplikasi::route('/{record}'),
            'edit' => Pages\EditAplikasi::route('/{record}/edit'),
        ];
    }
}
