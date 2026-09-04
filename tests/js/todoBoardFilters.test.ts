import assert from 'node:assert/strict';
import { test } from 'node:test';
import { todoListFiltersForProject } from '../../resources/js/lib/todoBoardFilters.ts';

test('omits group id when switching to another project', () => {
    assert.deepEqual(todoListFiltersForProject(2, { id: 10, project_id: 1 }), { project_id: 2 });
});

test('includes group id when it belongs to the current project', () => {
    assert.deepEqual(todoListFiltersForProject(2, { id: 20, project_id: 2 }), { project_id: 2, project_group_id: 20 });
});

test('omits group id when the group has no project id', () => {
    assert.deepEqual(todoListFiltersForProject(2, { id: 20 }), { project_id: 2 });
});

test('returns empty filters without a project', () => {
    assert.deepEqual(todoListFiltersForProject(null, { id: 10, project_id: 1 }), {});
});
