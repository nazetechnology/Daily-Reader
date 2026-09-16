'use strict';

const http = require('http');
const https = require('https');
const { URL } = require('url');

const PORT = Number(process.env.PORT) || 8010;

// Optional server-side API token.
// If not provided, browser's api-key / authorization header will be used.
const KEY = process.env.DAILY_KEY || '';

const DAILY_HOST = 'api.daily.dev';
const DAILY_BASE = '/public/v1';


// ---------------------------------------------------------
// Helpers
// ---------------------------------------------------------

function sendJSON(res, status, data) {
  const body = JSON.stringify(data);

  res.writeHead(status, {
    'Content-Type': 'application/json; charset=utf-8',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Authorization, api-key, Content-Type',
    'Access-Control-Allow-Methods': 'GET, OPTIONS',
    'Cache-Control': 'no-store'
  });

  res.end(body);
}


function getToken(req) {
  // 1. Server environment variable
  if (KEY) {
    return KEY;
  }

  // 2. Standard Authorization header
  const auth = req.headers.authorization;

  if (auth && /^Bearer\s+/i.test(auth)) {
    return auth.replace(/^Bearer\s+/i, '').trim();
  }

  // 3. Backward compatibility with your existing index.html
  const apiKey = req.headers['api-key'];

  if (apiKey) {
    return String(apiKey).trim();
  }

  return '';
}


function buildAuthorizationHeader(req) {
  const token = getToken(req);

  if (!token) {
    return {};
  }

  return {
    Authorization: 'Bearer ' + token
  };
}


// ---------------------------------------------------------
// Convert your OLD frontend request to the NEW daily.dev API
//
// OLD:
//   GET /posts?page=1&pageSize=20
//
// NEW:
//   GET /feeds/foryou?limit=20
// ---------------------------------------------------------

function translateRequest(req) {
  const incoming = new URL(
    req.url,
    `http://${req.headers.host || 'localhost'}`
  );

  let pathname = incoming.pathname;

  const params = incoming.searchParams;

  // -------------------------------------------------------
  // Existing frontend calls:
  //
  // /posts?page=1&pageSize=20
  //
  // Convert to:
  //
  // /feeds/foryou?limit=20
  // -------------------------------------------------------

  if (pathname === '/posts' || pathname === '/posts/') {

    const pageSize = Number(params.get('pageSize')) || 20;

    const limit = Math.max(
      1,
      Math.min(50, pageSize)
    );

    // If your frontend sends a tag, use the official
    // /feeds/tag/{tag} endpoint.
    const tag = params.get('tag');

    if (tag) {

      pathname =
        '/feeds/tag/' +
        encodeURIComponent(tag);

      const outgoing = new URLSearchParams();

      outgoing.set('limit', String(limit));

      return pathname + '?' + outgoing.toString();
    }

    // Default feed
    pathname = '/feeds/foryou';

    const outgoing = new URLSearchParams();

    outgoing.set('limit', String(limit));

    return pathname + '?' + outgoing.toString();
  }


  // -------------------------------------------------------
  // Allow official endpoints directly as well:
  //
  // /feeds/foryou
  // /feeds/popular
  // /feeds/discussed
  // /feeds/tag/javascript
  // /feeds/source/...
  // /search/posts
  // etc.
  // -------------------------------------------------------

  return pathname + (incoming.search || '');
}


// ---------------------------------------------------------
// Transform NEW daily.dev response into the OLD response
// shape expected by your existing index.html.
//
// daily.dev:
// {
//   data: [...],
//   pagination: {
//     hasNextPage: true,
//     cursor: "..."
//   }
// }
//
// Your frontend expects:
// {
//   posts: [...],
//   paginationInfo: {
//     hasNext: true
//   }
// }
// ---------------------------------------------------------

function transformResponse(path, json) {

  // Compatibility response for feed endpoints
  if (
    json &&
    Array.isArray(json.data) &&
    json.pagination
  ) {

    return {
      posts: json.data,

      paginationInfo: {
        hasNext:
          json.pagination.hasNextPage === true,

        cursor:
          json.pagination.cursor || null
      },

      // Keep original response too.
      // Useful for debugging / future frontend updates.
      data: json.data,

      pagination: json.pagination
    };
  }

  // If response isn't a feed response,
  // return it unchanged.
  return json;
}


// ---------------------------------------------------------
// Proxy request
// ---------------------------------------------------------

function proxyRequest(req, res) {

  const targetPath = translateRequest(req);

  const authorization =
    buildAuthorizationHeader(req);

  const headers = {
    Accept: 'application/json',
    'User-Agent': 'Daily-Reader-Proxy/1.0',
    ...authorization
  };


  console.log(
    `[${new Date().toLocaleTimeString()}] ` +
    `${req.method} ${req.url} ` +
    `→ ${DAILY_BASE}${targetPath}`
  );


  const options = {
    hostname: DAILY_HOST,
    port: 443,
    method: 'GET',

    path: DAILY_BASE + targetPath,

    headers
  };


  const upstream = https.request(
    options,
    (up) => {

      let body = '';

      up.setEncoding('utf8');

      up.on('data', chunk => {
        body += chunk;
      });


      up.on('end', () => {

        let json;

        try {
          json = JSON.parse(body);
        } catch (_) {

          sendJSON(res, up.statusCode || 502, {
            error: 'invalid_upstream_response',
            message:
              'daily.dev returned a non-JSON response',
            upstreamStatus:
              up.statusCode
          });

          return;
        }


        // Transform only successful feed responses.
        const output =
          up.statusCode >= 200 &&
          up.statusCode < 300
            ? transformResponse(targetPath, json)
            : json;


        sendJSON(
          res,
          up.statusCode || 502,
          output
        );

      });
    }
  );


  upstream.on('error', (err) => {

    console.error(
      'Upstream request failed:',
      err.message
    );

    sendJSON(res, 502, {
      error: 'upstream_failed',
      message: err.message
    });

  });


  upstream.end();
}


// ---------------------------------------------------------
// HTTP server
// ---------------------------------------------------------

const server = http.createServer(
  (req, res) => {

    // CORS
    res.setHeader(
      'Access-Control-Allow-Origin',
      '*'
    );

    res.setHeader(
      'Access-Control-Allow-Headers',
      'Authorization, api-key, Content-Type'
    );

    res.setHeader(
      'Access-Control-Allow-Methods',
      'GET, OPTIONS'
    );


    // Browser preflight
    if (req.method === 'OPTIONS') {

      res.writeHead(204);

      res.end();

      return;
    }


    // Only GET is needed for your reader.
    if (req.method !== 'GET') {

      sendJSON(res, 405, {
        error: 'method_not_allowed',
        message: 'Only GET requests are supported.'
      });

      return;
    }


    try {

      proxyRequest(req, res);

    } catch (err) {

      console.error(err);

      sendJSON(res, 500, {
        error: 'proxy_error',
        message: err.message
      });

    }
  }
);


// ---------------------------------------------------------
// Start
// ---------------------------------------------------------

server.listen(
  PORT,
  () => {

    console.log('');
    console.log('==========================================');
    console.log(' daily.dev Reader Proxy');
    console.log('==========================================');
    console.log('');
    console.log(
      `Local proxy : http://localhost:${PORT}`
    );
    console.log(
      `Upstream    : https://${DAILY_HOST}${DAILY_BASE}`
    );
    console.log(
      `API token   : ${KEY ? 'SERVER ENVIRONMENT' : 'BROWSER REQUEST'}`
    );
    console.log('');
    console.log(
      'Frontend endpoint:'
    );
    console.log(
      `http://localhost:${PORT}/posts?page=1&pageSize=20`
    );
    console.log('');
    console.log(
      'This will internally call:'
    );
    console.log(
      `https://${DAILY_HOST}${DAILY_BASE}/feeds/foryou?limit=20`
    );
    console.log('');
    console.log('==========================================');
    console.log('');
  }
);