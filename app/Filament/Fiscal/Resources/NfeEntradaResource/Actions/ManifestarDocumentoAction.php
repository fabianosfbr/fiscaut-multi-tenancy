<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Exception;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Models\Tenant\ManifestacaoFiscal;
use App\Services\Fiscal\SefazConnectionService;
use App\Enums\Tenant\StatusManifestoNfeEnum;

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
                        '210200' => 'Confirmação da Operação',
                        '210210' => 'Ciência da Operação',
                        '210220' => 'Desconhecimento da Operação',
                        '210240' => 'Operação não Realizada',
                    ])
                    ->reactive()
                    ->required()
                    ->default('210200'),
                    
                \Filament\Forms\Components\Textarea::make('justificativa')
                    ->label('Justificativa')
                    ->helperText('Obrigatória para manifestações de desconhecimento ou operação não realizada')
                    ->required(fn (callable $get) => in_array($get('tipo_manifestacao'), ['210220', '210240']))
                    ->minLength(15)
                    ->maxLength(255)
                    ->visible(fn (callable $get) => in_array($get('tipo_manifestacao'), ['210220', '210240'])),
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
                    $resultado = $sefazService->manifestarNFe(
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
                            '210200' => StatusManifestoNfeEnum::CONFIRMADA,
                            '210210' => StatusManifestoNfeEnum::CIENCIA,
                            '210220' => StatusManifestoNfeEnum::DESCONHECIDA,
                            '210240' => StatusManifestoNfeEnum::OPERACAO_NAO_REALIZADA,
                            default => StatusManifestoNfeEnum::PENDENTE,
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
                return !$record->status_manifestacao || $record->status_manifestacao === StatusManifestoNfeEnum::PENDENTE;
            });
    }
} 