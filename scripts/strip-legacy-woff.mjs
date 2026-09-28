/**
 * The Laravel fonts plugin emits a second @font-face for woff *after* the woff2
 * one. Same family/weight/style means the later rule wins, so browsers that
 * support both still download the larger woff — roughly 120KB of dead weight
 * per page load. Nothing we support lacks woff2, so the legacy blocks come out
 * of the manifest, the stylesheet and the build directory.
 */
import { readFileSync, writeFileSync, existsSync, readdirSync, rmSync } from 'node:fs';
import { resolve } from 'node:path';

const BUILD = resolve(process.cwd(), 'public/build');
const manifestPath = resolve(BUILD, 'fonts-manifest.json');

if (!existsSync(manifestPath)) {
    console.log('no fonts manifest — nothing to strip');
    process.exit(0);
}

const strip = (css) => css.replace(/@font-face\s*\{[^}]*format\("woff"\)[^}]*\}\s*/g, '');
const count = (css) => (css.match(/format\("woff"\)/g) || []).length;

const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
let removed = 0;

if (manifest.style?.familyStyles) {
    for (const [family, css] of Object.entries(manifest.style.familyStyles)) {
        removed += count(css);
        manifest.style.familyStyles[family] = strip(css);
    }
}

if (manifest.style?.inline) {
    manifest.style.inline = strip(manifest.style.inline);
}

writeFileSync(manifestPath, JSON.stringify(manifest, null, 2));

if (manifest.style?.file) {
    const cssPath = resolve(BUILD, manifest.style.file);
    if (existsSync(cssPath)) writeFileSync(cssPath, strip(readFileSync(cssPath, 'utf8')));
}

let deleted = 0;
const assets = resolve(BUILD, 'assets');

if (existsSync(assets)) {
    for (const file of readdirSync(assets)) {
        if (file.endsWith('.woff')) {
            rmSync(resolve(assets, file));
            deleted++;
        }
    }
}

console.log(`stripped ${removed} legacy woff faces, deleted ${deleted} files`);
