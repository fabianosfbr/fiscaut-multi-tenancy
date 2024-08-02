<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DigitalCertificate extends Model
{
    use HasFactory;
    use HasUuids;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function issuer()
    {
        return $this->belongsTo(Organization::class);
    }
}
