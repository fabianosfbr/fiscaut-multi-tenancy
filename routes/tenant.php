<?php

declare(strict_types=1);

use App\Models\Tenant\FileUpload;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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
    Route::get('/', function () {

        return redirect('/app');
    });

    Route::get('/fiscal/download-file', function () {

        $file = FileUpload::where('id', request()->input('id'))
            ->first();

        if (!$file) {
            return abort(404, 'Arquivo indisponível');
        }

        //Conteúdo do arquivo
        $file_content = Storage::disk('public')->get($file->path);

        //Tipo do arquivo
        $mimeType = Storage::disk('public')->mimeType($file->path);

        $headers = [
            'Content-Description' => 'File Transfer',
            'Content-Type' => $mimeType,
        ];

        if ($mimeType == 'application/zip') {
            $name = $file->id . '-' . $file->title . '.zip';
            $name = str_replace('/', '-', $name);
            ob_end_clean();

            return Storage::disk('public')->download($file->path, $name, $headers);
        }

        if ($mimeType == 'application/x-rar') {
            $name = $file->id . '-' . $file->title . '.rar';
            $name = str_replace('/', '-', $name);
            ob_end_clean();

            return Storage::disk('public')->download($file->path, $name, $headers);
        }

        return response($file_content)->header('Content-Type', $mimeType);
    })->name('download.file');
});
