import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

const html = await readFile(new URL('../tests/fixtures/next-app/.next/server/app/index.html', import.meta.url), 'utf8');
const head = html.slice(html.indexOf('<head>'), html.indexOf('</head>'));
assert.match(head, /<title>Article \| Rank Math<\/title>/);
assert.ok(!head.includes('Parent suffix') && !head.includes('Parent OG') && !head.includes('Parent Twitter'));
assert.equal((head.match(/rel="canonical"/g) || []).length, 1);
assert.match(head, /href="https:\/\/www.example.com\/article\/"/);
assert.match(head, /name="robots" content="noindex, follow, max-image-preview:large"/);
assert.match(head, /name="googlebot" content="noindex, follow"/);
assert.match(head, /name="bingbot" content="noindex"/);
assert.equal((head.match(/property="og:image"/g) || []).length, 2);
assert.equal((head.match(/name="twitter:card"/g) || []).length, 1);
assert.ok(!html.includes('<script>alert'));
const json = html.match(/<script type="application\/ld\+json">([^]*?)<\/script>/)?.[1];
assert.ok(json, 'JSON-LD must be present in generated HTML.');
assert.equal(JSON.parse(json).description, '</script><script>alert(1)</script>');
console.log('Next.js production HTML: title, canonical, robots, social tags and safe JSON-LD passed.');
