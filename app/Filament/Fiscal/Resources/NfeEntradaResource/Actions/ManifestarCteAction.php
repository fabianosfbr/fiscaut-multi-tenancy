<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Exception;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\Tenant\ManifestacaoFiscal;
use App\Enums\Tenant\StatusManifestoCteEnum;
use App\Enums\Tenant\StatusManifestoNfeEnum;
use App\Services\Fiscal\SefazConnectionService;

class ManifestarDocumentoAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'manifestar-documento';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Manifestar')
            ->icon('heroicon-o-document-check')
            ->requiresConfirmation()
            ->modalHeading('Manifestar Documento Fiscal')
            ->modalWidth('lg')
            ->modalDescription('Selecione o tipo de manifestação que deseja realizar.')
            ->form([
                \Filament\Forms\Components\Select::make('tipo_manifestacao')
                    ->label('Tipo de Manifestação')
                    ->options([
                        '610110' => 'Prestação de serviço em desacordo',
                    ])
                    ->reactive()
                    ->required()
                    ->default('610110'),

                \Filament\Forms\Components\Textarea::make('justificativa')
                    ->label('Justificativa')
                    ->helperText('Obrigatória para manifestações de desconhecimento ou operação não realizada')
                    ->required()
                    ->minLength(15)
                    ->maxLength(255),
            ])
            ->action(function (array $data) {
                $record = $this->getRecord();
                $organization = getOrganizationCached();

                try {
                    DB::beginTransaction();

                    // Cria o registro da manifestação
                    $manifestacao = ManifestacaoFiscal::create([
                        'organization_id' => $organization->id,
                        'documento_type' => get_class($record),
                        'documento_id' => $record->id,
                        'chave_acesso' => $record->chave_acesso,
                        'tipo_documento' => $record instanceof \App\Models\Tenant\NotaFiscalEletronica ? 'NFe' : 'CTe',
                        'tipo_manifestacao' => $data['tipo_manifestacao'],
                        'status' => 'pendente',
                        'justificativa' => $data['justificativa'] ?? null,
                        'data_manifestacao' => now(),
                        'created_by' => Auth::id(),
                    ]);

                    // Chama o serviço da SEFAZ
                    $sefazService = new SefazConnectionService($organization);
                    $resultado = $sefazService->manifestarCTe(
                        $record->chave_acesso,
                        $data['tipo_manifestacao'],
                        $data['justificativa'] ?? null
                    );

                    // Atualiza o registro com o resultado
                    $manifestacao->update([
                        'status' => $resultado['success'] ? 'sucesso' : 'erro',
                        'protocolo' => $resultado['success'] ? $resultado['std']->retEvento->infEvento->nProt : null,
                        'data_resposta' => now(),
                        'erro' => $resultado['success'] ? null : ($resultado['message'] ?? 'Erro desconhecido'),
                        'xml_resposta' => $resultado['success'] ? $resultado['response'] : null,
                    ]);

                    // Atualiza o status da manifestação no documento
                    if ($resultado['success']) {
                        $statusManifestacao = match ($data['tipo_manifestacao']) {
                            '210200' => StatusManifestoCteEnum::CONFIRMADA,
                            '210210' => StatusManifestoCteEnum::CIENCIA,
                            '210220' => StatusManifestoCteEnum::DESCONHECIDA,
                            '210240' => StatusManifestoCteEnum::OPERACAO_NAO_REALIZADA,
                            '610110' => StatusManifestoCteEnum::PRESTACAO_DE_SERVICO_EM_DESACORDO,
                            default => StatusManifestoCteEnum::PENDENTE,
                        };

                        $record->update([
                            'status_manifestacao' => $statusManifestacao,
                        ]);
                    }

                    DB::commit();

                    Notification::make()
                        ->title($resultado['success'] ? 'Manifestação realizada com sucesso' : 'Erro na manifestação')
                        ->body($resultado['success'] ? 'Manifestação registrada com sucesso.' : ($resultado['message'] ?? 'Erro desconhecido'))
                        ->status($resultado['success'] ? 'success' : 'danger')
                        ->send();
                } catch (Exception $e) {
                    DB::rollBack();

                    Notification::make()
                        ->title('Erro ao manifestar documento')
                        ->body('Ocorreu um erro ao tentar manifestar o documento. Tente novamente.')
                        ->danger()
                        ->send();
                }
            })
            ->visible(function ($record) {
                
                // Só mostra a action se o documento não estiver manifestado
                return $record->status_manifestacao === StatusManifestoCteEnum::PENDENTE;
            });
    }
}
