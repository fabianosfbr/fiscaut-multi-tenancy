<?php

namespace App\Http\Controllers;

use App\Models\Tenant\PlanoDeConta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanoDeContaSelectController extends Controller
{
    public function search(Request $request)
    {
        $params = $request->get('query');

        // Verifica se o termo de busca foi passado
        if (!$params) {
            return response()->json(['message' => 'Por favor, informe um termo de busca.'], 400);
        }

        $results = [];

        if (is_numeric($params)) {
            // Consulta o código do plano de conta
            $results = PlanoDeConta::where('organization_id', getOrganizationCached()->id)->where('codigo', $params)->limit(50)
                ->get(['id', 'codigo', 'nome']);
        } else {
                // Consulta o nome do plano de conta usando LIKE
            $results = PlanoDeConta::where('organization_id', getOrganizationCached()->id)->where('nome', 'LIKE', "%$params%")->limit(50)
                ->get(['id', 'codigo', 'nome']);
        }

        return response()->json($results);
    }
}
