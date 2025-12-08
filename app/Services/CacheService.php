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
        
        // Use cache tags if Redis is available for better invalidation
        if (config('cache.default') === 'redis' && method_exists(Cache::store(), 'tags')) {
            $companyId = $filters['company_id'] ?? null;
            $tags = ['user:' . $userId];
            if ($companyId) {
                $tags[] = 'company:' . $companyId;
            }
            if (isset($filters['project_id'])) {
                $tags[] = 'project:' . $filters['project_id'];
            }
            
            return Cache::tags($tags)->remember(
                self::CACHE_PREFIX . $key,
                self::DEFAULT_TTL,
                $callback
            );
        }
        
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
    public static function invalidateUserCaches(int $userId, ?int $companyId = null)
    {
        try {
            if (config('cache.default') === 'redis') {
                // Use Redis pattern matching for efficient cache invalidation
                $patterns = [
                    self::CACHE_PREFIX . self::USER_TODOS_KEY . $userId . '*',
                    self::CACHE_PREFIX . self::USER_PROJECTS_KEY . $userId,
                    self::CACHE_PREFIX . self::USER_NOTIFICATIONS_KEY . $userId . '*',
                    self::CACHE_PREFIX . self::USER_UNREAD_COUNT_KEY . $userId,
                ];
                
                // Also invalidate company-wide caches if company_id is provided
                if ($companyId) {
                    $patterns[] = self::CACHE_PREFIX . self::PROJECT_TODOS_KEY . '*';
                    $patterns[] = self::CACHE_PREFIX . self::PROJECT_STATS_KEY . '*';
                }
                
                foreach ($patterns as $pattern) {
                    self::deleteByPattern($pattern);
                }
                
                Log::info('Invalidated user caches (Redis)', ['user_id' => $userId, 'company_id' => $companyId]);
            } else {
                // For non-Redis drivers, use cache tags if supported
                if (method_exists(Cache::store(), 'tags')) {
                    Cache::tags(['user:' . $userId])->flush();
                    if ($companyId) {
                        Cache::tags(['company:' . $companyId])->flush();
                    }
                    Log::info('Invalidated user caches (tags)', ['user_id' => $userId, 'company_id' => $companyId]);
                } else {
                    // Last resort: flush all (less efficient but ensures consistency)
                    Cache::flush();
                    Log::warning('Invalidated all caches (no Redis/tags support)', ['user_id' => $userId]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate user caches', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            // Fallback to flush all on error
            Cache::flush();
        }
    }

    /**
     * Invalidate project-related caches
     */
    public static function invalidateProjectCaches(int $projectId, ?int $companyId = null)
    {
        try {
            if (config('cache.default') === 'redis') {
                // Use Redis pattern matching
                $patterns = [
                    self::CACHE_PREFIX . self::PROJECT_TODOS_KEY . $projectId,
                    self::CACHE_PREFIX . self::PROJECT_STATS_KEY . $projectId,
                    // Also invalidate user todo caches that might include this project
                    self::CACHE_PREFIX . self::USER_TODOS_KEY . '*',
                ];
                
                foreach ($patterns as $pattern) {
                    self::deleteByPattern($pattern);
                }
                
                Log::info('Invalidated project caches (Redis)', ['project_id' => $projectId, 'company_id' => $companyId]);
            } else {
                // For non-Redis drivers, use cache tags if supported
                if (method_exists(Cache::store(), 'tags')) {
                    Cache::tags(['project:' . $projectId])->flush();
                    if ($companyId) {
                        Cache::tags(['company:' . $companyId])->flush();
                    }
                    Log::info('Invalidated project caches (tags)', ['project_id' => $projectId, 'company_id' => $companyId]);
                } else {
                    // Last resort: flush all
                    Cache::flush();
                    Log::warning('Invalidated all caches (no Redis/tags support)', ['project_id' => $projectId]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate project caches', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            // Fallback to flush all on error
            Cache::flush();
        }
    }

    /**
     * Invalidate notification caches for a user
     */
    public static function invalidateNotificationCaches(int $userId)
    {
        try {
            if (config('cache.default') === 'redis') {
                $patterns = [
                    self::CACHE_PREFIX . self::USER_NOTIFICATIONS_KEY . $userId . '*',
                    self::CACHE_PREFIX . self::USER_UNREAD_COUNT_KEY . $userId,
                ];
                
                foreach ($patterns as $pattern) {
                    self::deleteByPattern($pattern);
                }
                
                Log::info('Invalidated notification caches (Redis)', ['user_id' => $userId]);
            } else {
                if (method_exists(Cache::store(), 'tags')) {
                    Cache::tags(['notifications:' . $userId])->flush();
                } else {
                    Cache::flush();
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate notification caches', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
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
