<?php

namespace App\Filament\Fiscal\Pages\Importar;

use App\Models\Tenant\Organization;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Services\Tenant\Sefaz\NfeService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Concerns\InteractsWithFormActions;

class NfeCte extends Page
{
    use InteractsWithFormActions;
    protected static ?string $navigationGroup = 'Ferramentas';

    protected static ?string $modelLabel = 'Importar XML';

    protected static ?string $navigationLabel = 'Importar XML';

    protected static ?string $title = '';

    protected static string $view = 'filament.fiscal.pages.importar.nfe-cte';

    public $filesToImport = [];
    public $xml_type = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Enviar arquivo para upload')
                    ->description('Os dados do xml estará disponível para todas as partes interessadas. (emitente, destinatário e transportador)')
                    ->schema([
                        ToggleButtons::make('xml_type')
                            ->label('Tipo de xml')
                            ->options([
                                'nfe' => 'NFe',
                                'cte' => 'CTe',
                            ])
                            ->required()
                            ->grouped()
                            ->inline(),
                        FileUpload::make('filesToImport')
                            ->label('')
                            ->multiple()
                            ->preserveFilenames()
                            ->required()
                            ->directory(function () {
                                $issuer = Organization::find(auth()->user()->last_organization_id);

                                return 'documentos/' . $issuer->cnpj . '/upload-xml';
                            })
                            ->uploadProgressIndicatorPosition('left')
                            ->maxFiles(25)
                            ->columnSpanFull(),
                    ])->columns(2),

            ]);
    }

    // public function import(): void
    // {

    //     $issuer = Issuer::find(getCurrentIssuer());
    //     $filesData = [];
    //     $directory = 'documentos/' . $issuer->tenant_id . '/' . $issuer->cnpj . '/upload-xml/' . Str::random(40);

    //     foreach ($this->filesToImport as $file) {

    //         //Todo codigo auxilia no debug
    //         // $xml = Storage::get($file->storeAs($directory, $file->getClientOriginalName()));
    //         // $element = loadXmlReader($xml);
    //         // dd($element->value('procEventoNFe')->get());

    //         $data = [
    //             'name' => $file->getClientOriginalName(),
    //             'extension' => explode('.', $file->getClientOriginalName())[1],
    //             'path' => $file->storeAs($directory, $file->getClientOriginalName()),
    //             'directory' => $directory,
    //         ];

    //         array_push($filesData, $data);
    //     }


    //     XmlImportJob::dispatch($filesData, $issuer)->onQueue('high');

    //     unset($this->filesToImport);

    //     $this->form->fill([]);

    //     Notification::make()
    //         ->title('Arquivos serão processados')
    //         ->success()
    //         ->send();
    // }


    public function import(): void
    {

        $issuer = Organization::find(auth()->user()->last_organization_id);

        $filesData = [];
        $directory = 'documentos/' .  $issuer->cnpj . '/upload-xml/' . Str::random(40);

        foreach ($this->filesToImport as $file) {

            //Todo codigo auxilia no debug
            // $xml = Storage::get($file->storeAs($directory, $file->getClientOriginalName()));
            // $element = loadXmlReader($xml);
            // dd($element->value('procEventoNFe')->get());

            $data = [
                'name' => $file->getClientOriginalName(),
                'extension' => explode('.', $file->getClientOriginalName())[1],
                'path' => $file->storeAs($directory, $file->getClientOriginalName()),
                'directory' => $directory,
            ];

            array_push($filesData, $data);
        }

        foreach ($filesData as $file) {


            $xml = Storage::get($file['path']);
            $xmlReader = loadXmlReader($xml);


            if ($this->xml_type == 'cte') {

                //  $service = app(CteService::class)->issuer($issuer);
            } else {

                $service = app(NfeService::class)->issuer($issuer);
            }

            $service->exec($xmlReader, $xml, 'Importação');
        }




        //  XmlImportJob::dispatch($filesData, $issuer)->onQueue('high');

        unset($this->filesToImport);

        $this->form->fill([]);

        Notification::make()
            ->title('Arquivos serão processados')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('importar')
            ->label('Importar')
            ->submit('import')
            ->keyBindings(['mod+s']);
    }
}
