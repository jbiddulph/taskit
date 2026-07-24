# ZapTask Chrome Extension

Quick-capture tasks from any page into ZapTask.

## Install (developer / unpacked)

1. Open Chrome and go to `chrome://extensions`
2. Turn on **Developer mode** (top right)
3. Click **Load unpacked**
4. Select this folder: `browser-extension`
5. Pin the ZapTask icon from the puzzle menu if you like

## Connect to your account

1. In ZapTask, open **Settings → API Tokens**
2. Create a token and **copy it immediately** (shown once)
3. Right-click the extension icon → **Options** (or open Options from the extensions page)
4. Paste:
   - **API base URL**: `https://zaptask.co.uk/api` (or your local URL, e.g. `https://taskit.test/api`)
   - **API token**: the token you copied
5. Click **Test connection**, pick a **default project**, then **Save**

## Use it

- Click the toolbar icon to create a task (title, project, priority)
- Right-click a page → **Add page to ZapTask**
- Select text → right-click → **Add selection to ZapTask**

Right-click capture uses your default project from Options.
