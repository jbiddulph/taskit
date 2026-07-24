import { createTodo, getSettings, resolveProjectId } from './api.js';

const MENU_PAGE = 'zaptask-add-page';
const MENU_SELECTION = 'zaptask-add-selection';

function ensureContextMenus() {
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
}

chrome.runtime.onInstalled.addListener(ensureContextMenus);
chrome.runtime.onStartup.addListener(ensureContextMenus);

chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  try {
    const settings = await getSettings();
    if (!settings.apiToken) {
      notify('Add your API token in Options, then try again.');
      chrome.runtime.openOptionsPage();
      return;
    }

    const projectId = await resolveProjectId();

    let title = 'New task';
    let description = '';
    const pageUrl = tab?.url || '';
    const isBrowserPage = /^chrome:|^chrome-extension:|^about:|^edge:/.test(pageUrl);

    if (info.menuItemId === MENU_SELECTION) {
      title = (info.selectionText || '').trim().slice(0, 255) || 'New task';
      description = pageUrl && !isBrowserPage ? `From: ${pageUrl}` : '';
    } else {
      title = (tab?.title || info.linkUrl || 'New task').trim().slice(0, 255);
      const url = info.linkUrl || (isBrowserPage ? '' : pageUrl);
      description = url ? `Captured from: ${url}` : '';
    }

    await createTodo({
      title,
      projectId,
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
    message: String(message).slice(0, 250),
  });
}
