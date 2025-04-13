<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LayoutColumn extends Model
{

    protected $table = 'contabil_layout_columns';
    protected $guarded = ['id'];


    public function layout()
    {
        return $this->belongsTo(Layout::class);
    }
}
