<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DocumentOCR extends Model
{
    use HasUuids;

    public $table = 'documents_ocr';

    protected $keyType = 'string';

    public $incrementing = false;


    protected $guarded = ['id'];


    //
}
