<?php

namespace App\Services\Configuracoes;

use Illuminate\Support\Facades\Auth;

class ConfiguracaoFactory
{
    /**
     * Cria uma instância do serviço de configuração para a organização informada
     */
    public static function criar(string $organizationId): ConfiguracaoService
    {
        return new ConfiguracaoService($organizationId);
    }
    
    /**
     * Cria uma instância do serviço de configuração para a organização atual
     */
    public static function atual(): ConfiguracaoService
    {
        $organizationId = Auth::user()?->last_organization_id ?? '';
        
        if (empty($organizationId)) {
            throw new \Exception('Usuário não está vinculado a uma organização');
        }
        
        return self::criar($organizationId);
    }
} 