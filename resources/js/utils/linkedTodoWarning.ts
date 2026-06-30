export function linkedTodoWarning(count?: number): string {
  if (!count || count <= 0) {
    return '';
  }

  const label = count === 1 ? 'task' : 'tasks';

  return ` ${count} linked Kanban ${label} will also be permanently deleted.`;
}
