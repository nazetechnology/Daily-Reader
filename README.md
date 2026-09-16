# The Daily Reader

A lightweight, single-page reading desk for the daily.dev public feed.

Created by [Naze Technology](https://naze.in).

## Features

- Read daily.dev posts in a focused feed.
- Filter posts by all, unread, saved, tag, or text search.
- Save posts for later and mark posts as read.
- Open posts, copy links, and export the visible list as Markdown.
- Use the daily.dev API directly or fall back to the included CORS proxy.
- No build step and no frontend dependencies.

## Requirements

- A modern web browser.
- A daily.dev personal access token.
- Node.js only if the proxy is needed.

## Quick Start

1. Download or clone this repository.
2. Start a local server from the project directory:

   ```powershell
   py -m http.server 8000
   ```

3. Open [http://localhost:8000](http://localhost:8000).
4. Paste your daily.dev personal access token into the startup screen.
5. Select **OPEN THE WIRE**.

The reader tries the daily.dev API directly first. If the browser blocks the request because of CORS, start the optional proxy below and use the default proxy URL in **SETUP**.

## Optional CORS Proxy

Open a second terminal in the project directory and run:

```powershell
node .\daily-proxy.js
```

The proxy listens on `http://localhost:8010` by default. Keep the reader's proxy URL set to that address, then choose **RUN TEST** or **SAVE & LOAD FEED** in **SETUP**.

To keep the API token on the server instead of entering it in the browser, configure `DAILY_KEY` before starting the proxy.

### Windows PowerShell

```powershell
$env:DAILY_KEY="your-daily-dev-token"
node .\daily-proxy.js
```

### Windows Command Prompt

```bat
set DAILY_KEY=your-daily-dev-token
node daily-proxy.js
```

You can also change the port with the `PORT` environment variable:

```powershell
$env:PORT="8011"
node .\daily-proxy.js
```

If you change the port, update the proxy URL in the reader's **SETUP** panel.

## Privacy and Security

- The reader stores your token and reading preferences in your browser's local storage.
- Do not commit API tokens or place them in public source files.
- Use `DAILY_KEY` when you want the local proxy to hold the token instead.
- The proxy does not store tokens or responses.

## Project Files

- `index.html` - the complete frontend and interface.
- `daily-proxy.js` - optional zero-dependency Node.js proxy.
- `LICENSE` - MIT license.

## Contributing

Contributors are welcome. To contribute:

1. Fork the repository. 
2. Create a focused feature or fix branch.
3. Test the reader in a browser and run `node --check .\daily-proxy.js`.
4. Open a pull request with a clear description of the change.

Please keep changes focused, avoid committing secrets, and preserve the dependency-free setup where possible.

## License

This project is available under the [MIT License](LICENSE).
