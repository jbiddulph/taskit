import { createTodo, fetchProjects, getSettings } from './api.js';

const form = document.getElementById('task-form');
const titleInput = document.getElementById('title');
const projectSelect = document.getElementById('project');
const prioritySelect = document.getElementById('priority');
const includePage = document.getElementById('include-page');
const statusEl = document.getElementById('status');
const submitBtn = document.getElementById('submit');

function setStatus(message, isError = false) {
  statusEl.textContent = message;
  statusEl.classList.toggle('error', isError);
  statusEl.classList.toggle('ok', !isError && Boolean(message));
}

async function getActiveTab() {
  const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
  return tabs[0] || null;
}

async function init() {
  try {
    const settings = await getSettings();

    if (!settings.apiToken) {
      setStatus('Add your API token in Options first.', true);
      submitBtn.disabled = true;
      return;
    }

    prioritySelect.value = settings.defaultPriority || 'Medium';

    const projects = await fetchProjects();
    projectSelect.innerHTML = '';

    if (!projects.length) {
      setStatus('No projects found. Create one in ZapTask first.', true);
      submitBtn.disabled = true;
      return;
    }

    for (const project of projects) {
      const option = document.createElement('option');
      option.value = String(project.id);
      option.textContent = project.name;
      projectSelect.appendChild(option);
    }

    if (settings.defaultProjectId) {
      projectSelect.value = String(settings.defaultProjectId);
    }

    const tab = await getActiveTab();
    if (tab?.title && !/^chrome:|^chrome-extension:|^about:/.test(tab.url || '')) {
      titleInput.value = tab.title.slice(0, 255);
    }

    titleInput.focus();
    titleInput.select();
  } catch (error) {
    setStatus(error.message || 'Could not load projects.', true);
    submitBtn.disabled = true;
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  setStatus('');
  submitBtn.disabled = true;

  try {
    let description = null;
    if (includePage.checked) {
      const tab = await getActiveTab();
      if (tab?.url && !/^chrome:|^chrome-extension:|^about:/.test(tab.url)) {
        description = `Captured from: ${tab.url}`;
      }
    }

    await createTodo({
      title: titleInput.value.trim(),
      projectId: projectSelect.value,
      priority: prioritySelect.value,
      description,
    });

    setStatus('Task created.');
    titleInput.value = '';
    setTimeout(() => window.close(), 700);
  } catch (error) {
    setStatus(error.message || 'Could not create task.', true);
  } finally {
    submitBtn.disabled = false;
  }
});

init();
