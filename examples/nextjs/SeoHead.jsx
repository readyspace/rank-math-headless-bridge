import Head from 'next/head';
import { safeHttpUrl, serializeJsonLd } from './get-head-seo.mjs';

// Pages Router component. Integrate this into the existing page SEO owner.
export default function SeoHead({ seo }) {
  const canonical = safeHttpUrl(seo.canonical);
  return (
    <Head>
      {seo.title ? <title>{seo.title}</title> : null}
      {seo.description ? <meta name="description" content={seo.description} key="description" /> : null}
      {canonical ? <link rel="canonical" href={canonical} key="canonical" /> : null}
      {['robots', 'googlebot', 'bingbot'].map((name) => (
        typeof seo.robots[name] === 'string' && seo.robots[name]
          ? <meta name={name} content={seo.robots[name]} key={name} />
          : null
      ))}
      {seo.openGraph.map((tag, index) => (
        tag && typeof tag.property === 'string' && typeof tag.content === 'string'
          ? <meta property={tag.property} content={tag.content} key={`og-${index}`} />
          : null
      ))}
      {seo.twitter.map((tag, index) => (
        tag && typeof tag.name === 'string' && typeof tag.content === 'string'
          ? <meta name={tag.name} content={tag.content} key={`twitter-${index}`} />
          : null
      ))}
      {seo.jsonLd.map((value, index) => (
        value && typeof value === 'object'
          ? <script
              type="application/ld+json"
              key={`jsonld-${index}`}
              dangerouslySetInnerHTML={{ __html: serializeJsonLd(value) }}
            />
          : null
      ))}
    </Head>
  );
}
