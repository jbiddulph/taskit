<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Cache keys constants
     */
    const CACHE_PREFIX = 'zaptask:';
    const USER_TODOS_KEY = 'user_todos:';
    const PROJECT_TODOS_KEY = 'project_todos:';
    const USER_PROJECTS_KEY = 'user_projects:';
    const PROJECT_STATS_KEY = 'project_stats:';
    const USER_NOTIFICATIONS_KEY = 'user_notifications:';
    const USER_UNREAD_COUNT_KEY = 'user_unread_count:';

    /**
     * Cache TTL in seconds
     */
    const DEFAULT_TTL = 3600; // 1 hour
    const SHORT_TTL = 300;    // 5 minutes
    const LONG_TTL = 86400;   // 24 hours

    /**
     * Get cached data with fallback
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        $fullKey = self::CACHE_PREFIX . $key;
        
        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Cache user todos with filters
     */
    public static function cacheUserTodos(int $userId, array $filters, callable $callback)
    {
        $filterHash = md5(serialize($filters));
        $key = self::USER_TODOS_KEY . $userId . ':' . $filterHash;
        
        return self::remember($key, self::DEFAULT_TTL, $callback);
    }

    /**
     * Cache project todos
     */
    public static function cacheProjectTodos(int $projectId, callable $callback)
    {
        $key = self::PROJECT_TODOS_KEY . $projectId;
        
        return self::remember($key, self::DEFAULT_TTL, $callback);
    }

    /**
     * Cache user projects
     */
    public static function cacheUserProjects(int $userId, callable $callback)
    {
        $key = self::USER_PROJECTS_KEY . $userId;
        
        return self::remember($key, self::LONG_TTL, $callback);
    }

    /**
     * Cache project statistics
     */
    public static function cacheProjectStats(int $projectId, callable $callback)
    {
        $key = self::PROJECT_STATS_KEY . $projectId;
        
        return self::remember($key, self::SHORT_TTL, $callback);
    }

    /**
     * Cache user notifications
     */
    public static function cacheUserNotifications(int $userId, int $page, callable $callback)
    {
        $key = self::USER_NOTIFICATIONS_KEY . $userId . ':' . $page;
        
        return self::remember($key, self::DEFAULT_TTL, $callback);
    }

    /**
     * Cache user unread count
     */
    public static function cacheUserUnreadCount(int $userId, callable $callback)
    {
        $key = self::USER_UNREAD_COUNT_KEY . $userId;
        
        return self::remember($key, self::SHORT_TTL, $callback);
    }

    /**
     * Invalidate user-related caches
     */
    public static function invalidateUserCaches(int $userId)
    {
        // For database cache driver, we need to flush all caches since pattern matching isn't supported
        // This is less efficient but ensures cache consistency
        Cache::flush();

        Log::info('Invalidated user caches', ['user_id' => $userId]);
    }

    /**
     * Invalidate project-related caches
     */
    public static function invalidateProjectCaches(int $projectId)
    {
        // For database cache driver, we need to flush all caches since pattern matching isn't supported
        // This is less efficient but ensures cache consistency
        Cache::flush();

        Log::info('Invalidated project caches', ['project_id' => $projectId]);
    }

    /**
     * Invalidate all caches
     */
    public static function invalidateAllCaches()
    {
        Cache::flush();
        Log::info('Invalidated all caches');
    }

    /**
     * Delete cache by pattern (Redis only)
     */
    private static function deleteByPattern(string $pattern)
    {
        try {
            if (config('cache.default') === 'redis') {
                $keys = Redis::keys($pattern);
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            } else {
                // For other cache drivers, we can't use pattern matching
                // This is a limitation of file/database cache drivers
                Log::warning('Pattern-based cache invalidation not supported for current cache driver');
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $info = Redis::info();
                return [
                    'used_memory' => $info['used_memory_human'] ?? 'N/A',
                    'connected_clients' => $info['connected_clients'] ?? 'N/A',
                    'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A',
                    'keyspace_hits' => $info['keyspace_hits'] ?? 'N/A',
                    'keyspace_misses' => $info['keyspace_misses'] ?? 'N/A',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to get cache stats', ['error' => $e->getMessage()]);
        }

        return ['error' => 'Cache stats not available'];
    }

    /**
     * Warm up frequently accessed caches
     */
    public static function warmUpCaches(int $userId)
    {
        try {
            // Warm up user projects
            \App\Models\Project::where('owner_id', $userId)->get();
            
            // Warm up user todos count
            \App\Models\Todo::where('user_id', $userId)->count();
            
            Log::info('Cache warmed up for user', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Failed to warm up caches', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
