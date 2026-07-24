import { fetchMe, fetchProjects, getSettings, saveSettings } from './api.js';

const form = document.getElementById('options-form');
const apiBaseUrl = document.getElementById('apiBaseUrl');
const apiToken = document.getElementById('apiToken');
const defaultProjectId = document.getElementById('defaultProjectId');
const defaultPriority = document.getElementById('defaultPriority');
const statusEl = document.getElementById('status');
const testBtn = document.getElementById('test');

function setStatus(message, isError = false) {
  statusEl.textContent = message;
  statusEl.classList.toggle('error', isError);
  statusEl.classList.toggle('ok', !isError && Boolean(message));
}

async function loadProjectsIntoSelect(selectedId) {
  const projects = await fetchProjects();
  defaultProjectId.innerHTML = '';

  const blank = document.createElement('option');
  blank.value = '';
  blank.textContent = projects.length ? 'Select a project…' : 'No projects found';
  defaultProjectId.appendChild(blank);

  for (const project of projects) {
    const option = document.createElement('option');
    option.value = String(project.id);
    option.textContent = project.name;
    defaultProjectId.appendChild(option);
  }

  if (selectedId) {
    defaultProjectId.value = String(selectedId);
  }
}

async function init() {
  const settings = await getSettings();
  apiBaseUrl.value = settings.apiBaseUrl;
  apiToken.value = settings.apiToken || '';
  defaultPriority.value = settings.defaultPriority || 'Medium';

  if (settings.apiToken) {
    try {
      await loadProjectsIntoSelect(settings.defaultProjectId);
    } catch (error) {
      setStatus(error.message || 'Could not load projects.', true);
    }
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  setStatus('');

  try {
    await saveSettings({
      apiBaseUrl: apiBaseUrl.value.trim(),
      apiToken: apiToken.value.trim(),
      defaultProjectId: defaultProjectId.value ? Number(defaultProjectId.value) : null,
      defaultPriority: defaultPriority.value,
    });

    await loadProjectsIntoSelect(defaultProjectId.value || null);

    if (defaultProjectId.value) {
      await saveSettings({ defaultProjectId: Number(defaultProjectId.value) });
    }

    setStatus('Saved.');
  } catch (error) {
    setStatus(error.message || 'Could not save settings.', true);
  }
});

testBtn.addEventListener('click', async () => {
  setStatus('Testing…');
  try {
    await saveSettings({
      apiBaseUrl: apiBaseUrl.value.trim(),
      apiToken: apiToken.value.trim(),
      defaultPriority: defaultPriority.value,
    });

    const me = await fetchMe();
    await loadProjectsIntoSelect(defaultProjectId.value || null);

    if (!defaultProjectId.value && defaultProjectId.options.length > 1) {
      defaultProjectId.selectedIndex = 1;
      await saveSettings({
        defaultProjectId: Number(defaultProjectId.value),
        lastUsedProjectId: Number(defaultProjectId.value),
      });
    }

    setStatus(`Connected as ${me?.data?.name || me?.data?.email || 'user'}.`);
  } catch (error) {
    setStatus(error.message || 'Connection failed.', true);
  }
});

init();
