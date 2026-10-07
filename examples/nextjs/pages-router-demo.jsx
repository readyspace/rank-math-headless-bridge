import SeoHead from './SeoHead.jsx';
import { getHeadSeo } from './get-head-seo.mjs';

// Demonstration only: copy into a Pages Router page and adjust import paths.
// Your real article route should use the ID from its existing published content query.
export async function getServerSideProps() {
  const postId = Number(process.env.EXAMPLE_WORDPRESS_POST_ID);
  const seo = await getHeadSeo(postId);
  return { props: { seo } };
}

export default function MetadataDemo({ seo }) {
  return (
    <>
      <SeoHead seo={seo} />
      <main>
        <h1>Headless metadata example</h1>
        <p>This example renders the SEO data for WordPress post {seo.postId}.</p>
        <p>Your actual page should render the corresponding published article content here.</p>
      </main>
    </>
  );
}
