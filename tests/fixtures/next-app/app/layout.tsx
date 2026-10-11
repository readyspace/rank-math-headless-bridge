import type { Metadata } from 'next';
import type { ReactNode } from 'react';

export const metadata: Metadata = {
  title: { default: 'Parent', template: '%s | Parent suffix' },
  openGraph: { title: 'Parent OG' }, twitter: { title: 'Parent Twitter' },
};
export default function Layout({ children }: { children: ReactNode }) {
  return <html lang="en"><body>{children}</body></html>;
}
