<?php

use App\Enums\Tenant\UserTypeEnum;

return [
    'roles' => array_keys(UserTypeEnum::toArray()),

    'permissions' => [
        '0' => 'manifestar-nota',
        '1' => 'classificar-nota',
        '2' => 'marcar-documento-apurado',
    ],

];
