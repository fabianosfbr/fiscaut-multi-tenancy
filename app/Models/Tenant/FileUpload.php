<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\DocTypeEnum;
use App\Models\Tenant\Organization;
use App\Models\Tenant\Concerns\HasTags;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class FileUpload extends Model
{
    use HasTags, HasUuids;

    protected $guarded = ['id'];

    protected $keyType = 'string';

    public $incrementing = false;


    protected $casts = [
        'doc_type' => DocTypeEnum::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
