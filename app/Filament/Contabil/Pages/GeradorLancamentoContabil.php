<?php

namespace App\Filament\Contabil\Pages;

use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\FileUpload;
use avadim\FastExcelLaravel\Facades\Excel as FastExcel;
use App\Imports\PlanilhaLancamentosContabeis;
use EightyNine\ExcelImport\ExcelImportAction;

use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\Actions\ImportField;


class GeradorLancamentoContabil extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.contabil.pages.gerador-lancamento-contabil';

    protected static bool $shouldRegisterNavigation = false;

    public array $data;

    protected function getHeaderActions(): array
    {
        return [
            ExcelImportAction::make()
                ->modalHeading('Importar arquivo de plano de contas')
                ->modalWidth('lg')
                ->modalDescription(new HtmlString("O arquivo excel deve conter os seguintes campos: <br> codi_cta , nome_cta, clas_cta, tipo_cta. <br> <br> Clique <a href='/downloads/mock/modelo-plano-de-contas.xlsx' download>aqui</a> para baixar arquivo de exemplo"))
                ->modalSubmitActionLabel('Sim, importar arquivo')
                ->label("Importar")
                ->color("primary")
                ->use(PlanilhaLancamentosContabeis::class)
                ->uploadField(
                    fn($upload) => $upload
                        ->label("Anexe o documento para importação")
                        ->validationMessages([
                            'required' => 'O arquivo é obrigatório',
                        ])
                ),

            ImportAction::make()
                ->slideOver()
                ->modalWidth('sm')
                ->fields([
                    ImportField::make('data')
                        ->label('Data'),
                    ImportField::make('lancamento')
                        ->label('Lançamento'),
                    ImportField::make('valor')
                        ->label('Valor (R$)'),
                    ImportField::make('saldo')
                        ->label('Saldo (R$)'),
                    ImportField::make('observacao')
                        ->label('Observação'),
                ], 1)
                ->handleRecordCreation(function ($data) {

                    dd($data);
                    return GeradorLancamentoContabil::create($data);
                })


        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('attachment')
                    ->label('Arquivo')
                    ->disk('public')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                    ->directory('imports')

            ])
            ->statePath('data');
    }

    public function create(): void
    {

        $data = $this->form->getState();


        $filePath = storage_path('app/public/' . $data['attachment']);







        dd(storage_path('app/public/' . $data['attachment']));
    }

    public function returnAction(): Action
    {
        return Action::make('return')
            ->label('Cancelar')
            ->color('warning'); // @phpstan-ignore-line
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Salvar')
            ->action(fn() => $this->create());
    }
}
