# The Daily Reader

A lightweight reading desk for the daily.dev public feed, created by [Naze Technology](https://naze.in).

## Live Website

Use the production website here:

[Open The Daily Reader](https://daily.naze.in/)

The current live deployment uses the included PHP proxy to communicate with the daily.dev API.

## Features

- Read daily.dev posts in a focused feed.
- Filter posts by unread, saved, tag, or text search.
- Save posts for later and mark posts as read.
- Open articles, copy links, and export the visible list as Markdown.
- Works without a build step or frontend package installation.

## Requirements

- A modern web browser.
- A daily.dev personal access token.
- A server-side proxy only when direct browser requests are blocked by CORS.
- Choose either PHP with cURL or Node.js for the proxy. Both are optional; you do not need to install or run both.

## How to Use

1. Open [daily.naze.in](https://daily.naze.in/).
2. Enter your own daily.dev personal access token when the startup window appears.
3. Select **OPEN THE WIRE**.
4. Read, filter, save, and manage your feed.

The token is entered only in your browser window. It is not included in the public source code, URL, or README.

## Release Version

For everyday use, download the latest stable version from the [Releases](https://github.com/nazetechnology/Daily-Reader/releases) page. The release version is recommended for users who want a ready-to-use copy without changing the source code.

Use the live website for the simplest experience. Use the source code when you want to inspect, customize, or contribute to the project.

## Privacy and Security

- Each user enters and uses their own daily.dev token.
- The token is stored in that user's browser local storage and is not hard-coded in this repository.
- The token is sent through HTTPS as an authorization header when the feed is requested.
- The PHP proxy does not intentionally save tokens or API responses.
- Never share your token or add it to an issue, screenshot, URL, source file, or public post.
- Do not use the live website on a shared or public computer. Clear browser storage or revoke the token when necessary.

The live website is convenient, but privacy depends on your browser, device, hosting account, and token handling. For maximum privacy, run the project locally.

## Run Locally

Local use is the privacy-first option and does not require the public website.

### Option 1: Static frontend server

Use this option to serve the frontend files for testing:

```powershell
py -m http.server 8000
```

Then open [http://localhost:8000](http://localhost:8000).

### Option 2: PHP server and proxy

Use this option when PHP and cURL are available. From the project directory, run:

```powershell
php -S localhost:8000
```

Open [http://localhost:8000](http://localhost:8000), then set the proxy URL to `/proxy.php` and the route to `proxy` in **SETUP**.

### Option 3: Node.js proxy

Use this option when Node.js is available. Start the frontend server in one terminal:

```powershell
py -m http.server 8000
```

Then start the Node.js proxy in a second terminal:

```powershell
node .\daily-proxy.js
```

The Node.js proxy listens on `http://localhost:8010` by default. Keep that address in **SETUP** and use the `auto` or `proxy` route.

Choose the PHP proxy or Node.js proxy when direct browser requests are blocked by CORS. You do not need to run both proxy options.

## Shared Hosting or VPS Deployment

The project can run on any shared hosting account or VPS. Choose one proxy option based on what your server supports.

### PHP proxy

Use this option on a PHP-compatible server with cURL enabled. Upload these files to your web root, such as `public_html`:

```text
index.html
proxy.php
.htaccess
```

The PHP deployment requires PHP, cURL, Apache or an equivalent web server, and HTTPS. The PHP proxy forwards feed requests to daily.dev, handles browser preflight requests, and keeps the frontend and proxy on the same HTTPS domain.

### Node.js proxy

Use this option on a VPS or another server that supports long-running Node.js processes. Run `daily-proxy.js`, keep the frontend and proxy reachable over HTTPS, and set the proxy URL in **SETUP**.

The Node.js proxy and PHP proxy are alternatives. Choose one; the application does not require both.

## Community Support

Community support is welcome. For questions, bug reports, and feature requests, open an [Issue](https://github.com/nazetechnology/Daily-Reader/issues). Include your browser, operating system, setup method, and the relevant error message. Never include your API token.

If The Daily Reader is useful to you, please [star the repository](https://github.com/nazetechnology/Daily-Reader). A star helps other users discover the project and supports future improvements.

## Contributing

Contributors are welcome. You can help with bug reports, documentation, feature ideas, testing, or code.

1. Fork the repository.
2. Create a focused feature or fix branch.
3. Make your changes without adding secrets.
4. Test the reader in a browser.
5. Run `node --check .\daily-proxy.js` when changing the Node proxy.
6. Open a pull request with a clear description and testing notes.

Please keep contributions focused and preserve the simple, dependency-free setup where possible.

## Project Files

- `index.html` - the frontend application.
- `proxy.php` - the optional PHP API proxy.
- `.htaccess` - HTTPS, proxy routing, and basic web-server rules.
- `daily-proxy.js` - the optional Node.js API proxy.
- `LICENSE` - the MIT license.

## License

This project is available under the [MIT License](LICENSE).
