<?php

declare(strict_types=1);

use App\Models\Tenant\FileUpload;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\PlanoDeContaSelectController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/fiscal/remote-select/search', [PlanoDeContaSelectController::class, 'search'])
        ->name('fiscal.remote-select.search');

    Route::get('/', function () {

        return redirect('/fiscal');
    });

    Route::get('/fiscal/download-file/avancado-nfe/{filename}', [DownloadController::class, 'downloadAvancadoNfe'])
        ->name('download.avancado.nfe');

    Route::get('/fiscal/download-file/{document}', function (App\Models\Tenant\FileUpload $document) {

        if (!$document->path) {
            return abort(404, 'Documento não encontrado');
        }
        
        $filePath = storage_path('app/public/' . $document->path);
   
        if (!file_exists($filePath)) {
            return abort(404, 'Arquivo não encontrado');
        }

        $mimeType = mime_content_type($filePath);
  

        $headers = [
            'Content-Description' => 'File Transfer',
            'Content-Type' => $mimeType,
        ];

        if ($mimeType == 'application/zip') {
            $name = $document->id . '-' . $document->title . '.zip';
            $name = str_replace('/', '-', $name);
            ob_end_clean();

            return Storage::disk('public')->download($document->path, $name, $headers);
        }

        if ($mimeType == 'application/x-rar') {
            $name = $document->id . '-' . $document->title . '.rar';
            $name = str_replace('/', '-', $name);
            ob_end_clean();

            return Storage::disk('public')->download($document->path, $name, $headers);
        }

        return response($file_content)->header('Content-Type', $mimeType);
    })->name('download.file');

    Route::get('/ged/document-ocr/{document}', function (App\Models\Tenant\DocumentOCR $document) {

        if (!$document->file) {
            return abort(404, 'Documento não encontrado');
        }
        
        $filePath = storage_path('app/public/' . $document->file);
   
        if (!file_exists($filePath)) {
            return abort(404, 'Arquivo não encontrado');
        }

        $mimeType = mime_content_type($filePath);

    
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline'
        ]);
    })->name('document-ocr.view');
});
