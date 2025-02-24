<?php

namespace App\Jobs\Downloads;

use ZipArchive;
use App\Models\Tenant\User;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Enums\Tenant\DocTypeEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Collection;

class DownloadLoteUploadFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $failOnTimeout = false;

    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Collection $records,
        public array $data,
        public string $userId,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! \File::isDirectory(public_path('downloads/' . now()->format('m-Y')))) {
            \File::makeDirectory(public_path('downloads/' . now()->format('m-Y')), 0777, true, true);
        }

        $filename = now()->format('m-Y') . '/' . Str::random(8) . '.zip';

        $pathFile = public_path('downloads/' . $filename);

        $zip = new ZipArchive;
        $zip->open($pathFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($this->data['is_folder'] == true) {
            foreach ($this->records as $file) {
                $tipoDocumentos = DocTypeEnum::toArray();
                if (count($file->tagged) > 1) {
                    $name = $tipoDocumentos[$file->doc_type->value] . '/' . '#Multiplas Etiquetas/' . basename($file->path);
                    $file_content = Storage::disk('public')->get($file->path);
                    $zip->addFromString($name, $file_content);
                } else {
                    foreach ($file->tagNamesWithCode() as $path) {
                        $file_content = Storage::disk('public')->get($file->path);
                        $name = $tipoDocumentos[$file->doc_type->value] . '/' . $path . '/' . basename($file->path);
                        $zip->addFromString($name, $file_content);
                    }
                }
            }
        } else {
            foreach ($this->records as $file) {

                if (count($file->tagged) > 1) {
                    $name = '#Multiplas Etiquetas/' . basename($file->path);
                    $file_content = Storage::disk('public')->get($file->path);
                    $zip->addFromString($name, $file_content);
                } else {
                    foreach ($file->tagNamesWithCode() as $path) {
                        $file_content = Storage::disk('public')->get($file->path);
                        $name = $path . '/' . basename($file->path);
                        $zip->addFromString($name, $file_content);
                    }
                }

                // activity()
                //     ->causedBy(auth()->user())
                //     ->performedOn($file)
                //     ->event('Download Arquivo não fiscal ')
                //     ->log('Download Arquivo não fiscal nº: '.$file->id);
            }
        }

        $zip->close();

        Log::warning('Arquivo gerado com sucesso: ' . $pathFile);
        Log::warning(url('') . '/downloads/' . $filename);


        Notification::make()
            ->title('Arquivo disponível para download')
            ->icon('heroicon-o-arrow-down-circle')
            ->iconColor('danger')
            ->body('Seus arquivos foram processados com sucesso </br> Clique <a class="font-bold" href="' . url('') . '/downloads/' . $filename . '">aqui para baixar</a>')
            ->sendToDatabase(User::find($this->userId));
    }
}
