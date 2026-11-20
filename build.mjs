#!/usr/bin/env node
/**
 * Builds all OpenConsent packages into dist/ (no dependencies, Node 18+):
 *
 *   dist/openconsent-wordpress.zip  WordPress plugin (top-level folder "openconsent")
 *   dist/openconsent-joomla.zip     Joomla 5/6 system plugin
 *   dist/openconsent.js             universal snippet (head script + styles + banner in one file)
 *
 * The shared frontend files in core/ are copied into each package.
 * Fails if the versions in wordpress/openconsent.php and joomla/openconsent.xml differ,
 * or (with --tag vX.Y.Z) if they do not match the release tag.
 * Zips are created with the "zip" command; without it, only the folders in dist/ are built.
 */
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = dirname(fileURLToPath(import.meta.url));
const dist = join(root, 'dist');
const read = (path) => readFileSync(join(root, path), 'utf8');

function fail(message) {
    console.error(`::error::${message}`);
    process.exit(1);
}

// --- Versions ---
const wpMain = read('wordpress/openconsent.php');
const versions = {
    'wordpress/openconsent.php (header)': wpMain.match(/^\s*\*\s*Version:\s*(\S+)/m)?.[1],
    'wordpress/openconsent.php (OC_VERSION)': wpMain.match(/define\(\s*'OC_VERSION',\s*'([^']+)'/)?.[1],
    'joomla/openconsent.xml': read('joomla/openconsent.xml').match(/<version>([^<]+)<\/version>/)?.[1],
};
const tagIndex = process.argv.indexOf('--tag');
if (tagIndex !== -1) {
    versions['release tag'] = (process.argv[tagIndex + 1] || '').replace(/^v/, '');
}
const version = versions['wordpress/openconsent.php (header)'];
for (const [source, value] of Object.entries(versions)) {
    console.log(`${source}: ${value}`);
    if (!value || value !== version) {
        fail(`Version mismatch: ${Object.entries(versions).map(([s, v]) => `${s} = ${v}`).join(', ')}`);
    }
}

// --- Packages ---
rmSync(dist, { recursive: true, force: true });

const wpDir = join(dist, 'wordpress', 'openconsent');
cpSync(join(root, 'wordpress'), wpDir, { recursive: true });
mkdirSync(join(wpDir, 'css'), { recursive: true });
cpSync(join(root, 'core/openconsent.js'), join(wpDir, 'js/openconsent.js'));
cpSync(join(root, 'core/openconsent-head.js'), join(wpDir, 'js/openconsent-head.js'));
cpSync(join(root, 'core/openconsent.css'), join(wpDir, 'css/openconsent.css'));

const joomlaDir = join(dist, 'joomla');
cpSync(join(root, 'joomla'), joomlaDir, { recursive: true });
mkdirSync(join(joomlaDir, 'media/js'), { recursive: true });
mkdirSync(join(joomlaDir, 'media/css'), { recursive: true });
cpSync(join(root, 'core/openconsent.js'), join(joomlaDir, 'media/js/openconsent.js'));
cpSync(join(root, 'core/openconsent-head.js'), join(joomlaDir, 'media/js/openconsent-head.js'));
cpSync(join(root, 'core/openconsent.css'), join(joomlaDir, 'media/css/openconsent.css'));

// --- Universal snippet ---
const css = read('core/openconsent.css').replace(/\r\n/g, '\n');
const snippet = [
    `/*! OpenConsent ${version} | universal snippet | GPL-2.0-or-later | https://github.com/vidual-labs/openconsent */`,
    '/*',
    ' * Load this file in <head>, before any Google tag, without async or defer,',
    ' * after window.openconsentConfig is set (see README).',
    ' */',
    read('core/openconsent-head.js').replace(/\r\n/g, '\n').trim(),
    '',
    '/* OpenConsent banner styles */',
    '(function (d) {',
    "    if (d.getElementById('openconsent-css')) return;",
    "    var s = d.createElement('style');",
    "    s.id = 'openconsent-css';",
    `    s.textContent = ${JSON.stringify(css)};`,
    '    (d.head || d.documentElement).appendChild(s);',
    '})(document);',
    '',
    read('core/openconsent.js').replace(/\r\n/g, '\n').trim(),
    '',
].join('\n');
writeFileSync(join(dist, 'openconsent.js'), snippet);

// --- Zips ---
let hasZip = true;
try {
    execFileSync('zip', ['-v'], { stdio: 'ignore' });
} catch {
    hasZip = false;
}
if (hasZip) {
    execFileSync('zip', ['-qr', '../openconsent-wordpress.zip', 'openconsent'], { cwd: join(dist, 'wordpress') });
    execFileSync('zip', ['-qr', '../openconsent-joomla.zip', '.'], { cwd: joomlaDir });
} else {
    console.warn('"zip" not found: built the folders in dist/ only.');
}

console.log(`\nBuilt OpenConsent ${version}:`);
for (const file of ['openconsent-wordpress.zip', 'openconsent-joomla.zip', 'openconsent.js']) {
    if (existsSync(join(dist, file))) console.log(`  dist/${file}`);
}
