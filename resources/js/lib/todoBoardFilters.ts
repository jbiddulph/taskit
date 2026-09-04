export type TodoBoardGroupRef = {
    id: number;
    project_id?: number | null;
};

export type TodoListFilters = {
    project_id?: number;
    project_group_id?: number;
};

/**
 * Filters for GET /api/todos. Never send another project's group id — that
 * would stamp ungrouped tasks onto the wrong board.
 */
export function todoListFiltersForProject(projectId: number | null | undefined, group: TodoBoardGroupRef | null | undefined): TodoListFilters {
    const filters: TodoListFilters = {};

    if (!projectId) {
        return filters;
    }

    filters.project_id = projectId;

    if (group?.id && group.project_id === projectId) {
        filters.project_group_id = group.id;
    }

    return filters;
}
