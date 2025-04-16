<?php

namespace App\Filament\Clusters\Profile\Pages;

use Filament\Forms\Form;
use App\Models\Tenant\User;
use Filament\Actions\Action;
use App\Filament\Clusters\Profile;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Concerns\InteractsWithFormActions;

class SiegConfiguration extends BaseProfile
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Configuração Sieg';

    protected static ?string $slug = 'me/sieg-configuration';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.clusters.profile.pages.sieg-configuration';

    protected static ?string $cluster = Profile::class;

    public ?array $data = [];

    public function mount(): void
    {
        $data = User::where('owner', true)->select('id', 'sieg_api_key')->first()?->attributesToArray();
     
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações de integração com o Sieg')
                    ->description('Forneça as credenciais de integração de API do Sieg')
                    ->schema([
                        TextInput::make('sieg_api_key')
                            ->label('Chave de acesso')
                            ->required()
                            ->password()
                            ->revealable()
                            ->maxLength(255),
                    ]),

            ])
            ->model($this->getUser())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = User::where('owner', true)->first();

        $user->sieg_api_key = $data['sieg_api_key'];

        $user->saveQuietly();

        $this->getSavedNotification('Informações atualizadas com sucesso')->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Salvar')
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('back')
            ->label('Página Inicial')
            ->url('/fiscal');
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }

    public static function canAccess(): bool
    {        
        return auth()->user()->hasRole(['admin', 'super-admin']);
    }

 
}
