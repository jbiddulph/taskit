import { createTodo, getSettings } from './api.js';

const MENU_PAGE = 'zaptask-add-page';
const MENU_SELECTION = 'zaptask-add-selection';

chrome.runtime.onInstalled.addListener(() => {
  chrome.contextMenus.removeAll(() => {
    chrome.contextMenus.create({
      id: MENU_PAGE,
      title: 'Add page to ZapTask',
      contexts: ['page', 'link'],
    });
    chrome.contextMenus.create({
      id: MENU_SELECTION,
      title: 'Add selection to ZapTask',
      contexts: ['selection'],
    });
  });
});

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  try {
    const settings = await getSettings();
    if (!settings.apiToken) {
      notify('Open ZapTask extension options and add your API token.');
      chrome.runtime.openOptionsPage();
      return;
    }

    if (!settings.defaultProjectId) {
      notify('Set a default project in ZapTask extension options first.');
      chrome.runtime.openOptionsPage();
      return;
    }

    let title = 'New task';
    let description = '';

    if (info.menuItemId === MENU_SELECTION) {
      title = (info.selectionText || '').trim().slice(0, 255) || 'New task';
      description = tab?.url ? `From: ${tab.url}` : '';
    } else {
      title = (tab?.title || info.linkUrl || 'New task').trim().slice(0, 255);
      const url = info.linkUrl || tab?.url || '';
      description = url ? `Captured from: ${url}` : '';
    }

    await createTodo({
      title,
      projectId: settings.defaultProjectId,
      priority: settings.defaultPriority || 'Medium',
      description,
    });

    notify(`Task created: ${title}`);
  } catch (error) {
    notify(error.message || 'Could not create task');
  }
});

function notify(message) {
  chrome.notifications.create({
    type: 'basic',
    iconUrl: 'icons/icon128.png',
    title: 'ZapTask',
    message,
  });
}
