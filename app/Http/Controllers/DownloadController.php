<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class DownloadController extends Controller
{
    /**
     * Faz o download do arquivo ZIP gerado pelo download avançado
     */
    public function downloadAvancadoNfe(Request $request)
    {
        if (!$request->hasValidSignature()) {
            Log::error('Tentativa de download com assinatura inválida');
            abort(401);
        }

        $filename = $request->filename;
        $mesAno = $request->mes_ano;
        $path = 'downloads/' . $mesAno . '/' . $filename;


        if (!Storage::disk('public')->exists($path)) {
            Log::error('Arquivo não encontrado', ['path' => $path]);
            abort(404);
        }

        return Storage::disk('public')->download($path, $filename);
    }
} 