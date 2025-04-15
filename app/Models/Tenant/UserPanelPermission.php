<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserPanelPermission extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'panel'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public static function hasAccess(User $user, string $panel): bool
    {
        $userPanels = self::getUserPanels($user);
        return in_array($panel, $userPanels);
    }

    public static function getUserPanels(User $user): array
    {
        $cacheKey = self::getCacheKey($user->id);
        
        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($user) {
            return static::where('user_id', $user->id)
                ->pluck('panel')
                ->toArray();
        });
    }

    public static function syncPermissions(User $user, array $panels): void
    {
        // Remove permissões antigas
        static::where('user_id', $user->id)
            ->delete();

        if (empty($panels)) {
            self::clearCache($user->id);
            return;
        }

        // Prepara os dados para inserção em lote
        $permissions = array_map(function ($panel) use ($user) {
            return [
                'user_id' => $user->id,
                'panel' => $panel,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $panels);

        // Insere em lote
        static::insert($permissions);

        // Limpa o cache
        self::clearCache($user->id);
    }

    protected static function boot()
    {
        parent::boot();

        // Limpa o cache quando uma permissão é alterada
        static::saved(function ($permission) {
            self::clearCache($permission->user_id);
        });

        static::deleted(function ($permission) {
            self::clearCache($permission->user_id);
        });
    }

    private static function getCacheKey(string $userId): string
    {
        return "user_panel_permissions:{$userId}";
    }

    private static function clearCache(string $userId): void
    {
        $cacheKey = self::getCacheKey($userId);
        Cache::forget($cacheKey);
        
    }
} 