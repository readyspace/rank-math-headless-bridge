import React from 'react';
import { safeHttpUrl, serializeJsonLd, type HeadlessSeo } from './metadata';

/** Server component. React 19 hoists these metadata tags into the document head. */
export function HeadlessSeoTags({ seo, nonce }: { seo: HeadlessSeo; nonce?: string }) {
  return <>
    {seo.openGraph.map((tag, index) => {
      const isUrl = /^(og:(url|image|video|audio)(:(url|secure_url))?|article:(author|publisher)|profile:url)$/.test(tag.property);
      const content = isUrl ? safeHttpUrl(tag.content) : tag.content;
      return content ? <meta key={`og-${index}`} property={tag.property} content={content} /> : null;
    })}
    {seo.twitter.map((tag, index) => {
      const isUrl = /^twitter:(url|image(:src)?|player(:stream)?|app:url:.*)$/.test(tag.name);
      const content = isUrl ? safeHttpUrl(tag.content) : tag.content;
      return content ? <meta key={`twitter-${index}`} name={tag.name} content={content} /> : null;
    })}
    {seo.jsonLd.map((graph, index) => <script key={`jsonld-${index}`} type="application/ld+json"
      nonce={nonce} dangerouslySetInnerHTML={{ __html: serializeJsonLd(graph) }} />)}
  </>;
}
