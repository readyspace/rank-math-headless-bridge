import { test } from 'node:test';
import assert from 'node:assert/strict';
import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { normalizeHeadlessSeo, toNextMetadata, serializeJsonLd } from '../examples/nextjs/app-router/metadata';
import { HeadlessSeoTags } from '../examples/nextjs/app-router/HeadlessSeoTags';
import { seoFixture } from './fixtures/seo';

test('REST and GraphQL schema data normalize to the same frontend contract', () => {
  const rest = normalizeHeadlessSeo(seoFixture);
  const graphql = normalizeHeadlessSeo({ ...seoFixture, jsonLd: seoFixture.jsonLd.map(item => JSON.stringify(item)) });
  assert.deepEqual(rest, graphql);
  assert.deepEqual(toNextMetadata(rest).title, { absolute: seoFixture.title });
  assert.equal(toNextMetadata(rest).robots, seoFixture.robots.robots);
  assert.equal(toNextMetadata(rest).other?.googlebot, seoFixture.robots.googlebot);
});

test('invalid schema and partial GraphQL selections fail instead of deploying incomplete SEO', () => {
  for (const value of [null, { ...seoFixture, jsonLd: ['bad json'] }, { ...seoFixture, jsonLd: ['null'] },
    { ...seoFixture, robots: { robots: 1 } }, { title: 'partial' },
    { ...seoFixture, postId: -1 }, { ...seoFixture, openGraph: [{ property: 'onload', content: 'evil' }] }]) {
    assert.throws(() => normalizeHeadlessSeo(value));
  }
});

test('social tags preserve duplicate images, media host and schema without script-boundary injection', () => {
  const seo = normalizeHeadlessSeo(seoFixture);
  const html = renderToStaticMarkup(<html><head /><body><HeadlessSeoTags seo={seo} nonce="fixture-nonce" /></body></html>);
  assert.equal((html.match(/property="og:image"/g) || []).length, 2);
  assert.ok(html.indexOf('cms.example.com/uploads/first.jpg') < html.indexOf('cdn.example.com/second.jpg'));
  assert.ok(html.indexOf('property="og:type"') < html.indexOf('</head>'));
  assert.ok(html.includes('nonce="fixture-nonce"'));
  assert.ok(!html.includes('<script>alert'));
  assert.deepEqual(JSON.parse(serializeJsonLd(seo.jsonLd[0])), seoFixture.jsonLd[0]);
});

test('unsafe canonical and social URL schemes are omitted', () => {
  const seo = normalizeHeadlessSeo({ ...seoFixture, canonical: 'javascript:alert(1)',
    openGraph: [{ property: 'og:image', content: 'javascript:alert(1)' }],
    twitter: [{ name: 'twitter:image', content: 'https://user:secret@example.com/image' }] });
  assert.equal(toNextMetadata(seo).alternates?.canonical, null);
  const html = renderToStaticMarkup(<HeadlessSeoTags seo={seo} />);
  assert.ok(!html.includes('javascript:') && !html.includes('user:secret'));
});
