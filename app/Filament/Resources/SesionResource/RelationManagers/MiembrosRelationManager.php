<?php

namespace App\Filament\Resources\SesionResource\RelationManagers;

use App\Exceptions\UsuarioYaAsignadoException;
use App\Models\User;
use App\Services\MembresiaNegocio;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MiembrosRelationManager extends RelationManager
{
    protected static string $relationship = 'usuarios';

    protected static ?string $title = 'Miembros';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
            ])
            ->headerActions([$this->accionAsignar()])
            ->actions([$this->accionQuitar()]);
    }

    private function accionAsignar(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('asignar')
            ->label('Asignar usuario')
            ->icon('heroicon-o-user-plus')
            ->form([
                Forms\Components\Select::make('id_user')
                    ->label('Usuario')
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data): void {
                try {
                    app(MembresiaNegocio::class)->asignar(User::findOrFail($data['id_user']), $this->getOwnerRecord());
                } catch (UsuarioYaAsignadoException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Usuario asignado')->success()->send();
            });
    }

    private function accionQuitar(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('quitar')
            ->label('Quitar')
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function (Model $record): void {
                app(MembresiaNegocio::class)->retirar($record, $this->getOwnerRecord());

                Notification::make()->title('Usuario retirado')->success()->send();
            });
    }
}
