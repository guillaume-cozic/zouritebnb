// One-off check: log in as the demo host, open the edit page of her first
// accommodation and screenshot the pricing card containing the extra services block.
import { chromium } from 'playwright';

const FRONT = process.env.QA_FRONT_URL ?? 'http://localhost:3000';
const API = process.env.QA_API_URL ?? 'http://localhost:8080';

const login = await fetch(`${API}/api/login`, {
  method: 'POST',
  headers: { 'Content-Type': 'application/ld+json' },
  body: JSON.stringify({ email: 'marie.hote@example.com', password: 'password' }),
});
if (!login.ok) throw new Error(`login failed: ${login.status} ${await login.text()}`);
const auth = await login.json();
const token = auth.token ?? auth.jwt ?? auth.accessToken;

const mine = await fetch(`${API}/api/my-accommodations`, {
  headers: { Authorization: `Bearer ${token}`, Accept: 'application/ld+json' },
});
const data = await mine.json();
const first = (data['hydra:member'] ?? data.member ?? [])[0];
if (!first) throw new Error('no accommodation for host');
console.log('accommodation:', first.id, first.title);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 2000 } });
await page.goto(FRONT);
await page.evaluate(([t, u]) => {
  localStorage.setItem('auth.token', t);
  localStorage.setItem('auth.user', JSON.stringify(u));
}, [token, auth.user ?? { email: 'marie.hote@example.com' }]);
page.on('console', (m) => { if (m.type() === 'error') console.log('console.error:', m.text()); });
page.on('pageerror', (e) => console.log('pageerror:', e.message));

await page.goto(`${FRONT}/accommodations/${first.id}/edit`, { waitUntil: 'networkidle' });
await page.waitForTimeout(1500);

const title = page.getByText('Services supplémentaires', { exact: false }).first();
const found = await title.count();
console.log('extraServices block found:', found > 0);
const navEntry = page.locator('nav button', { hasText: 'Services' }).first();
console.log('sidebar entry found:', (await navEntry.count()) > 0);
if (found > 0) {
  await title.scrollIntoViewIfNeeded();
  await page.waitForTimeout(300);
  // Add a service to reveal the row with the billed toggle.
  await page.getByRole('button', { name: 'Ajouter un service' }).click();
  await page.waitForTimeout(300);
  const toggle = page.locator('button[role="switch"][aria-label*="Facturé"]').first();
  console.log('billed toggle found:', (await toggle.count()) > 0);
}
await page.screenshot({ path: 'qa-report/extra-services-check.png', fullPage: false });
await browser.close();
