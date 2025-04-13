<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Layout extends Model
{
    protected $table = 'contabil_layouts';

    protected $with = ['layoutColumns', 'layoutRules'];

    protected $guarded = ['id'];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function layoutColumns()
    {
        return $this->hasMany(LayoutColumn::class);
    }
    public function layoutRules()
    {
        return $this->hasMany(LayoutRule::class);
    }

    public function duplicateWithRelationships()
    {
        // Duplica o Layout
        $newLayout = $this->replicate();
        $newLayout->save();

        // Duplica LayoutRules
        $this->layoutRules->each(function ($rule) use ($newLayout) {
            $newRule = $rule->replicate();
            $newRule->layout_id = $newLayout->id;
            $newRule->save();
        });

        // Duplica LayoutColumns
        $this->layoutColumns->each(function ($column) use ($newLayout) {
            $newColumn = $column->replicate();
            $newColumn->layout_id = $newLayout->id;
            $newColumn->save();
        });

        return $newLayout;
    }
}
