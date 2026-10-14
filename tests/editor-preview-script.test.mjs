import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

// Exercise the production nowdoc, rather than a second copy of its URL logic.
const source = readFileSync(new URL('../includes/class-editor-links.php', import.meta.url), 'utf8');
const match = source.match(/\$script = <<<'JS'\r?\n([\s\S]*?)\r?\nJS;/);
assert.ok(match, 'The provider preview script must remain available for regression testing.');
const script = match[1];
const config = {
  preview: 'https://www.example.com/preview/?p=42&preview=true&previewPathname=%2F%3Fp%3D42&typeName=Post&provider=a%2Bb&preview_nonce=old-nonce&preview_id=42#editor',
  origins: ['https://cms.example.com', 'https://www.example.com', 'http://cms.example.com'],
};

function anchor(initial) {
  let href = initial;
  let writes = 0;
  return {
    get href() { return href; },
    set href(value) { href = value; writes++; },
    get writes() { return writes; },
  };
}

function editor(initialAnchors = [], readyState = 'complete') {
  const anchors = [...initialAnchors];
  const frames = [];
  const listeners = new Map();
  const observations = [];
  let notify;
  const document = {
    readyState,
    body: {},
    querySelectorAll(selector) {
      assert.equal(selector, 'a[href]');
      return anchors;
    },
    addEventListener(name, callback, options) {
      assert.equal(options.once, true);
      listeners.set(name, callback);
    },
  };
  class MutationObserver {
    constructor(callback) { notify = callback; }
    observe(target, options) {
      assert.equal(target, document.body);
      assert.equal(options.subtree, true);
      assert.equal(options.childList, true);
      assert.equal(options.attributes, true);
      assert.deepEqual(Array.from(options.attributeFilter), ['href']);
      observations.push(options);
    }
  }
  vm.runInNewContext(script.replace('CONFIG', JSON.stringify(config)), {
    URL, document, MutationObserver,
    requestAnimationFrame(callback) { frames.push(callback); },
  });
  return {
    anchors, frames, observations,
    mutation() { assert.equal(typeof notify, 'function'); notify(); },
    flush() { const callback = frames.shift(); assert.equal(typeof callback, 'function'); callback(); },
    ready() { assert.equal(typeof listeners.get('DOMContentLoaded'), 'function'); listeners.get('DOMContentLoaded')(); },
  };
}

test('provider routing survives fresh WordPress preview and autosave parameters', () => {
  const preview = anchor('https://cms.example.com/article/?p=999&preview=true&previewPathname=%2Fwrong&typeName=Page&preview_nonce=fresh-nonce&preview_id=88&autosave=88&_wpnonce=fresh-editor-nonce');
  editor([preview]);
  const url = new URL(preview.href);
  assert.equal(url.origin, 'https://www.example.com');
  assert.equal(url.pathname, '/preview/');
  assert.equal(url.hash, '#editor');
  assert.equal(url.searchParams.get('p'), '42');
  assert.equal(url.searchParams.get('preview'), 'true');
  assert.equal(url.searchParams.get('previewPathname'), '/?p=42');
  assert.equal(url.searchParams.get('typeName'), 'Post');
  assert.equal(url.searchParams.get('provider'), 'a+b');
  assert.equal(url.searchParams.get('preview_nonce'), 'fresh-nonce');
  assert.equal(url.searchParams.get('preview_id'), '88');
  assert.equal(url.searchParams.get('autosave'), '88');
  assert.equal(url.searchParams.get('_wpnonce'), 'fresh-editor-nonce');
  assert.equal(preview.writes, 1);
});

test('only preview anchors on configured CMS/frontend origins are repaired', () => {
  const unchanged = [
    'https://foreign.example/article/?preview=true&preview_nonce=foreign',
    'https://cms.example.com.evil.example/?preview=true',
    'https://cms.example.com:8443/?preview=true',
    'http://www.example.com/?preview=true',
    'https://cms.example.com/article/',
    'https://cms.example.com/article/?preview=false',
    'https://cms.example.com/article/?preview=1',
    'javascript:alert(1)',
    'invalid-url',
  ].map(anchor);
  const allowed = [
    anchor('http://cms.example.com/?preview=true&preview_nonce=http-cms'),
    anchor('https://www.example.com/old/?preview=true&preview_nonce=public-origin'),
  ];
  const before = unchanged.map(link => link.href);
  editor([...unchanged, ...allowed]);
  assert.deepEqual(unchanged.map(link => link.href), before);
  assert.deepEqual(unchanged.map(link => link.writes), before.map(() => 0));
  for (const link of allowed) {
    const url = new URL(link.href);
    assert.equal(url.origin, 'https://www.example.com');
    assert.equal(url.pathname, '/preview/');
    assert.equal(url.searchParams.get('p'), '42');
    assert.equal(link.writes, 1);
  }
  assert.equal(new URL(allowed[0].href).searchParams.get('preview_nonce'), 'http-cms');
  assert.equal(new URL(allowed[1].href).searchParams.get('preview_nonce'), 'public-origin');
});

test('dynamic Gutenberg links repair once per frame and remain idempotent', () => {
  const state = editor();
  assert.equal(state.observations.length, 1);
  const dynamic = anchor('https://cms.example.com/new/?preview=true&p=500&preview_nonce=new-autosave&preview_id=99');
  state.anchors.push(dynamic);
  state.mutation();
  state.mutation();
  state.mutation();
  assert.equal(state.frames.length, 1);
  assert.equal(dynamic.writes, 0);
  state.flush();
  assert.equal(new URL(dynamic.href).searchParams.get('p'), '42');
  assert.equal(new URL(dynamic.href).searchParams.get('preview_nonce'), 'new-autosave');
  assert.equal(new URL(dynamic.href).searchParams.get('preview_id'), '99');
  assert.equal(dynamic.writes, 1);
  const firstRepair = dynamic.href;
  state.mutation();
  state.flush();
  assert.equal(dynamic.href, firstRepair);
  assert.equal(dynamic.writes, 1, 'The observer must not create an href mutation loop.');
});

test('deferred editor initialization waits for the DOM and then repairs previews', () => {
  const preview = anchor('https://cms.example.com/?preview=true&preview_nonce=deferred');
  const state = editor([preview], 'loading');
  assert.equal(preview.writes, 0);
  assert.equal(state.observations.length, 0);
  state.ready();
  assert.equal(preview.writes, 1);
  assert.equal(state.observations.length, 1);
  assert.equal(new URL(preview.href).searchParams.get('preview_nonce'), 'deferred');
});
