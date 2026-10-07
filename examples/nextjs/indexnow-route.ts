// Optional App Router example for Next.js versions with asynchronous route params.
// Copy to app/[indexnowKey]/route.ts only if this does not collide with existing routes.
// Prefer a static public/<CURRENT_KEY>.txt file when possible.
export async function GET(
  _request: Request,
  { params }: { params: Promise<{ indexnowKey: string }> },
) {
  const key = process.env.INDEXNOW_KEY || '';
  const { indexnowKey } = await params;
  if (!/^[a-zA-Z0-9-]{8,128}$/.test(key) || indexnowKey !== `${key}.txt`) {
    return new Response('Not found', { status: 404 });
  }
  return new Response(`${key}\n`, {
    status: 200,
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
      'Cache-Control': 'no-store',
      'X-Content-Type-Options': 'nosniff',
    },
  });
}
