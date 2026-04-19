<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Filament\Resources\ClienteResource\RelationManagers;
use App\Models\Cliente;
use App\Models\Municipio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
  protected static ?string $model = Cliente::class;

  protected static ?string $navigationIcon = 'heroicon-o-user-group';

  protected static ?int $navigationSort = 5;

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\Select::make('id_documento_identificacion')
          ->label('Tipo de identificacion')
          ->options([
            '1' => 'Registro civil',
            '2' => 'Tarjeta de identidad',
            '3' => 'Cedula de ciudadania',
            '4' => 'Tarjeta de extranjeria',
            '5' => 'Cedula de extranjeria',
            '6' => 'NIT',
            '7' => 'Pasaporte',
            '8' => 'Documento de identificacion extranjero',
            '9' => 'PEP',
            '10' => 'NIT otro pais',
            '11' => 'NUIP*',
          ])
          ->searchable()
          ->required()
          ->live(),
        Forms\Components\TextInput::make('identificacion')
          ->label('Numero de identificacion')
          ->required()
          ->maxLength(12),
        Forms\Components\TextInput::make('dv')
          ->label('Digito de verificacion del NIT (dv)')
          ->numeric()
          ->disabled(fn(Get $get): bool => $get('id_documento_identificacion') !== '6'),
        Forms\Components\Select::make('tipo_organizacion')
          ->label('Tipo de organizacion')
          ->options([
            '1' => 'Persona juridica',
            '2' => 'Persona natural',
          ])
          ->required()
          ->live(),
        Forms\Components\TextInput::make('empresa')
          ->label('Razon social')
          ->maxLength(50)
          ->disabled(fn(Get $get): bool => $get('tipo_organizacion') !== '1'),
        Forms\Components\TextInput::make('nombre_comercial')
          ->maxLength(50),
        Forms\Components\TextInput::make('nombres')
          ->label('Nombre completo')
          ->maxLength(40)
          ->disabled(fn(Get $get): bool => $get('tipo_organizacion') !== '2'),
        Forms\Components\TextInput::make('direccion')
          ->maxLength(40),
        Forms\Components\TextInput::make('email')
          ->email()
          ->maxLength(40),
        Forms\Components\TextInput::make('n_celular')
          ->label('Numero de celular')
          ->maxLength(13),
        Forms\Components\Select::make('id_tributo')
          ->label('Tributo')
          ->options([
            '18' => 'IVA',
            '21' => 'No aplica *'
          ])
          ->required(),
        Forms\Components\Select::make('id_municipio')
          ->options(Municipio::query()->pluck('name', 'id'))
          ->label('Municipio')
          ->searchable()
          ->required(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('id_documento_identificacion')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('identificacion')
          ->searchable(),
        Tables\Columns\TextColumn::make('dv')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('empresa')
          ->searchable(),
        Tables\Columns\TextColumn::make('nombre_comercial')
          ->searchable(),
        Tables\Columns\TextColumn::make('nombres')
          ->searchable(),
        Tables\Columns\TextColumn::make('direccion')
          ->searchable(),
        Tables\Columns\TextColumn::make('email')
          ->searchable(),
        Tables\Columns\TextColumn::make('n_celular')
          ->searchable(),
        Tables\Columns\TextColumn::make('tipo_organizacion')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('id_tributo')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('id_municipio')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->modifyQueryUsing(function (Builder $query) {
        return $query
          ->where('clientes.id_usuario', auth()->id());
      })
      ->filters([
        //
      ])
      ->actions([
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
      'index' => Pages\ListClientes::route('/'),
      'create' => Pages\CreateCliente::route('/create'),
      'edit' => Pages\EditCliente::route('/{record}/edit'),
    ];
  }
}
