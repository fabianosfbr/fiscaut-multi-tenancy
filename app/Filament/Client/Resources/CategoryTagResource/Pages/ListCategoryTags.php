<?php

namespace App\Filament\Client\Resources\CategoryTagResource\Pages;

use App\Filament\Client\Resources\CategoryTagResource;
use Filament\Actions;
use App\Models\Tenant\Tag;
use App\Models\Tenant\CategoryTag;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCategoryTags extends ListRecords
{
    protected static string $resource = CategoryTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('issuers')
                ->color('success')
                ->label('Gerar Etiqueta')
                ->hidden(CategoryTag::where('organization_id', auth()->user()->last_organization_id)->get()->count() > 0)
                ->action(function () {


                    $categoryData = config('tags.default');

                    foreach ($categoryData as $cat) {

                        $category = new CategoryTag();
                        $category->order = $cat['order'];
                        $category->name = $cat['name'];
                        $category->color = $cat['color'];
                        $category->organization_id = auth()->user()->last_organization_id;

                        $category->save();

                        foreach ($cat['tags'] as $value) {
                            $tag = new Tag();
                            $tag->name = $value['name'];
                            $tag->category_id = $category->id;
                            $tag->code = $value['code'];

                            $tag->save();
                        }
                    }

                    Notification::make()
                        ->title('Etiquetas geradas com sucesso')
                        ->success()
                        ->send();
                }),
        ];
    }
}
