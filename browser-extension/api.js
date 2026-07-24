const DEFAULTS = {
  apiBaseUrl: 'https://zaptask.co.uk/api',
  apiToken: '',
  defaultProjectId: null,
  defaultPriority: 'Medium',
};

export async function getSettings() {
  const stored = await chrome.storage.sync.get(DEFAULTS);
  return {
    ...DEFAULTS,
    ...stored,
    apiBaseUrl: String(stored.apiBaseUrl || DEFAULTS.apiBaseUrl).replace(/\/$/, ''),
  };
}

export async function saveSettings(partial) {
  const current = await getSettings();
  const next = {
    ...current,
    ...partial,
    apiBaseUrl: String(partial.apiBaseUrl ?? current.apiBaseUrl).replace(/\/$/, ''),
  };
  await chrome.storage.sync.set(next);
  return next;
}

async function apiRequest(path, { method = 'GET', body } = {}) {
  const { apiBaseUrl, apiToken } = await getSettings();

  if (!apiToken) {
    throw new Error('Add your API token in extension options first.');
  }

  const response = await fetch(`${apiBaseUrl}${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${apiToken}`,
    },
    body: body ? JSON.stringify(body) : undefined,
  });

  let payload = null;
  try {
    payload = await response.json();
  } catch {
    payload = null;
  }

  if (!response.ok) {
    const message =
      payload?.message ||
      (payload?.errors ? Object.values(payload.errors).flat().join(' ') : null) ||
      `Request failed (${response.status})`;
    throw new Error(message);
  }

  return payload;
}

export async function fetchMe() {
  return apiRequest('/extension/me');
}

export async function fetchProjects() {
  const payload = await apiRequest('/extension/projects');
  return payload?.data || [];
}

export async function createTodo({ title, projectId, priority, description, status = 'todo', type = 'Task' }) {
  return apiRequest('/extension/todos', {
    method: 'POST',
    body: {
      title,
      project_id: Number(projectId),
      priority: priority || 'Medium',
      status,
      type,
      description: description || null,
    },
  });
}
