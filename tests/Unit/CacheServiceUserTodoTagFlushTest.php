<?php

namespace Tests\Unit;

use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceUserTodoTagFlushTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_flush_user_todo_tags_drops_every_listing_tagged_with_the_project_or_company(): void
    {
        $this->assertTrue(
            Cache::store()->supportsTags(),
            'The array store is the Redis stand-in: cacheUserTodos() tags project:{id} and company:{id}.'
        );

        $sameProject = ['user:1', 'company:9', 'project:42'];
        $otherUserSameProject = ['user:2', 'company:9', 'project:42'];
        $sameCompanyOtherProject = ['user:1', 'company:9', 'project:99'];
        $unrelated = ['user:3', 'company:8', 'project:7'];

        Cache::tags($sameProject)->put('zaptask:user_todos:1:filter-a', ['stale-a'], 3600);
        Cache::tags($otherUserSameProject)->put('zaptask:user_todos:2:filter-b', ['stale-b'], 3600);
        Cache::tags($sameCompanyOtherProject)->put('zaptask:user_todos:1:filter-c', ['company-wide'], 3600);
        Cache::tags($unrelated)->put('zaptask:user_todos:3:filter-d', ['keep'], 3600);

        CacheService::flushUserTodoTags(42, 9);

        $this->assertNull(Cache::tags($sameProject)->get('zaptask:user_todos:1:filter-a'));
        $this->assertNull(Cache::tags($otherUserSameProject)->get('zaptask:user_todos:2:filter-b'));
        $this->assertNull(Cache::tags($sameCompanyOtherProject)->get('zaptask:user_todos:1:filter-c'));
        $this->assertSame(['keep'], Cache::tags($unrelated)->get('zaptask:user_todos:3:filter-d'));
    }

    public function test_flush_user_todo_tags_without_company_only_drops_that_project(): void
    {
        $projectTags = ['user:1', 'project:42'];
        $otherProject = ['user:1', 'project:99'];

        Cache::tags($projectTags)->put('zaptask:user_todos:1:a', ['gone'], 3600);
        Cache::tags($otherProject)->put('zaptask:user_todos:1:b', ['stay'], 3600);

        CacheService::flushUserTodoTags(42);

        $this->assertNull(Cache::tags($projectTags)->get('zaptask:user_todos:1:a'));
        $this->assertSame(['stay'], Cache::tags($otherProject)->get('zaptask:user_todos:1:b'));
    }
}
