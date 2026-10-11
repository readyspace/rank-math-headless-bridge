import { normalizeHeadlessSeo, toNextMetadata } from '../../../../examples/nextjs/app-router/metadata';
import { HeadlessSeoTags } from '../../../../examples/nextjs/app-router/HeadlessSeoTags';
import { seoFixture } from '../../seo';

const seo = normalizeHeadlessSeo(seoFixture);
export function generateMetadata() { return toNextMetadata(seo); }
export default function Page() {
  return <><HeadlessSeoTags seo={seo} /><main><h1>Bridge build fixture</h1></main></>;
}
