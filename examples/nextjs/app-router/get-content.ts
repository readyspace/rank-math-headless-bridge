import { cache } from 'react';
import { normalizeHeadlessSeo } from './metadata';

const query = `query HeadlessPage($uri: String!) {
  nodeByUri(uri: $uri) {
    ... on ContentNode {
      databaseId
      headlessSeo {
        postId title description canonical
        robots { robots googlebot bingbot }
        openGraph { property content }
        twitter { name content }
        jsonLd
      }
    }
    ... on NodeWithTitle { title }
  }
}`;

/** Shared within a server render by generateMetadata and the page component. */
export const getContent = cache(async (uri: string) => {
  const endpoint = new URL(process.env.WORDPRESS_GRAPHQL_URL || '');
  if (endpoint.protocol !== 'https:' || endpoint.username || endpoint.password || endpoint.search || endpoint.hash) {
    throw new Error('WORDPRESS_GRAPHQL_URL must be a trusted HTTPS GraphQL endpoint.');
  }
  const response = await fetch(endpoint, {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ query, variables: { uri } }),
    cache: 'no-store', redirect: 'error', signal: AbortSignal.timeout(5000),
  });
  if (!response.ok) throw new Error(`Content query failed (${response.status}).`);
  const payload = await response.json();
  if (payload.errors?.length) throw new Error('The CMS could not resolve the headless content query.');
  const node = payload.data?.nodeByUri;
  if (!node) return null;
  const seo = normalizeHeadlessSeo(node.headlessSeo);
  if (node.databaseId !== seo.postId || typeof node.title !== 'string') throw new Error('Content and SEO do not identify the same post.');
  return { title: node.title, seo };
});
