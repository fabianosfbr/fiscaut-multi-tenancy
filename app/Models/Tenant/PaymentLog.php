<?php

namespace App\Models\Tenant;

use App\Enums\Tenant\PaymentLogStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    use HasFactory;

    protected $table = 'payment_logs';

    protected $connection = 'central';

    protected $guarded = ['id'];

    

    
}
