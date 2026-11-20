#!/usr/bin/env node
/**
 * Builds all OpenConsent packages into dist/ (no dependencies, Node 18+):
 *
 *   dist/openconsent-wordpress-X.Y.Z.zip  WordPress plugin (top-level folder "openconsent")
 *   dist/openconsent-joomla-X.Y.Z.zip     Joomla 5/6 system plugin
 *   dist/openconsent_X.Y.Z.zip            TYPO3 12-14 extension (name required by the Extension Manager)
 *   dist/openconsent-X.Y.Z.js             universal snippet (head script + styles + banner in one file)
 *
 * The shared frontend files in core/ are copied into each package.
 * Fails if the versions in wordpress/openconsent.php, joomla/openconsent.xml and
 * typo3/ext_emconf.php differ, or (with --tag vX.Y.Z) if they do not match the release tag.
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
    'typo3/ext_emconf.php': read('typo3/ext_emconf.php').match(/'version'\s*=>\s*'([^']+)'/)?.[1],
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

const typo3Dir = join(dist, 'typo3');
cpSync(join(root, 'typo3'), typo3Dir, { recursive: true });
mkdirSync(join(typo3Dir, 'Resources/Public/JavaScript'), { recursive: true });
mkdirSync(join(typo3Dir, 'Resources/Public/Css'), { recursive: true });
cpSync(join(root, 'core/openconsent.js'), join(typo3Dir, 'Resources/Public/JavaScript/openconsent.js'));
cpSync(join(root, 'core/openconsent-head.js'), join(typo3Dir, 'Resources/Public/JavaScript/openconsent-head.js'));
cpSync(join(root, 'core/openconsent.css'), join(typo3Dir, 'Resources/Public/Css/openconsent.css'));

// File names carry the version, e.g. openconsent-wordpress-1.3.0.zip.
// The TYPO3 Extension Manager reads the extension key from the file name, so that one must be openconsent_X.Y.Z.zip.
const files = {
    wordpress: `openconsent-wordpress-${version}.zip`,
    joomla: `openconsent-joomla-${version}.zip`,
    typo3: `openconsent_${version}.zip`,
    snippet: `openconsent-${version}.js`,
};

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
writeFileSync(join(dist, files.snippet), snippet);

// --- Zips ---
let hasZip = true;
try {
    execFileSync('zip', ['-v'], { stdio: 'ignore' });
} catch {
    hasZip = false;
}
if (hasZip) {
    execFileSync('zip', ['-qr', `../${files.wordpress}`, 'openconsent'], { cwd: join(dist, 'wordpress') });
    execFileSync('zip', ['-qr', `../${files.joomla}`, '.'], { cwd: joomlaDir });
    execFileSync('zip', ['-qr', `../${files.typo3}`, '.'], { cwd: typo3Dir });
} else {
    console.warn('"zip" not found: built the folders in dist/ only.');
}

console.log(`\nBuilt OpenConsent ${version}:`);
for (const file of Object.values(files)) {
    if (existsSync(join(dist, file))) console.log(`  dist/${file}`);
}
