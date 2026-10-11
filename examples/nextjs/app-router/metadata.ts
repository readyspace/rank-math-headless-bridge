import type { Metadata } from 'next';

export type JsonLd = Record<string, unknown> | unknown[];
export type HeadlessSeo = {
  postId: number;
  title: string;
  description: string;
  canonical: string;
  robots: { robots?: string; googlebot?: string; bingbot?: string };
  openGraph: { property: string; content: string }[];
  twitter: { name: string; content: string }[];
  jsonLd: JsonLd[];
};

export function safeHttpUrl(value: string): string | undefined {
  try {
    const url = new URL(value);
    if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) return undefined;
    return url.href;
  } catch { return undefined; }
}

/** Accept the complete REST response or GraphQL headlessSeo selection. */
export function normalizeHeadlessSeo(input: unknown): HeadlessSeo {
  if (!input || typeof input !== 'object') throw new TypeError('Missing headless SEO data.');
  const value = input as Record<string, unknown>;
  if (!Number.isSafeInteger(value.postId) || Number(value.postId) < 1 ||
      !['title', 'description', 'canonical'].every(key => typeof value[key] === 'string') ||
      !value.robots || typeof value.robots !== 'object' || Array.isArray(value.robots) ||
      !Array.isArray(value.openGraph) || !Array.isArray(value.twitter) || !Array.isArray(value.jsonLd)) {
    throw new TypeError('Unexpected headless SEO response. Select the complete GraphQL fragment.');
  }
  const robots: HeadlessSeo['robots'] = {};
  for (const key of ['robots', 'googlebot', 'bingbot'] as const) {
    const directive = (value.robots as Record<string, unknown>)[key];
    if (directive != null && typeof directive !== 'string') throw new TypeError('Invalid robots directive.');
    if (typeof directive === 'string' && directive) robots[key] = directive;
  }
  const openGraph = value.openGraph.map(tag => {
    if (!tag || typeof tag.property !== 'string' || !/^(og|article|profile):[a-z0-9_:]+$/.test(tag.property) || typeof tag.content !== 'string') {
      throw new TypeError('Invalid Open Graph tag.');
    }
    return { property: tag.property, content: tag.content };
  });
  const twitter = value.twitter.map(tag => {
    if (!tag || typeof tag.name !== 'string' || !/^twitter:[a-z0-9_:]+$/.test(tag.name) || typeof tag.content !== 'string') {
      throw new TypeError('Invalid Twitter tag.');
    }
    return { name: tag.name, content: tag.content };
  });
  const jsonLd = value.jsonLd.map(item => {
    // GraphQL uses JSON strings to keep arbitrary Rank Math schemas schema-independent.
    const decoded: unknown = typeof item === 'string' ? JSON.parse(item) : item;
    if (!decoded || typeof decoded !== 'object') throw new TypeError('JSON-LD must be a decoded object or array.');
    return decoded as JsonLd;
  });
  return { postId: Number(value.postId), title: String(value.title), description: String(value.description),
    canonical: String(value.canonical), robots, openGraph, twitter, jsonLd };
}

/** Rank Math has already applied title templates. Prevent Next.js applying another. */
export function toNextMetadata(seo: HeadlessSeo): Metadata {
  return {
    title: seo.title ? { absolute: seo.title } : null,
    description: seo.description || null,
    alternates: { canonical: safeHttpUrl(seo.canonical) || null },
    robots: seo.robots.robots || null,
    other: {
      googlebot: seo.robots.googlebot || [],
      bingbot: seo.robots.bingbot || [],
    },
    // HeadlessSeoTags owns social metadata, preserving ordered and custom properties.
    openGraph: null,
    twitter: null,
  };
}

export function serializeJsonLd(value: JsonLd): string {
  return JSON.stringify(value).replace(/</g, '\\u003c').replace(/\u2028/g, '\\u2028').replace(/\u2029/g, '\\u2029');
}
