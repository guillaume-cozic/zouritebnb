// QA crawler — drives a real Chromium over the front app for each persona
// (anonymous / guest / host), visiting every route and collecting runtime
// signals: console errors, uncaught exceptions, failed network requests,
// broken images, missing alt text, plus a full-page screenshot per page.
//
// Output: $QA_OUT/report.json  (machine-readable findings)
//         $QA_OUT/<persona>__<slug>.png  (one screenshot per visited page)
//
// Run:  node crawl.mjs   (see SKILL.md for the full procedure)

import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'node:fs';

const FRONT = process.env.QA_FRONT_URL || 'http://localhost:3000';
const API = process.env.QA_API_URL || 'http://localhost:8080';
const OUT = process.env.QA_OUT || './qa-report';
const PASSWORD = process.env.QA_PASSWORD || 'password';
const NAV_TIMEOUT = Number(process.env.QA_NAV_TIMEOUT || 20000);

// Guest defaults to a crawler-managed throwaway account so QA never depends on
// the seed state of a real user. Override with a seeded account for richer data.
const GUEST_EMAIL = process.env.QA_GUEST_EMAIL || 'qa.voyageur@example.com';
const HOST_EMAIL = process.env.QA_HOST_EMAIL || 'marie.hote@example.com';

const slug = (s) => s.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '') || 'root';

// --- resolve sample resource ids from the public API ----------------------
async function fetchJson(path) {
  const res = await fetch(`${API}${path}`, { headers: { Accept: 'application/ld+json' } });
  if (!res.ok) throw new Error(`${path} -> ${res.status}`);
  return res.json();
}
function firstId(collection) {
  const members = collection?.['hydra:member'] ?? collection?.member ?? collection ?? [];
  const m = Array.isArray(members) ? members[0] : null;
  if (!m) return null;
  return m.id ?? (typeof m['@id'] === 'string' ? m['@id'].split('/').pop() : null);
}

// --- auth: replicate what the app stores in localStorage ------------------
async function login(email) {
  const res = await fetch(`${API}/api/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/ld+json', Accept: 'application/ld+json' },
    body: JSON.stringify({ email, password: PASSWORD }),
  });
  if (!res.ok) throw new Error(`login ${email} -> ${res.status} ${await res.text()}`);
  return res.json(); // AuthUser, includes .token
}

async function register(email) {
  const res = await fetch(`${API}/api/register`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/ld+json', Accept: 'application/ld+json' },
    body: JSON.stringify({ email, password: PASSWORD }),
  });
  if (!res.ok) throw new Error(`register ${email} -> ${res.status} ${await res.text()}`);
  return res.json();
}

// Login, or register-then-login on first run (used for the throwaway guest).
async function ensureAccount(email) {
  try {
    return await login(email);
  } catch {
    await register(email);
    return login(email);
  }
}

async function authenticate(persona) {
  if (!persona.email) return null;
  return persona.register ? ensureAccount(persona.email) : login(persona.email);
}

async function main() {
  mkdirSync(OUT, { recursive: true });

  let accoId = null;
  let projectId = null;
  try { accoId = firstId(await fetchJson('/api/accommodations')); } catch (e) { console.error('accommodations:', e.message); }
  try { projectId = firstId(await fetchJson('/api/solidarity-projects')); } catch (e) { console.error('projects:', e.message); }

  const anonRoutes = [
    '/',
    '/accommodations',
    accoId && `/accommodations/${accoId}`,
    '/solidarity-projects',
    projectId && `/solidarity-projects/${projectId}`,
    '/login',
    '/register',
  ].filter(Boolean);

  const guestRoutes = [
    ...anonRoutes,
    accoId && `/accommodations/${accoId}/book`,
    '/account',
    '/account/conversations',
    '/account/settings',
    '/account/verification',
  ].filter(Boolean);

  const hostRoutes = [
    '/',
    '/admin',
    '/admin/accommodations',
    '/admin/calendar',
    '/admin/reservations',
    '/admin/team',
    '/admin/conversations',
    '/create',
    accoId && `/accommodations/${accoId}`,
    accoId && `/accommodations/${accoId}/edit`,
    accoId && `/accommodations/${accoId}/photos`,
  ].filter(Boolean);

  const personas = [
    { id: 'anon', email: null, routes: anonRoutes },
    { id: 'guest', email: GUEST_EMAIL, register: true, routes: guestRoutes },
    { id: 'host', email: HOST_EMAIL, routes: hostRoutes },
  ];

  const browser = await chromium.launch();
  const report = { meta: { front: FRONT, api: API, accoId, projectId, generatedAt: null }, pages: [] };

  for (const persona of personas) {
    let auth = null;
    try { auth = await authenticate(persona); } catch (e) {
      report.pages.push({ persona: persona.id, route: '(login)', fatal: e.message });
      continue;
    }

    const context = await browser.newContext({ viewport: { width: 1366, height: 900 } });
    if (auth) {
      await context.addInitScript(({ user }) => {
        localStorage.setItem('auth.user', JSON.stringify(user));
        if (user.token) localStorage.setItem('auth.token', user.token);
      }, { user: auth });
    }

    for (const route of persona.routes) {
      const page = await context.newPage();
      const consoleErrors = [];
      const consoleWarnings = [];
      const pageErrors = [];
      const failedRequests = [];

      page.on('console', (msg) => {
        const t = msg.type();
        if (t === 'error') consoleErrors.push(msg.text());
        else if (t === 'warning') consoleWarnings.push(msg.text());
      });
      page.on('pageerror', (err) => pageErrors.push(String(err?.stack || err)));
      page.on('response', (res) => {
        const s = res.status();
        if (s >= 400) failedRequests.push({ status: s, url: res.url(), method: res.request().method() });
      });

      const entry = { persona: persona.id, route, url: `${FRONT}${route}` };
      try {
        await page.goto(`${FRONT}${route}`, { waitUntil: 'networkidle', timeout: NAV_TIMEOUT });
      } catch (e) {
        entry.navError = e.message;
      }

      try {
        entry.title = await page.title();
        entry.finalUrl = page.url();
        entry.redirected = !page.url().endsWith(route);

        // DOM-level UX/a11y signals
        const dom = await page.evaluate(() => {
          const imgs = Array.from(document.images);
          const broken = imgs.filter((i) => i.complete && i.naturalWidth === 0).map((i) => i.currentSrc || i.src);
          const noAlt = imgs.filter((i) => !i.alt || !i.alt.trim()).length;
          const buttons = Array.from(document.querySelectorAll('button'));
          const namelessButtons = buttons.filter((b) => !b.textContent.trim() && !b.getAttribute('aria-label') && !b.querySelector('img,svg[aria-label]')).length;
          const bodyText = (document.body.innerText || '').slice(0, 400);
          const looksEmpty = (document.body.innerText || '').trim().length < 30;
          // common crash/error markers rendered to the page
          const errorBanner = /une erreur|something went wrong|erreur est survenue|failed to fetch|cannot read|undefined is not/i.test(document.body.innerText || '');
          return { brokenImages: broken, imagesWithoutAlt: noAlt, namelessButtons, looksEmpty, errorBanner, bodyPreview: bodyText };
        });
        Object.assign(entry, dom);
      } catch (e) {
        entry.evalError = e.message;
      }

      entry.consoleErrors = consoleErrors;
      entry.consoleWarnings = consoleWarnings.slice(0, 20);
      entry.pageErrors = pageErrors;
      entry.failedRequests = failedRequests;

      const shot = `${persona.id}__${slug(route)}.png`;
      try {
        await page.screenshot({ path: `${OUT}/${shot}`, fullPage: true });
        entry.screenshot = shot;
      } catch (e) {
        entry.screenshotError = e.message;
      }

      report.pages.push(entry);
      console.log(`[${persona.id}] ${route} — ${consoleErrors.length} err, ${failedRequests.length} net4xx+, ${pageErrors.length} crash`);
      await page.close();
    }
    await context.close();
  }

  await browser.close();

  // headline counts so the reader can triage at a glance
  const summary = { pagesVisited: report.pages.length, pagesWithConsoleErrors: 0, pagesWithPageErrors: 0, pagesWithFailedRequests: 0, pagesWithBrokenImages: 0, pagesWithErrorBanner: 0 };
  for (const p of report.pages) {
    if (p.consoleErrors?.length) summary.pagesWithConsoleErrors++;
    if (p.pageErrors?.length) summary.pagesWithPageErrors++;
    if (p.failedRequests?.length) summary.pagesWithFailedRequests++;
    if (p.brokenImages?.length) summary.pagesWithBrokenImages++;
    if (p.errorBanner) summary.pagesWithErrorBanner++;
  }
  report.summary = summary;

  writeFileSync(`${OUT}/report.json`, JSON.stringify(report, null, 2));
  console.log('\nSummary:', JSON.stringify(summary, null, 2));
  console.log(`Report written to ${OUT}/report.json`);
}

main().catch((e) => { console.error(e); process.exit(1); });
