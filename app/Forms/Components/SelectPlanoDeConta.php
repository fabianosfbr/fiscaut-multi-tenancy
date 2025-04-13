<?php

namespace App\Forms\Components;

use Illuminate\Support\Facades\URL;
use Filament\Forms\Components\Field;

class SelectPlanoDeConta extends Field
{
    protected string $view = 'forms.components.select-plano-de-conta';

    protected string $apiEndpoint = '';

    public function apiEndpoint(string $endpoint): static
    {
        $this->apiEndpoint = $endpoint;
        return $this;   
    }

    public function getApiEndpoint(): string
    {
    
        return $this->apiEndpoint;
    }
}
