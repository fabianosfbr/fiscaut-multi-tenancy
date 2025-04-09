<?php

namespace App\Filament\Fiscal\Resources\NfeEntradaResource\Actions;

use Illuminate\Support\Str;
use function Psl\Dict\intersect;
use Illuminate\Contracts\View\View;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Tenant\OrganizacaoConfiguracao;
use App\Services\Tenant\Integracoes\Dominio\Nfe\LeiautePadrao;

class DownloadArquivoTxtDominio extends BulkAction
{

    private bool $enableDownload = false;

    public static function getDefaultName(): ?string
    {
        return 'download_txt_dominio';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Download TXT Dominio')
            ->icon('heroicon-o-cog-6-tooth')
            ->requiresConfirmation()
            ->modalHeading('Download TXT Dominio')
            ->modalSubmitActionLabel('Iniciar Download')
            ->successNotificationTitle('Download iniciado com sucesso')
            ->modalWidth(MaxWidth::ThreeExtraLarge)
            ->action(function (Collection $records, array $data) {

                $conteudo_txt = LeiautePadrao::generate($records, getOrganizationCached());

                $txtContentAnsi = mb_convert_encoding($conteudo_txt, 'Windows-1252', 'UTF-8');

                return response()->streamDownload(function () use ($txtContentAnsi) {
                    echo $txtContentAnsi;
                }, Str::random(10) . '.txt');


                Notification::make()
                    ->title('Download iniciado')
                    ->body('O download das notas foi iniciado e será processado em segundo plano.')
                    ->success()
                    ->send();
            });

        // Registra a modal de relatório
        $this->modalContent(function (Collection $records) {
            return view('filament.fiscal.actions.etiquetas-nao-configuradas', [
                'notas' => $this->verificaNotas($records)
            ]);
        });
    }

    /**
     * Verifica se as notas possuem etiquetas que não estão configuradas
     */
    private function verificaNotas(Collection $records): array
    {
        // Obtém a organização atual
        $organization = getOrganizationCached();
        
        // Carrega as configurações de etiquetas
        $etiquetasConfiguradas = $this->carregarEtiquetasConfiguradas($organization->id);
        
        // Array para armazenar notas com etiquetas não configuradas
        $notasComEtiquetasNaoConfiguradas = [];
        
        // Verifica cada nota fiscal
        foreach ($records as $nota) {
            $etiquetasFaltantes = $this->verificarEtiquetasFaltantes($nota, $etiquetasConfiguradas);
            
            if (!empty($etiquetasFaltantes)) {
                $notasComEtiquetasNaoConfiguradas[] = [
                    'numero' => $nota->numero,
                    'nome_emitente' => Str::limit($nota->nome_emitente, 45),
                    'etiquetas' => $etiquetasFaltantes
                ];
            }
        }
        
        $this->enableDownload = count($notasComEtiquetasNaoConfiguradas) > 0;
        
        return $notasComEtiquetasNaoConfiguradas;
    }

    /**
     * Carrega todas as etiquetas configuradas no sistema
     */
    private function carregarEtiquetasConfiguradas(string $organizationId): array
    {
        // Carrega configurações de acumuladores
        $acumuladores = $this->carregarConfiguracoesAcumuladores($organizationId);
        
        // Carrega configurações de CFOPs
        $cfops = $this->carregarConfiguracoesCfops($organizationId);
        
        return [
            'acumuladores' => $acumuladores,
            'cfops' => $cfops
        ];
    }

    /**
     * Carrega as configurações de acumuladores
     */
    private function carregarConfiguracoesAcumuladores(string $organizationId): array
    {
        // Obtém as configurações
        $acumuladores_nfe_terceiro = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $organizationId,
            tipo: 'entrada',
            subtipo: 'acumuladores',
            categoria: 'nfe_terceiro',
        );

        $acumuladores_nfe_propria = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $organizationId,
            tipo: 'entrada',
            subtipo: 'acumuladores',
            categoria: 'nfe_propria',
        );
        
        // Extrai os IDs das etiquetas
        $etiquetasAcumuladoresNfeTerceiro = collect($acumuladores_nfe_terceiro['itens'] ?? [])->pluck('tag_id')->toArray();
        $etiquetasAcumuladoresNfePropria = collect($acumuladores_nfe_propria['itens'] ?? [])->pluck('tag_id')->toArray();
        
        // Combina e remove duplicatas
        return array_unique(
            array_merge(
                collect($etiquetasAcumuladoresNfeTerceiro)->flatten()->all(), 
                collect($etiquetasAcumuladoresNfePropria)->flatten()->all()
            )
        );
    }

    /**
     * Carrega as configurações de CFOPs
     */
    private function carregarConfiguracoesCfops(string $organizationId): array
    {
        // Obtém as configurações
        $cfops_nfe_terceiro = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $organizationId,
            tipo: 'entrada',
            subtipo: 'cfops',
            categoria: 'nfe_terceiro',
        );

        $cfops_nfe_propria = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $organizationId,
            tipo: 'entrada',
            subtipo: 'cfops',
            categoria: 'nfe_propria',
        );

        $cfops_cte_entrada = OrganizacaoConfiguracao::obterConfiguracao(
            organizationId: $organizationId,
            tipo: 'entrada',
            subtipo: 'cfops',
            categoria: 'cte_entrada',
        );

        // Extrai os IDs das etiquetas
        $etiquetasCfopsNfeTerceiro = collect($cfops_nfe_terceiro['itens'] ?? [])->pluck('tag_id')->toArray();
        $etiquetasCfopsNfePropria = collect($cfops_nfe_propria['itens'] ?? [])->pluck('tag_id')->toArray();
        $etiquetasCfopsCteEntrada = collect($cfops_cte_entrada['itens'] ?? [])->pluck('tag_id')->toArray();
        
        // Combina e remove duplicatas
        return array_unique(array_merge(
            collect($etiquetasCfopsNfeTerceiro)->flatten()->all(), 
            collect($etiquetasCfopsNfePropria)->flatten()->all(), 
            collect($etiquetasCfopsCteEntrada)->flatten()->all()
        ));
    }

    /**
     * Verifica quais etiquetas estão faltando nas configurações
     */
    private function verificarEtiquetasFaltantes($nota, array $etiquetasConfiguradas): array
    {
        $etiquetasNota = $nota->tagged->pluck('tag_id')->toArray();
        
        // Se não houver etiquetas, retorna vazio
        if (empty($etiquetasNota)) {
            return [];
        }
        
        $etiquetasFaltantes = [];
        
        // Mapeia as etiquetas com seus nomes e IDs
        $etiquetasMapeadas = $this->mapearEtiquetas($nota, $etiquetasNota);
        
        // Verifica cada etiqueta
        foreach ($etiquetasMapeadas as $etiquetaId => $etiqueta) {
            $faltaAcumulador = !in_array($etiquetaId, $etiquetasConfiguradas['acumuladores']);
            $faltaCfop = !in_array($etiquetaId, $etiquetasConfiguradas['cfops']);
            
            if ($faltaAcumulador || $faltaCfop) {
                $etiquetasFaltantes[] = [
                    'nome' => $etiqueta['nome'],
                    'falta_acumulador' => $faltaAcumulador,
                    'falta_cfop' => $faltaCfop
                ];
            }
        }
        
        return $etiquetasFaltantes;
    }

    /**
     * Mapeia as etiquetas de uma nota para um formato estruturado
     */
    private function mapearEtiquetas($nota, array $etiquetasIds): array
    {
        $mapeadas = [];
        
        foreach ($etiquetasIds as $etiquetaId) {
            $tagged = $nota->tagged->firstWhere('tag_id', $etiquetaId);
            $nomeEtiqueta = $tagged['tag']['code'] . '-' . $tagged['tag']['name'];
            
            $mapeadas[$etiquetaId] = [
                'id' => $etiquetaId,
                'nome' => $nomeEtiqueta
            ];
        }
        
        return $mapeadas;
    }
}
