<?php

return [
    'environment' => [
        'HAMBIENTE_SEFAZ' => 1,
    ],

    'sieg' => [
        'url' => env('SIEG_URL'),
        'email' => env('SIEG_EMAIL'),
        'apikey' => env('SIEG_API_KEY'),
    ],

    'doc_types' => [
        '1' => 'NFS Tomada',
        '2' => 'Fatura',
        '3' => 'Boleto',
        '4' => 'Nota Débito',
        '5' => 'Documentos contábeis',
        '6' => 'Extrato bancário',
        '7' => 'Contratos',
        '8' => 'Planilhas de controle',
    ],

    'panels' => [
        'contabil' => 'Painel Contábil',
        'fiscal' => 'Painel Fiscal',
        'ged' => 'Painel GED',
    ],
];
