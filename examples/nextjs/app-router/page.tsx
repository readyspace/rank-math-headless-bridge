import { notFound } from 'next/navigation';
import { getContent } from './get-content';
import { toNextMetadata } from './metadata';
import { HeadlessSeoTags } from './HeadlessSeoTags';

// Copy to an appropriate app/[...slug]/page.tsx and adjust component imports.
type Props = { params: Promise<{ slug: string[] }> };
async function loadPage({ params }: Props) {
  const { slug } = await params;
  // Default assumes frontend and CMS share paths. Supply your explicit reverse route mapping.
  return getContent(`/${slug.map(encodeURIComponent).join('/')}/`);
}

export async function generateMetadata(props: Props) {
  const page = await loadPage(props);
  if (!page) notFound();
  return toNextMetadata(page.seo);
}

export default async function Page(props: Props) {
  const page = await loadPage(props);
  if (!page) notFound();
  return <>
    <HeadlessSeoTags seo={page.seo} />
    <main><h1>{page.title}</h1></main>
  </>;
}
