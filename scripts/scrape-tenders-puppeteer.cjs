#!/usr/bin/env node
/* eslint-disable no-console */

const SOURCE_DEFAULT =
  'https://bidassist.com/all-tenders/active?filter=CATEGORY:Scraps&filter=LOCATION_STRING:Kerala&sort=RELEVANCE:DESC&pageNumber=0&pageSize=10&tenderType=ACTIVE&tenderEntity=TENDER_LISTING&year=2026&removeUnavailableTenderAmountCards=false&removeUnavailableEmdCards=false';

const sourceUrl = process.argv[2] || SOURCE_DEFAULT;

function findTenderItemsInTree(node) {
  if (!node || typeof node !== 'object') return [];
  const keys = ['tenders', 'tenderList', 'tender_listing', 'results', 'items', 'data'];
  for (const key of keys) {
    const val = node[key];
    if (Array.isArray(val) && val.length && typeof val[0] === 'object') {
      const first = val[0] || {};
      const known = ['title', 'tender_title', 'tenderTitle', 'authority', 'organisation', 'closing_date', 'submission_end_date'];
      if (known.some((k) => Object.prototype.hasOwnProperty.call(first, k))) {
        return val;
      }
    }
  }
  for (const value of Object.values(node)) {
    if (value && typeof value === 'object') {
      const nested = findTenderItemsInTree(value);
      if (nested.length) return nested;
    }
  }
  return [];
}

function normalizeTender(item, idx) {
  const pick = (...keys) => {
    for (const k of keys) {
      const v = item[k];
      if (v !== undefined && v !== null && v !== '') return String(v);
    }
    return '';
  };
  return {
    sl_no: idx + 1,
    title: pick('title', 'tender_title', 'tenderTitle', 'name'),
    authority: pick('authority', 'organisation', 'organization', 'department'),
    location: pick('location', 'city', 'state'),
    closing_date: pick('submission_end_date', 'closing_date', 'closingDate', 'bid_end_date'),
    closing_label: pick('closing_label', 'closing_type'),
    tender_value: pick('tender_value', 'value', 'estimate_value', 'tender_amount'),
    type: pick('type', 'tender_type', 'procurement_type'),
    category: pick('category', 'tender_category'),
    platform: pick('platform', 'source'),
    description: pick('description', 'brief', 'tender_description'),
    url: pick('url', 'detail_url', 'link'),
    phone_number: ''
  };
}

function filterKeralaScraps(items) {
  const filtered = (items || []).filter((t) => {
    const title = String(t.title || '').toLowerCase();
    const loc = String(t.location || '').toLowerCase();
    const desc = String(t.description || '').toLowerCase();
    const cat = String(t.category || '').toLowerCase();
    const url = String(t.url || '').toLowerCase();
    if (!title) return false;
    if (url.includes('/global-tenders/')) return false;
    const hasKerala = title.includes('kerala') || loc.includes('kerala') || desc.includes('kerala');
    const hasScrap = title.includes('scrap') || desc.includes('scrap') || cat.includes('scrap');
    return hasKerala && hasScrap;
  });

  const seen = new Set();
  const deduped = [];
  for (const t of filtered) {
    const key = `${t.title}|${t.closing_date}|${t.location}|${t.url}`;
    if (!seen.has(key)) {
      seen.add(key);
      deduped.push(t);
    }
  }
  return deduped.slice(0, 10).map((t, i) => ({ ...t, sl_no: i + 1 }));
}

async function main() {
  let puppeteer;
  try {
    puppeteer = require('puppeteer');
  } catch (_e) {
    console.log(JSON.stringify({
      status: 'error',
      msg: 'puppeteer package not installed. Run: npm install puppeteer',
      data: []
    }));
    process.exit(0);
  }

  let browser;
  try {
    browser = await puppeteer.launch({
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    const page = await browser.newPage();
    await page.setUserAgent('Mozilla/5.0 (compatible; ScrapmateAdmin/1.0)');
    await page.goto(sourceUrl, { waitUntil: 'networkidle2', timeout: 60000 });

    const result = await page.evaluate(() => {
      const nextEl = document.querySelector('#__NEXT_DATA__');
      let nextData = null;
      if (nextEl && nextEl.textContent) {
        try {
          nextData = JSON.parse(nextEl.textContent);
        } catch (_e) {
          nextData = null;
        }
      }
      return { nextData };
    });

    const rawItems = findTenderItemsInTree(result.nextData || {});
    const normalized = rawItems.map((x, i) => normalizeTender(x, i));
    const data = filterKeralaScraps(normalized);

    console.log(JSON.stringify({
      status: 'success',
      msg: 'Puppeteer scrape completed',
      data
    }));
  } catch (e) {
    console.log(JSON.stringify({
      status: 'error',
      msg: e && e.message ? e.message : 'Puppeteer scrape failed',
      data: []
    }));
  } finally {
    if (browser) {
      try {
        await browser.close();
      } catch (_e) {
        // ignore
      }
    }
  }
}

main();

