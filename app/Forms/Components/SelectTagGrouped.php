<?php

namespace App\Forms\Components;

use Closure;
use App\Models\Tenant\CategoryTag;
use Filament\Forms\Components\Field;

class SelectTagGrouped extends Field
{
    protected string $view = 'forms.components.select-tag-grouped';

    protected array | Closure $options = [];
    protected bool $multiple = false;

    public function organization(string $organization_id): static
    {

        $categoryTag = categoryWithTagForSearching($organization_id);

        foreach ($categoryTag as $key => $category) {
            $tags = [];
            foreach ($category->tags  as $tagKey => $tag) {
                if (!$tag->is_enable) {
                    continue;
                }
                $tags[$tagKey]['id'] = $tag->id;
                $tags[$tagKey]['name'] = $tag->code . ' - ' . $tag->name;
            }
            $this->options[$key]['text'] = $category->name;
            $this->options[$key]['children'] = $tags;
        }

        return $this;
    }


    public function options($options): static
    {
        if (is_callable($options)) {
            $val = call_user_func($options);
            $this->options = $val;
        } else {
            $this->options = $options;
        }

        return $this;
    }


    public function multiple($value): static
    {
        $this->multiple = $value;

        return $this;
    }

    public function getMultiple(): bool
    {


        return $this->multiple;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
