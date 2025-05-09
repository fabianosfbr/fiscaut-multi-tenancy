<?php

namespace App\Livewire\Organization;

use Livewire\Component;
use Filament\Tables\Table;
use App\Models\Tenant\User;
use App\Enums\Tenant\UserTypeEnum;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Organization;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Section;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\Tenant\UserPanelPermission;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\Actions\Action as FormAction;

class UserOrganizationForm extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Organization $organization;

    public function mount(): void
    {
        $this->organization = getOrganizationCached();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {

                $userInOrganization = DB::table('organization_user')
                    ->where('organization_id', $this->organization->id)
                    ->pluck('user_id')
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
                TextColumn::make('id')
                    ->label('Perfil')
                    ->getStateUsing(function (User $record) {
                        $roles = $record->roles()
                            ->wherePivot('organization_id', $this->organization->id)
                            ->get()->pluck('name')
                            ->toArray();
                        foreach ($roles as $index => $value) {
                            $role[$index] = UserTypeEnum::from($value)->getLabel();
                        }

                        return $role ?? [];
                    })
                    ->badge(),
                TextColumn::make('panels')
                    ->label('Painéis')
                    ->getStateUsing(function (User $record) {
                        return UserPanelPermission::where('user_id', $record->id)
                            ->pluck('panel')
                            ->toArray();
                    })
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

            ])
            ->filters([
                // ...
            ])
            ->actions([
                Action::make('edit-user')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Atualizar permissões')
                    ->modalWidth(MaxWidth::TwoExtraLarge)
                    ->modalSubmitActionLabel('Salvar')
                    ->fillForm(function (User $user) {

                        $organization = $user
                            ->organizations()
                            ->where('organizations.id', $this->organization->id)
                            ->first();

                        $panels = UserPanelPermission::where('user_id', $user->id)
                            ->pluck('panel')
                            ->toArray();

                        return [
                            'is_active' => $organization->pivot->is_active,
                            'roles' => $user->roles()->wherePivot('organization_id', $organization->id)->get()->pluck('name')->toArray(),
                            'panels' => $panels,
                            // 'expires_at' => $organization->pivot->expires_at,
                        ];
                    })
                    ->form($this->userVinculationForm())
                    ->action(function (User $user, array $data) {

                        $user->organizations()
                            ->updateExistingPivot($this->organization->id, ['is_active' => $data['is_active']]);

                        $user->syncRolesWithOrganization($data['roles'], $this->organization->id);

                        UserPanelPermission::syncPermissions($user, $data['panels']);

                        Cache::forget('all_valid_organizations_for_user_' . $user->id);

                        $this->redirect(request()->header('Referer'));

                        Notification::make()
                            ->title('Usuário atualizado com sucesso.')
                            ->success()
                            ->duration(3000)
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('attach')
                    ->label('Vincular')
                    ->modalWidth(MaxWidth::TwoExtraLarge)
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
                                    ->required()
                                    ->columnSpan(2)
                                    ->options(function () {
                                        $users = $this->organization->users()->get()->pluck('id')->toArray();

                                        return User::whereNotIn('id', $users)->get()->pluck('name', 'id');
                                    })
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
                                                    ->revealable()
                                                    ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                                                    ->dehydrated(fn(?string $state): bool => filled($state))
                                                    ->required()
                                                    ->columnSpan(2),
                                            ]),
                                    ])
                                    ->createOptionAction(function (FormAction $action) {
                                        return $action
                                            ->label('Adicionar Usuário')
                                            ->slideOver()
                                            ->modalWidth('md')
                                            ->action(static function (array $data) {
                                                User::create([
                                                    'name' => $data['name'],
                                                    'email' => $data['email'],
                                                    'password' => $data['password'],
                                                    'email_verified_at' => now(),
                                                ]);
                                            });
                                    }),

                                ...$this->userVinculationForm(),

                            ]),
                    ])
                    ->action(function (array $data) {

                        $user = User::find($data['user_id']);

                        $this->organization
                            ->users()
                            ->attach([
                                'user_id' => $data['user_id'],
                            ]);

                        $user->update(['last_organization_id' => $this->organization->id]);

                        $user->syncRolesWithOrganization($data['roles'], $this->organization->id);

                        Cache::forget('all_valid_organizations_for_user_' . $user->id);

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
                            $roles = UserTypeEnum::toArray();

                            return $roles;
                        })
                        ->disableOptionWhen(fn(string $value): bool => $value === 'super-admin')
                        ->columnSpan(2),

                ]),
            Fieldset::make('Painéis')
                ->schema([
                    ToggleButtons::make('panels')
                        ->hiddenLabel()
                        ->inline()
                        ->multiple()
                        ->required()
                        ->options(config('admin.panels'))
                        ->validationMessages([
                            'panels.required' => 'É necessário selecionar pelo menos um painel.',
                        ])
                        ->columnSpan(2),
                ]),

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
