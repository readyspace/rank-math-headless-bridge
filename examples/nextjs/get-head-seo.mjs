// Server-side helper. Keep the CMS address in trusted deployment configuration.
export async function getHeadSeo(postId) {
  if (!Number.isSafeInteger(postId) || postId < 1) {
    throw new TypeError('A positive WordPress post ID is required.');
  }

  const cms = new URL(process.env.WORDPRESS_CMS_URL || '');
  if (cms.protocol !== 'https:' || cms.username || cms.password || cms.search || cms.hash) {
    throw new Error('WORDPRESS_CMS_URL must be a trusted HTTPS CMS base URL.');
  }

  // Preserve a CMS installed in a subdirectory, for example /wordpress/.
  const base = `${cms.origin}${cms.pathname.replace(/\/$/, '')}/`;
  const endpoint = new URL('wp-json/headless-bridge/v1/head', base);
  endpoint.searchParams.set('post_id', String(postId));
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 5000);

  try {
    const response = await fetch(endpoint, {
      headers: { Accept: 'application/json' },
      cache: 'no-store',
      redirect: 'error',
      signal: controller.signal,
    });
    if (!response.ok) {
      throw new Error(`Headless metadata request failed (${response.status}).`);
    }

    const seo = await response.json();
    if (
      !seo || seo.postId !== postId ||
      typeof seo.title !== 'string' || typeof seo.description !== 'string' ||
      typeof seo.canonical !== 'string' ||
      !seo.robots || typeof seo.robots !== 'object' || Array.isArray(seo.robots) ||
      !Array.isArray(seo.openGraph) || !Array.isArray(seo.twitter) || !Array.isArray(seo.jsonLd)
    ) {
      throw new Error('The CMS returned an unexpected metadata response.');
    }
    return seo;
  } finally {
    clearTimeout(timeout);
  }
}

// Use for a URL attribute, not arbitrary raw HTML.
export function safeHttpUrl(value) {
  if (typeof value !== 'string' || !value) return undefined;
  try {
    const url = new URL(value);
    if (!['http:', 'https:'].includes(url.protocol) || url.username || url.password) {
      return undefined;
    }
    return url.href;
  } catch {
    return undefined;
  }
}

// JSON-LD needs JSON serialization and HTML script-boundary protection.
export function serializeJsonLd(value) {
  if (!value || typeof value !== 'object') {
    throw new TypeError('JSON-LD must be a decoded object or array.');
  }
  return JSON.stringify(value)
    .replace(/</g, '\\u003c')
    .replace(/\u2028/g, '\\u2028')
    .replace(/\u2029/g, '\\u2029');
}
