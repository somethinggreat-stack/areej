/**
 * Builds every image the site serves from the untouched originals in
 * storage/app/original-img.
 *
 *   public/img/<name>.jpg            capped at 1800px, used as the last-resort
 *                                    src and by the gallery lightbox
 *   public/img/<name>.webp           same, for anything that wants one file
 *   public/img/r/<name>-<w>.{jpg,webp}  the srcset ladder <x-site.img> emits
 *
 * Drop a new photograph into storage/app/original-img and re-run:
 *
 *   npm run images
 *
 * Safe to run repeatedly — it always reads the originals, never its own output.
 */
import sharp from 'sharp';
import { readdirSync, mkdirSync, statSync, existsSync, renameSync, copyFileSync } from 'node:fs';
import { join, resolve } from 'node:path';

const ORIGINALS = resolve(process.cwd(), 'storage/app/original-img');
const PUBLIC = resolve(process.cwd(), 'public/img');
const RESPONSIVE = join(PUBLIC, 'r');

const MAX_WIDTH = 1800;
const WIDTHS = [480, 800, 1200, 1800];
const JPEG = { quality: 74, mozjpeg: true, progressive: true };
const WEBP = { quality: 72, effort: 5 };

mkdirSync(ORIGINALS, { recursive: true });
mkdirSync(RESPONSIVE, { recursive: true });

// First run: whatever is already in public/img is the original.
for (const file of readdirSync(PUBLIC)) {
    if (!/\.jpe?g$/i.test(file)) continue;
    if (!existsSync(join(ORIGINALS, file))) renameSync(join(PUBLIC, file), join(ORIGINALS, file));
}

const files = readdirSync(ORIGINALS).filter((f) => /\.jpe?g$/i.test(f));
let originalBytes = 0;
let builtBytes = 0;

for (const file of files) {
    const base = file.replace(/\.jpe?g$/i, '');
    const src = join(ORIGINALS, file);
    const meta = await sharp(src).metadata();
    const full = Math.min(meta.width || MAX_WIDTH, MAX_WIDTH);

    originalBytes += statSync(src).size;

    await sharp(src).resize({ width: full, withoutEnlargement: true })
        .jpeg(JPEG).toFile(join(PUBLIC, `${base}.jpg`));
    await sharp(src).resize({ width: full, withoutEnlargement: true })
        .webp(WEBP).toFile(join(PUBLIC, `${base}.webp`));

    builtBytes += statSync(join(PUBLIC, `${base}.jpg`)).size;

    for (const width of WIDTHS) {
        if (width > (meta.width || 0) * 1.05) continue;   // never upscale

        await sharp(src).resize({ width, withoutEnlargement: true })
            .jpeg(JPEG).toFile(join(RESPONSIVE, `${base}-${width}.jpg`));
        await sharp(src).resize({ width, withoutEnlargement: true })
            .webp(WEBP).toFile(join(RESPONSIVE, `${base}-${width}.webp`));
    }

    process.stdout.write('.');
}

/* The logo is a flat brand mark and never renders above 132 CSS px. */
const logo = join(ORIGINALS, 'midland-logo.png');
if (!existsSync(logo) && existsSync(join(PUBLIC, 'midland-logo.png'))) {
    copyFileSync(join(PUBLIC, 'midland-logo.png'), logo);
}
if (existsSync(logo)) {
    await sharp(logo).resize({ width: 320, withoutEnlargement: true })
        .png({ compressionLevel: 9, palette: true }).toFile(join(PUBLIC, 'midland-logo.png.tmp'));
    renameSync(join(PUBLIC, 'midland-logo.png.tmp'), join(PUBLIC, 'midland-logo.png'));

    await sharp(logo).resize({ width: 320, withoutEnlargement: true })
        .webp({ quality: 88 }).toFile(join(PUBLIC, 'midland-logo.webp'));
}

console.log(
    `\n${files.length} photographs, ${readdirSync(RESPONSIVE).length} derivatives.\n` +
    `full-size jpg: ${(originalBytes / 1048576).toFixed(1)}MB in -> ${(builtBytes / 1048576).toFixed(1)}MB out`
);
