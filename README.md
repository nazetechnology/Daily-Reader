# The Daily Reader

A lightweight, privacy-first reading desk for the daily.dev public feed.

Created by [Naze Technology](https://naze.in).

## Try It Live

The live GitHub Pages site is available for testing and quick demos:

[Open The Daily Reader](https://nazetechnology.github.io/Daily-Reader/)

For regular use, download the latest **release version** from GitHub. The release version is the recommended, stable option for users who want to use the app without working from source code.

## Features

- Read daily.dev posts in a focused feed.
- Filter posts by unread, saved, tag, or text search.
- Save posts for later and mark posts as read.
- Open articles, copy links, and export the visible list as Markdown.
- Use the daily.dev API directly or through the optional local proxy.
- No build step and no frontend package installation required.

## Requirements

- A modern web browser.
- A daily.dev personal access token.
- PHP with cURL enabled when hosting the proxy on a PHP server.
- Node.js only if the optional local proxy is needed.

## Recommended: Use the Release Version

1. Open the [Releases](https://github.com/nazetechnology/Daily-Reader/releases) page.
2. Download the latest release version.
3. Extract the downloaded files.
4. Open the included `index.html`, or serve the folder locally using the instructions below.
5. Enter your daily.dev personal access token.
6. Select **OPEN THE WIRE**.

Using the release version is recommended because it represents the stable version intended for everyday use. Use the live GitHub Pages site for testing and previews, and use the source code when you want to contribute or customize the project.

## Privacy-First Local Use

For the most private and hassle-free experience, run The Daily Reader locally. Your token, saved posts, read status, and preferences are stored in your browser's local storage. The project does not need a hosted database or a build system.

From the project directory, start a local server:

```powershell
py -m http.server 8000
```

Then open [http://localhost:8000](http://localhost:8000) in your browser.

The reader tries the daily.dev API directly. If the browser blocks the request because of CORS, use the optional proxy below and keep the proxy URL set to `http://localhost:8010` in **SETUP**.

## Hostinger PHP Hosting

To host the complete project on Hostinger without GitHub Pages, upload these files to `public_html`:

```text
index.html
proxy.php
.htaccess
```

Open your HTTPS domain, then use `/proxy.php` as the proxy URL in **SETUP** and select the `proxy` route. The frontend automatically uses `/proxy.php` when hosted on a non-local domain. Hostinger must have PHP and cURL enabled.

The PHP proxy runs on the same domain as the frontend, forwards requests to daily.dev, handles browser preflight requests, and adds the required CORS headers. The included `.htaccess` file routes proxy paths correctly, redirects HTTP to HTTPS, and prevents the Node proxy source from being served publicly.

## Optional Local Proxy

GitHub Pages and standard PHP hosting cannot run Node.js. The Node proxy is intended for local development only; use `proxy.php` on Hostinger.

Open a second terminal in the project directory and run:

```powershell
node .\daily-proxy.js
```

The proxy listens on `http://localhost:8010` by default. Select **RUN TEST** or **SAVE & LOAD FEED** in **SETUP** after starting it.

To keep the API token in the proxy process instead of the browser, configure `DAILY_KEY` before starting the proxy.

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

## Privacy and Security

- Never commit your daily.dev token or add it to a public source file.
- The reader stores your token and reading preferences in your browser's local storage.
- The live GitHub Pages site is convenient for testing, but local use is recommended for regular private use.
- The optional proxy does not store tokens or API responses.
- Your token is sent to daily.dev when the reader requests your feed.

## Community Support

Community support is welcome. For questions, bug reports, and feature requests, open an [Issue](https://github.com/nazetechnology/Daily-Reader/issues). Please include your browser, operating system, setup method, and the relevant error message. Never include your API token in an issue.

If The Daily Reader is useful to you, please [star the repository](https://github.com/nazetechnology/Daily-Reader). A star helps other users discover the project and supports future improvements.

## Contributing

Contributors are welcome. You can help by reporting bugs, improving documentation, suggesting features, or submitting code.

1. Fork the repository.
2. Create a focused feature or fix branch.
3. Make your changes without adding secrets.
4. Test the reader in a browser.
5. Run `node --check .\daily-proxy.js` when changing the proxy.
6. Open a pull request with a clear description and testing notes.

Please keep contributions focused and preserve the project's simple, dependency-free setup where possible.

## Project Files

- `index.html` - the complete frontend and interface.
- `proxy.php` - the Hostinger-compatible PHP API proxy.
- `.htaccess` - HTTPS, proxy routing, and basic web-server rules.
- `daily-proxy.js` - the optional zero-dependency Node.js proxy.
- `LICENSE` - the MIT license.

## License

This project is available under the [MIT License](LICENSE).
