/**
 * One-off: lifts the real menu and services out of the Next.js content file
 * into Laravel config, so the 48 dishes and 7 services are never retyped.
 */
const fs = require('fs');

const src = fs.readFileSync('web/src/lib/content.ts', 'utf8');

const php = (v, ind = 0) => {
  const p = '    '.repeat(ind);
  if (Array.isArray(v)) {
    if (!v.length) return '[]';
    return '[\n' + v.map((x) => p + '    ' + php(x, ind + 1)).join(',\n') + ',\n' + p + ']';
  }
  if (v && typeof v === 'object') {
    return (
      '[\n' +
      Object.entries(v)
        .map(([k, x]) => p + '    ' + "'" + k + "' => " + php(x, ind + 1))
        .join(',\n') +
      ',\n' + p + ']'
    );
  }
  if (typeof v === 'string') {
    return "'" + v.split('\\').join('\\\\').split("'").join("\\'") + "'";
  }
  if (typeof v === 'boolean') return v ? 'true' : 'false';
  if (v === null || v === undefined) return 'null';
  return String(v);
};

const imgSlug = (k) => k.replace(/([A-Z])/g, '-$1').toLowerCase();

/**
 * Rewrites double-quoted TS strings as single-quoted ones, escaping any
 * apostrophe inside first. A blanket quote swap silently truncates any string
 * containing a word like "don't", which is how a whole service went missing.
 */
const normalise = (text) =>
  text.replace(/"((?:[^"\\]|\\.)*)"/g, (_, inner) => "'" + inner.split("'").join("\\'") + "'");

/** Matches a single-quoted string allowing escaped apostrophes inside. */
const STR = "'((?:[^'\\\\]|\\\\.)*)'";
const unescape = (s) => s.split("\\'").join("'");

/* ------------------------------- MENU ---------------------------------- */
const menuBlock = normalise(
  src.slice(src.indexOf('export const menu'), src.indexOf('export const dietaryKey'))
);

const menu = [];
const catRe = new RegExp(
  `id: ${STR},\\s*n: ${STR},\\s*title: ${STR},\\s*sub: ${STR},\\s*blurb:\\s*${STR},\\s*image: img\\.(\\w+),\\s*accent: img\\.(\\w+),\\s*dishes: \\[([\\s\\S]*?)\\n    \\],`,
  'g'
);

let m;
while ((m = catRe.exec(menuBlock))) {
  const dishes = [];
  const dRe = new RegExp(
    `\\{ name: ${STR}, desc: ${STR}(?:, tags: \\[([^\\]]*)\\])?(?:, signature: (true|false))? \\}`,
    'g'
  );
  let d;
  while ((d = dRe.exec(m[8]))) {
    dishes.push({
      name: unescape(d[1]),
      desc: unescape(d[2]),
      tags: d[3] ? d[3].split(',').map((t) => t.trim().split("'").join('')).filter(Boolean) : [],
      signature: d[4] === 'true',
    });
  }
  menu.push({
    id: m[1], n: m[2], title: unescape(m[3]), sub: unescape(m[4]), blurb: unescape(m[5]),
    image: imgSlug(m[6]), accent: imgSlug(m[7]), dishes,
  });
}

/* ----------------------------- SERVICES -------------------------------- */
const svcBlock = normalise(
  src.slice(src.indexOf('export const services'), src.indexOf('BRAND STORY'))
);

const services = [];
const sRe = new RegExp(
  `slug: ${STR},\\s*index: ${STR},\\s*title: ${STR},\\s*short: ${STR},\\s*lede: ${STR},\\s*body: \\[([\\s\\S]*?)\\],\\s*image: img\\.(\\w+),\\s*gallery: \\[([^\\]]+)\\],\\s*detail: ${STR},[\\s\\S]*?scale: ${STR},`,
  'g'
);

let s2;
while ((s2 = sRe.exec(svcBlock))) {
  const body = [...s2[6].matchAll(new RegExp(STR, 'g'))].map((x) => unescape(x[1]));
  const gallery = [...s2[8].matchAll(/img\.(\w+)/g)].map((x) => imgSlug(x[1]));
  services.push({
    slug: s2[1], index: s2[2], title: unescape(s2[3]), short: unescape(s2[4]),
    lede: unescape(s2[5]), body, image: imgSlug(s2[7]), gallery,
    detail: unescape(s2[9]), scale: unescape(s2[10]),
  });
}

fs.writeFileSync(
  'config/menu.php',
  "<?php\n\n/**\n * Midland Catering's real menu, transcribed from their own menu board:\n * 48 dishes across 6 sections. No prices — every event is quoted on numbers.\n */\nreturn " + php(menu) + ";\n"
);

fs.writeFileSync(
  'config/catering_services.php',
  "<?php\n\n/**\n * The seven occasions the client confirmed they cater for.\n */\nreturn " + php(services) + ";\n"
);

console.log('menu sections:', menu.length, '| dishes:', menu.reduce((a, c) => a + c.dishes.length, 0));
console.log('services:', services.length);
