<?php

namespace App\Livewire\Organization;

use Livewire\Component;
use App\Models\Tenant\Role;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\Models\Tenant\Permission;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\Tenant\Client as User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\Actions\Action as FormAction;

class UserOrganizationForm extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;
    public mixed $organization;

    public function mount(mixed $organization): void
    {
        $this->organization = $organization;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {

                $userInOrganization = DB::table('client_organization')
                    ->where('organization_id', $this->organization->id)
                    ->pluck('client_id')
                    ->toArray();

                return User::query()->whereIn('id', $userInOrganization)->with('organizations');
            })
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Perfil')
                    ->badge(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->getStateUsing(function (User $record) {

                        $organization = $record
                            ->organizations()
                            ->where('organizations.id', $this->organization->id)
                            ->first();

                        return $organization->pivot->is_active;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),


                TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->getStateUsing(function (User $record) {

                        $organization = $record
                            ->organizations()
                            ->where('organizations.id', $this->organization->id)
                            ->first();

                        return $organization->pivot->expires_at;
                    })
                    ->date('d/m/Y'),

            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('edit-user')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Atualizar permissões')
                    ->modalWidth('lg')
                    ->hidden(fn (User $record) => $record->hasRole('super-admin'))
                    ->modalSubmitActionLabel('Salvar')
                    ->fillForm(function (User $record) {

                        $organization = $record
                            ->organizations()
                            ->where('organizations.id', $this->organization->id)
                            ->first();

                        return [
                            'expires_at' => $organization->pivot->expires_at,
                            'is_active' => $organization->pivot->is_active,
                            'roles' => $record->roles->pluck('id'),
                            'permissions' => $record->permissions->pluck('id'),
                        ];
                    })
                    ->form($this->userVinculationForm())
                    ->action(function (User $user, array $data) {
                        $user->organizations()
                            ->updateExistingPivot($this->organization->id, ['is_active' => $data['is_active'], 'expires_at' => $data['expires_at']]);

                        $user->roles()->sync($data['roles']);
                        $user->permissions()->sync($data['permissions']);


                        Notification::make()
                            ->title('Usuário atualziado com sucesso.')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('attach')
                    ->label('Vincular')
                    ->modalWidth('lg')
                    ->modalSubmitActionLabel('Vincular')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('instructions')
                                    ->label('Instruções')
                                    ->content('Pesquise o usuário que deseja vincular. Caso ele não tenha cadastro, basta clicar no botão (+) para adicionar')
                                    ->columnSpan(2),
                                Select::make('user_id')
                                    ->label('Usuários')
                                    ->searchable()
                                    ->columnSpan(2)
                                    ->createOptionForm([
                                        Section::make('Dados do Usuário')
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Nome')
                                                    ->required()
                                                    ->columnSpan(2),
                                                TextInput::make('email')
                                                    ->label('E-mail')
                                                    ->required()
                                                    ->unique(table: User::class, ignoreRecord: true)
                                                    ->columnSpan(2),
                                                TextInput::make('password')
                                                    ->label('Senha')
                                                    ->password()
                                                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                                    ->required()
                                                    ->columnSpan(2),

                                            ]),
                                        Section::make('Permissões do usuário')
                                            ->schema([
                                                ToggleButtons::make('roles')
                                                    ->label('Grupos')
                                                    ->inline()
                                                    ->multiple()
                                                    ->options(function () {
                                                        return DB::table('roles')->where('slug', '<>', 'super-admin')
                                                            ->where('organization_id', $this->organization->id)
                                                            ->pluck('name', 'id');
                                                    }),
                                                ToggleButtons::make('permissions')
                                                    ->label('Permissões')
                                                    ->inline()
                                                    ->multiple()
                                                    ->options(function () {
                                                        return DB::table('permissions')->where('organization_id', $this->organization->id)
                                                            ->pluck('name', 'id');
                                                    }),


                                            ]),
                                    ])
                                    ->createOptionAction(function (FormAction $action) {
                                        return $action
                                            ->label('Adicionar Usuário')
                                            ->slideOver()
                                            ->modalWidth('md')
                                            ->action(static function (array $data) {

                                                $user = User::create([
                                                    'name' => $data['name'],
                                                    'email' => $data['email'],
                                                    'password' => $data['password'],
                                                    //'email_verified_at' => now(),
                                                ]);

                                                $user->roles()->sync($data['roles']);
                                                $user->permissions()->sync($data['permissions']);
                                            });
                                    })
                                    ->options(function () {
                                        $users = $this->organization->users()->get()->pluck('id')->toArray();

                                        return User::whereNotIn('id', $users)->get()->pluck('name', 'id');
                                    }),
                                ...$this->userVinculationForm(),
                            ])
                    ])
                    ->action(function (array $data) {

                        $user = User::findOrFail($data['user_id']);

                        $this->organization
                            ->users()
                            ->attach([
                                'user_id' => $data['user_id'],
                            ]);

                        $user->update(['last_organization_id' => $this->organization->id]);

                        $user->roles()->sync($data['roles']);
                        $user->permissions()->sync($data['permissions']);

                        Notification::make()
                            ->title('Usuário vinculado com sucesso')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    private function userVinculationForm()
    {
        return [
            Fieldset::make('Grupos')
                ->schema([
                    ToggleButtons::make('roles')
                        ->hiddenLabel()
                        ->inline()
                        ->multiple()
                        ->required()
                        ->options(function () {
                            return DB::table('roles')->where('slug', '<>', 'super-admin')
                                ->where('organization_id', $this->organization->id)
                                ->pluck('name', 'id');
                        })->columnSpan(2),
                ]),

            Fieldset::make('Permissões')
                ->schema([
                    ToggleButtons::make('permissions')
                        ->hiddenLabel()
                        ->inline()
                        ->multiple()
                        ->required()
                        ->options(function () {
                            return DB::table('permissions')
                                ->where('organization_id', $this->organization->id)
                                ->pluck('name', 'id');
                        })->columnSpan(2),
                ]),
            DatePicker::make('expires_at')
                ->hint('Deixar em branco para não expirar')
                ->label('Data Expiração')
                ->displayFormat('d/m/Y')
                ->native(false)
                ->columnSpan(2),

            Toggle::make('is_active')
                ->label('Ativo')
                ->inline(false)
                ->default(true)
                ->required()
                ->columnSpan(2),
        ];
    }
    public function render()
    {
        return view('livewire.organization.user-organization-form');
    }
}
