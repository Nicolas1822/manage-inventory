<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SesionResource\Pages;
use App\Filament\Resources\SesionResource\RelationManagers\MiembrosRelationManager;
use App\Models\Sesion;
use App\Services\NegocioActual;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SesionResource extends Resource
{
    protected static ?string $model = Sesion::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Negocios';

    protected static ?string $modelLabel = 'negocio';

    protected static ?string $pluralModelLabel = 'negocios';

    protected static ?int $navigationSort = 90;

    private static function esSuperadmin(): bool
    {
        return app(NegocioActual::class)->esSuperadmin();
    }

    public static function canViewAny(): bool
    {
        return self::esSuperadmin();
    }

    public static function canCreate(): bool
    {
        return self::esSuperadmin();
    }

    public static function canEdit(Model $record): bool
    {
        return self::esSuperadmin();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->required()
                ->maxLength(100),
            Forms\Components\TextInput::make('codigo')
                ->label('Código')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('descripcion')
                ->label('Descripción')
                ->columnSpanFull(),
            Forms\Components\Toggle::make('estado')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('codigo')->label('Código')->searchable(),
                Tables\Columns\IconColumn::make('estado')->label('Activo')->boolean(),
                Tables\Columns\TextColumn::make('usuarios_count')
                    ->label('Miembros')
                    ->counts('usuarios'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MiembrosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSesions::route('/'),
            'create' => Pages\CreateSesion::route('/create'),
            'edit' => Pages\EditSesion::route('/{record}/edit'),
        ];
    }
}
