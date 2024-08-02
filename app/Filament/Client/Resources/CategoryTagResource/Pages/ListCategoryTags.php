<?php

namespace App\Filament\Client\Resources\CategoryTagResource\Pages;

use Filament\Actions;
use App\Models\Tenant\Tag;
use Filament\Facades\Filament;
use App\Models\Tenant\CategoryTag;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Models\Tenant\CategoriaEtiquetaPadrao;
use App\Filament\Client\Resources\CategoryTagResource;

class ListCategoryTags extends ListRecords
{
    protected static string $resource = CategoryTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Criar categoria'),
            Actions\Action::make('issuers')
                ->color('success')
                ->label('Gerar Etiqueta')
                ->hidden(CategoryTag::where('organization_id', Filament::getTenant()->id)->get()->count() > 0)
                ->action(function () {

                    $this->registerTags();

                    Notification::make()
                        ->title('Etiquetas geradas com sucesso')
                        ->success()
                        ->send();
                })
        ];
    }

    private function registerTags(): void
    {
        $categoryData = CategoriaEtiquetaPadrao::with('tags')->get()->toArray();

        $organization = Filament::getTenant();
        foreach ($categoryData as $cat) {

            $category = new CategoryTag();
            $category->order = $cat['order'];
            $category->name = $cat['name'];
            $category->color = $cat['color'];
            $category->grupo = $cat['grupo'];
            $category->conta_contabil = $cat['conta_contabil'];
            $category->is_difal = $cat['is_difal'];
            $category->is_devolucao = $cat['is_devolucao'];
            $category->is_enable = $cat['is_enable'];
            $category->organization_id = $organization->id;

            $category->save();

            foreach ($cat['tags'] as $value) {
                $tag = new Tag();
                $tag->name = $value['name'];
                $tag->category_id = $category->id;
                $tag->code = $value['code'];

                $tag->save();
            }
        }
    }
}
