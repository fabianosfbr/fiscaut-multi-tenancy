<?php

namespace App\Filament\Clusters\Profile\Pages;

use App\Filament\Clusters\Profile;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Support\Enums\Alignment;

class EditProfile extends BaseProfile
{
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'Editar Perfil';

    protected static ?string $slug = 'me/edit-profile';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.clusters.profile.pages.edit-profile';

    protected static ?string $cluster = Profile::class;

    public function mount(): void
    {
        $data = $this->getUser()->attributesToArray();

        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações pessoais')
                    ->description('Editar informações do perfil')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                    ]),

            ])
            ->model($this->getUser())
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $this->handleRecordUpdate($this->getUser(), $data);

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
            ->url('/app');
    }

    public function getFormActionsAlignment(): string|Alignment
    {
        return Alignment::End;
    }
}
