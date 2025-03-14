<?php

namespace App\Models\Tenant\Concerns;

use App\Models\Tenant\Tag;
use App\Models\Tenant\Tagged;

trait HasTags
{
    public function tagged()
    {
        return $this->morphMany(Tagged::class, 'taggable')->with('tag');
    }

    public function untag()
    {
        $tags = $this->getTagsAttribute();
        foreach ($tags as $tag) {
            $this->tagged()->where('tag_id', $tag->id)->delete();
        }
    }

    public function tag($tag_id, $value, $product = null)
    {
        $tag = Tag::find($tag_id);

        if (! $tag) {
            throw new \Exception('Tag não encontrada');
        }

        $tagged = new Tagged([
            'tag_id' => $tag->id,
            'tag_name' => $tag->name,
            'value' => $value,
            'product' => $product,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->tagged()->save($tagged);
    }

    public function getTagsAttribute()
    {
        return $this->tagged->map(function (Tagged $item) {
            return $item->tag;
        });
    }

    public function getTagNamesAttribute(): array
    {
        return $this->tagNames();
    }

    public function tagNames(): array
    {
        return $this->tagged->map(function ($item) {
            return $item->tag_name;
        })->toArray();
    }

    public function tagNamesWithCode(): array
    {
        return $this->tagged->map(function ($item) {
            return $item->tag->code.' - '.$item->tag_name;
        })->toArray();
    }

    public function tagNamesWithCodeAndValue(): array
    {
        return $this->tagged->map(function ($item) {
            return $item->tag->code.' - '.$item->tag_name.' | R$'. number_format($item->value, 2, ',', '.');
        })->toArray();
    }

    public function tagAtrributes(): array
    {
        return $this->tagged->map(function ($item) {
            return $item->tag->code.' - '.$item->tag_name.' | '.$item->value.' | '.$item->products;
        })->toArray();
    }
}
