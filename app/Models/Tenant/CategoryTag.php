<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use App\Observers\Tenant\CategoryTagObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;


#[ObservedBy([CategoryTagObserver::class])]
class CategoryTag extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id'];


    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class, 'category_id');
    }
}
