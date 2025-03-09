<?php

namespace App\Filament\Fiscal\Resources\FileUploadResource\Pages;

use Filament\Actions;
use App\Models\Tenant\Tag;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Fiscal\Resources\FileUploadResource;

class CreateFileUpload extends CreateRecord
{
    protected static string $resource = FileUploadResource::class;

    protected static bool $canCreateAnother = false;


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::user()->id;
        $data['doc_type'] = intval($data['doc_type']);
        $data['organization_id'] = Auth::user()->last_organization_id;
        $mimeType = Storage::disk('public')->mimeType($data['arquivo']);
        $data['extension'] = explode('/', $mimeType)[1];

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['path'] = $data['arquivo'];

        $record = static::getModel()::create($data);

        $total = 0;
        $record->untag();
        //Aplica a etiqueta
        foreach ($data['tags'] as $tag_apply) {
            $tag = Tag::find($tag_apply['tag_id'][0]);
            $record->tag($tag, $tag_apply['valor']);
            $total = $total + $tag_apply['valor'];
        }
        $record->update([
            'doc_value' => $total,
        ]);

        return $record;
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label('Salvar')
            ->submit('create')
            ->keyBindings(['mod+s']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
