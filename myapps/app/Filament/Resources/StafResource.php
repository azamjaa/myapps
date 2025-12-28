<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StafResource\Pages;
use App\Filament\Resources\StafResource\RelationManagers;
use App\Models\Staf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StafResource extends Resource
{
    protected static ?string $model = Staf::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Pengurusan Staf';
    protected static ?string $modelLabel = 'Staf';
    protected static ?string $pluralModelLabel = 'Staf';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Maklumat Peribadi')
                    ->description('Maklumat asas peribadi staf')
                    ->schema([
                        Forms\Components\TextInput::make('no_staf')
                            ->label('No. Staf')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\TextInput::make('no_kp')
                            ->label('No. Kad Pengenalan')
                            ->required()
                            ->length(12)
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->hint('12 digit tanpa sengkang'),
                        
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Penuh')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('emel')
                            ->label('Emel')
                            ->email()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\TextInput::make('telefon')
                            ->label('No. Telefon')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\FileUpload::make('gambar')
                            ->label('Gambar Profil')
                            ->image()
                            ->directory('gambar')
                            ->imageEditor()
                            ->maxSize(2048),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Maklumat Pekerjaan')
                    ->description('Jawatan, gred, bahagian dan status')
                    ->schema([
                        Forms\Components\Select::make('id_jawatan')
                            ->label('Jawatan')
                            ->relationship('jawatan', 'jawatan')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('jawatan')
                                    ->required()
                                    ->maxLength(100),
                            ]),
                        
                        Forms\Components\Select::make('id_gred')
                            ->label('Gred')
                            ->relationship('gred', 'gred')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('gred')
                                    ->required()
                                    ->maxLength(10),
                            ]),
                        
                        Forms\Components\Select::make('id_bahagian')
                            ->label('Bahagian')
                            ->relationship('bahagian', 'bahagian')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('bahagian')
                                    ->required()
                                    ->maxLength(100),
                            ]),
                        
                        Forms\Components\Select::make('id_status')
                            ->label('Status')
                            ->relationship('status', 'status')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(1),
                        
                        Forms\Components\TextInput::make('password')
                            ->label('Password (Optional)')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->hint('Kosongkan jika tidak mahu tukar'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_staf')
                    ->label('No. Staf')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('emel')
                    ->label('Emel')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                    
                Tables\Columns\TextColumn::make('jawatan.jawatan')
                    ->label('Jawatan')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('gred.gred')
                    ->label('Gred')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('bahagian.bahagian')
                    ->label('Bahagian')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('status.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Masih Bekerja' => 'success',
                        'Bersara' => 'info',
                        'Berhenti' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('loginRecord.role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'warning',
                        'user' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_bahagian')
                    ->label('Bahagian')
                    ->relationship('bahagian', 'bahagian')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('id_jawatan')
                    ->label('Jawatan')
                    ->relationship('jawatan', 'jawatan')
                    ->multiple()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('id_status')
                    ->label('Status')
                    ->relationship('status', 'status')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama', 'asc');
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
            'index' => Pages\ListStafs::route('/'),
            'create' => Pages\CreateStaf::route('/create'),
            'view' => Pages\ViewStaf::route('/{record}'),
            'edit' => Pages\EditStaf::route('/{record}/edit'),
        ];
    }
}
