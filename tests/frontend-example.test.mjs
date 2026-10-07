import test from 'node:test';
import assert from 'node:assert/strict';
import { safeHttpUrl, serializeJsonLd, getHeadSeo } from '../examples/nextjs/get-head-seo.mjs';

test('JSON-LD stays valid without closing the HTML script element', () => {
  const value = { description: '</script><script>alert(1)</script>\u2028\u2029' };
  const serialized = serializeJsonLd(value);
  assert.equal(serialized.includes('<'), false);
  assert.deepEqual(JSON.parse(serialized), value);
});

test('URL attributes reject executable schemes and embedded credentials', () => {
  for (const value of ['javascript:alert(1)', 'https://user:secret@example.com/', 'data:text/html,<script>x</script>', 'relative/path']) {
    assert.equal(safeHttpUrl(value), undefined);
  }
  assert.equal(safeHttpUrl('https://www.example.com/article/'), 'https://www.example.com/article/');
});

test('SEO fetcher refuses invalid IDs before network access', async () => {
  for (const value of [0, -1, '1', 1.5, Number.MAX_SAFE_INTEGER + 1]) {
    await assert.rejects(() => getHeadSeo(value), TypeError);
  }
});
