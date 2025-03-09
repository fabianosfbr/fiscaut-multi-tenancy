<?php

namespace App\Filament\Fiscal\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Tenant\Tag;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Enums\Tenant\DocTypeEnum;
use App\Models\Tenant\FileUpload;
use App\Enums\Tenant\UserTypeEnum;
use App\Models\Tenant\CategoryTag;
use Illuminate\Support\HtmlString;
use App\Models\Tenant\Organization;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use App\Tables\Columns\TagColumnDocs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use App\Models\Tenant\ConfiguracaoGeral;
use Filament\Forms\Components\TextInput;


use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\SelectTagGrouped;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Forms\Components\DownloadDocumentFile;
use App\Jobs\Downloads\DownloadLoteUploadFile;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Fiscal\Resources\FileUploadResource\Pages;
use Filament\Forms\Components\FileUpload as FileUploadInput;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use App\Filament\Fiscal\Resources\FileUploadResource\RelationManagers;

class FileUploadResource extends Resource
{
    protected static ?string $model = FileUpload::class;

    protected static ?string $navigationGroup = 'Demais docs. fiscais';

    protected static ?string $modelLabel = 'Documento';

    protected static ?string $pluralLabel = 'Documentos';

    protected static ?string $navigationLabel = 'Documentos';

    protected static ?string $slug = 'documents';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Demais documentos fiscal')
                    ->description('Detalhes e características do documento ')
                    ->schema([
                        Placeholder::make('empresa')
                            ->label('Empresa')
                            ->content(function () {

                                $organization = getOrganizationCached();

                                return $organization->razao_social;
                            })
                            ->columnSpan('full'),
                        Select::make('doc_type')
                            ->label('Tipo de documento')
                            ->disabledOn('edit')
                            ->required()
                            ->options(DocTypeEnum::toArray())
                            ->columnSpan(1),

                        Select::make('periodo_exercicio')
                            ->label('Período de referência')
                            ->required()
                            ->options(getMesesAnterioresEPosteriores())
                            ->disabledOn('edit')
                            ->columnSpan(1),

                        TextInput::make('doc_value_create')
                            ->label('Valor Total Etiquetass')
                            ->prefix('R$')
                            ->disabled()
                            ->hiddenOn('edit')
                            ->columnSpan(1)
                            ->placeholder(function ($get, $set) {
                                $fields = $get('tags');
                                $sum = 0.0;
                                foreach ($fields as $field) {
                                    //Pega o valor do imput do repeter e incrementa
                                    // $field['valor'] = str_replace(',', '.', str_replace('.', '', $field['valor']));
                                    $sum = $sum + floatval($field['valor']);
                                }

                                $set('doc_value_total', number_format($sum, 2, ',', '.'));

                                return number_format($sum, 2, ',', '.');
                                // return $sum;
                            }),
                        Placeholder::make('etiquetas')
                            ->label('Etiquetas aplicadas')
                            ->hiddenOn('create')
                            ->content(function ($record) {
                                $tags = $record->tagged ?? [];
                                $content = '<ul class="mt-2 pl-5 list-disc">';
                                foreach ($tags as $tagged) {
                                    $content .= '<li>' . $tagged->tag->code . ' - ' . $tagged->tag_name . ' - ' . formatar_moeda($tagged->value) . '</li>';
                                }
                                $content .= '</ul>';

                                return new HtmlString($content);
                            })
                            ->columnSpan('full'),

                        Repeater::make('tags')
                            ->label('Classificação')
                            ->hiddenOn('edit')
                            ->schema([
                                SelectTagGrouped::make('tag_id')
                                    ->label('Etiqueta')
                                    ->columnSpan(1)
                                    ->multiple(true)
                                    ->options(function () {
                                        $categoryTag = CategoryTag::getAllEnabled(Auth::user()->last_organization_id);

                                        foreach ($categoryTag as $key => $category) {
                                            $tags = [];
                                            foreach ($category->tags as $tagKey => $tag) {
                                                if (! $tag->is_enable) {
                                                    continue;
                                                }
                                                $tags[$tagKey]['id'] = $tag->id;
                                                $tags[$tagKey]['name'] = $tag->code . ' - ' . $tag->name;
                                            }
                                            $tagData[$key]['text'] = $category->name;
                                            $tagData[$key]['children'] = $tags;
                                        }

                                        return $tagData ?? [];
                                    }),

                                TextInput::make('valor')
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->maxLength(13)
                                    ->columnSpan(1)
                                    ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                    ->required(),

                            ])
                            ->addActionLabel('Adicionar etiqueta')
                            ->columnSpan('full')
                            ->columns(2),
                        Textarea::make('title')
                            ->label('Descrição do documento')
                            ->required()
                            ->disabledOn('edit')
                            ->columnSpan('full'),


                        FileUploadInput::make('arquivo')
                            ->label('Arquivo')
                            ->visibility('private')
                            ->maxSize(51200)
                            ->directory(function ($get) {
                                $organization = getOrganizationCached();
                                $periodo = explode('-', $get('periodo_exercicio'));
                                return 'documentos/' . $organization->cnpj . '/docs-nao-fiscais/' . $periodo[0] . '-' . $periodo[1];
                            })

                            ->hidden(function ($record) {
                                return isset($record->created_at) ? true : false;
                            })
                            ->required()
                            ->columnSpan('full'),

                        Toggle::make('processed')
                            ->label('Apurado')
                            ->visible(function () {
                                $user = auth()->user();
                                if ($user->hasRole(UserTypeEnum::ADMIN->value, UserTypeEnum::ACCOUNTING->value)) {
                                    return true;
                                }
                                return false;
                            })
                            ->inline()
                            ->columnSpan(1),

                        DownloadDocumentFile::make('id')
                            ->hiddenLabel()
                            ->hidden(function ($record) {
                                return isset($record->created_at) ? false : true;
                            })
                            ->columnSpan(1),


                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('organization_id', Auth::user()->last_organization_id)->orderBy('created_at', 'DESC'))
            ->recordUrl(null)
            ->columns([
                TextColumn::make('title')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 40) {
                            return null;
                        }

                        // Only render the tooltip if the column contents exceeds the length limit.
                        return $state;
                    }),
                TextColumn::make('doc_value')
                    ->label('Valor')
                    ->money('BRL'),
                IconColumn::make('processed')
                    ->label('Apurado')
                    ->tooltip('Indica se a nota foi escriturada pelo departamento contábil/fiscal')
                    ->boolean()
                    ->alignment('center'),
                TextColumn::make('doc_type')
                    ->label('Tipo')
                    ->badge(),
                TagColumnDocs::make('tagged')
                    ->label('Etiqueta')
                    ->showTagCode(function () {
                        $isShow = ConfiguracaoGeral::getValue('isNfeMostrarEtiquetaComNomeAbreviado', Auth::user()->last_organization_id);
                        return $isShow;
                    })
                    ->alignCenter(),
                TextColumn::make('created_at')
                    ->label('Data Envio')
                    ->dateTime('d/m/y'),
                TextColumn::make('periodo_exercicio')
                    ->label('Exercício')
                    ->date('F - Y'),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Data Envio'),
                SelectFilter::make('processed')
                    ->label('Apurado')
                    ->options([
                        '1' => 'Sim',
                        '0' => 'Não',
                    ])
                    ->columnSpan(1),

                SelectFilter::make('doc_type')
                    ->label('Tipo')
                    ->options(DocTypeEnum::toArray()),

                SelectFilter::make('periodo_exercicio')
                    ->label('Período')
                    ->options(getMesesAnterioresEPosteriores()),

                Filter::make('qtde_etiqueta')
                    ->form([
                        Select::make('num_etiquetas')
                            ->label('Nº de etiquetas')
                            ->options([
                                'Sem etiqueta' => 'Sem etiqueta',
                                'Somente as etiquetadas' => 'Somente as etiquetadas',
                                'Apenas uma etiqueta' => 'Apenas uma etiqueta',
                                'Multiplas etiquetas' => 'Multiplas etiquetas',

                            ]),
                    ])->query(function (Builder $query, array $data) {

                        return $query->when($data['num_etiquetas'], function ($q) use ($data) {

                            if ($data['num_etiquetas'] == 'Sem etiqueta') {
                                $q->whereHas('tagged', operator: '=', count: 0);
                            } elseif ($data['num_etiquetas'] == 'Somente as etiquetadas') {
                                $q->whereHas('tagged', fn($query) => $query->whereIn('tag_id', array_keys(Tag::getTagsForFilter())));
                            } elseif ($data['num_etiquetas'] == 'Apenas uma etiqueta') {
                                $q->whereHas('tagged', operator: '=', count: 1);
                            } elseif ($data['num_etiquetas'] == 'Multiplas etiquetas') {
                                $q->whereHas('tagged', operator: '>', count: 1);
                            }
                        });
                    })->indicateUsing(function (array $data): ?string {

                        if (!$data['num_etiquetas']) {
                            return null;
                        }

                        return 'Nº de etiquetas: ' . $data['num_etiquetas'];
                    }),

                Filter::make('etiquetas')
                    ->label('Etiquetas')
                    ->form([
                        CheckboxList::make('etiquetas')
                            ->label('Etiquestas')
                            ->bulkToggleable()
                            ->searchable()
                            ->columns(2)
                            ->options(Tag::getTagsForFilter()),
                    ])->columnSpan(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['etiquetas'], function ($q) use ($data) {
                            $values = $data['etiquetas'];
                            $q->whereHas('tagged', fn($query) => $query->whereIn('tag_id', $values));
                        });
                    })
                    ->columnSpan('full'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('apurar')
                        ->label('Apurar')
                        ->icon('heroicon-o-currency-dollar')
                        ->modalSubmitActionLabel('Sim, apurar')
                        ->visible(function () {
                            $user = auth()->user();
                            if ($user->hasRole(UserTypeEnum::ADMIN->value, UserTypeEnum::ACCOUNTING->value)) {
                                return true;
                            }

                            return false;
                        })

                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $status = 0;
                                $record->update([
                                    'processed' => $status = !$record->status,
                                ]);

                                // activity()
                                //     ->causedBy(auth()->user())
                                //     ->performedOn($record)
                                //     ->event('NFe apurada')
                                //     ->log($status ? 'apurada' : 'não apurada');
                            }
                        }),
                    BulkAction::make('remover')
                        ->label('Remover')
                        ->icon('heroicon-o-trash')
                        ->modalSubmitActionLabel('Sim, remover')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if (!$record->processed) {
                                    Storage::disk('public')->delete($record->path);
                                    $record->delete();
                                }

                                // activity()
                                //     ->causedBy(auth()->user())
                                //     ->performedOn($record)
                                //     ->event('NFe removido')
                                //     ->log('removido');
                            }
                        }),
                    BulkAction::make('download-docs')
                        ->label('Download Docs')
                        ->icon('bi-filetype-pdf')
                        ->modalWidth('sm')
                        ->modalHeading('Download de documentos')
                        ->modalDescription('Selecione as opção de download que deseja.')
                        ->form([
                            Checkbox::make('is_folder')
                                ->label('Organizar por tipo de documento')
                                ->inline(),
                        ])
                        ->action(function (Collection $records, array $data) {


                            DownloadLoteUploadFile::dispatch($records, $data, auth()->user()->id);

                            Notification::make()
                                ->title('Exportação iniciada')
                                ->body('A exportação foi iniciada e as linhas selecionadas serão processadas em segundo plano')
                                ->success()
                                ->send();
                        }),
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
            'index' => Pages\ListFileUploads::route('/'),
            'create' => Pages\CreateFileUpload::route('/create'),
            'edit' => Pages\EditFileUpload::route('/{record}/edit'),
        ];
    }
}
