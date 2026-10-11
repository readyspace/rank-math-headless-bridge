export const seoFixture = {
  postId: 123, title: 'Article | Rank Math', description: 'A & B <quoted>',
  canonical: 'https://www.example.com/article/',
  robots: { robots: 'noindex, follow, max-image-preview:large', googlebot: 'noindex, follow', bingbot: 'noindex' },
  openGraph: [
    { property: 'og:type', content: 'article' },
    { property: 'og:title', content: 'Article | Rank Math' },
    { property: 'og:url', content: 'https://www.example.com/article/' },
    { property: 'og:image', content: 'https://cms.example.com/uploads/first.jpg' },
    { property: 'og:image:width', content: '1200' },
    { property: 'og:image', content: 'https://cdn.example.com/second.jpg' },
    { property: 'article:published_time', content: '2026-10-11T00:00:00Z' },
  ],
  twitter: [{ name: 'twitter:card', content: 'summary_large_image' }, { name: 'twitter:title', content: 'Social title' }],
  jsonLd: [{ '@context': 'https://schema.org', '@type': 'Article', description: '</script><script>alert(1)</script>' }],
};
