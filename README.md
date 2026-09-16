# The Daily Reader

A lightweight reading desk for the daily.dev public feed, created by [Naze Technology](https://naze.in).

## Live Website

Use the production website here:

[Open The Daily Reader](https://daily.naze.in/)

The site is hosted on Hostinger and uses the included PHP proxy to communicate with the daily.dev API.

## Features

- Read daily.dev posts in a focused feed.
- Filter posts by unread, saved, tag, or text search.
- Save posts for later and mark posts as read.
- Open articles, copy links, and export the visible list as Markdown.
- Works without a build step or frontend package installation.

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

From the project directory, run:

```powershell
py -m http.server 8000
```

Then open [http://localhost:8000](http://localhost:8000).

If the browser blocks direct API requests because of CORS, start the local Node.js proxy in a second terminal:

```powershell
node .\daily-proxy.js
```

The local proxy listens on `http://localhost:8010` by default. Configure that address in **SETUP** if required.

## Hostinger Deployment

The production site uses these files inside the Hostinger `public_html` directory:

```text
index.html
proxy.php
.htaccess
```

Hostinger must have PHP and cURL enabled. The PHP proxy forwards feed requests to daily.dev, handles browser preflight requests, and keeps the frontend and proxy on the same HTTPS domain.

The Node.js file is for local development. Standard PHP hosting does not run `daily-proxy.js` as a server.

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
- `proxy.php` - the Hostinger-compatible PHP API proxy.
- `.htaccess` - HTTPS, proxy routing, and basic web-server rules.
- `daily-proxy.js` - the optional local Node.js proxy.
- `LICENSE` - the MIT license.

## License

This project is available under the [MIT License](LICENSE).
