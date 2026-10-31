#!/usr/bin/env node

// Upstream: https://github.com/emulsify-ds/emulsify-ui-kit/pull/189 https://github.com/emulsify-ds/emulsify-core/pull/322 https://github.com/emulsify-ds/emulsify-drupal/pull/385
// Remove when UI Kit and Core releases pass lint and build without this.
import { createRequire } from 'node:module';
import { readFileSync, readdirSync, writeFileSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { execFileSync } from 'node:child_process';

// The check-only pass runs before system installation can touch theme source.
const checkOnly = process.argv[2] === '--check';
const directory = resolve(process.argv[checkOnly ? 3 : 2]);
const metadata = JSON.parse(readFileSync(join(directory, 'project.emulsify.json'), 'utf8'));
if (metadata.project?.generatedFrom !== 'emulsify-drupal' || !metadata.project.generatedFromVersion?.startsWith('7.')) {
  throw new Error('Theme initialization requires an Emulsify Drupal 7 starter.');
}
if (checkOnly) process.exit(0);
// Full normalization is tied to the reviewed UI Kit and Core versions.
if (metadata.system?.checkout !== '1.9.0') throw new Error('Theme initialization requires the reviewed UI Kit 1.9.0 source.');
const manifestPath = join(directory, 'package.json');
const manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
// Use the parser and formatter installed with this theme's Core toolchain.
const require = createRequire(manifestPath);
if (!JSON.parse(readFileSync(join(directory, 'node_modules/@emulsify/core/package.json'), 'utf8')).version.startsWith('4.')) {
  throw new Error('Theme initialization requires the reviewed Emulsify Core 4 toolchain.');
}
const scss = require('postcss-scss'); // Already required by Core's Stylelint config.

// Stop on the first lint failure before the next lint or formatting phase runs.
manifest.scripts.lint = 'npm run lint-js && npm run lint-styles';
manifest.scripts['lint-fix'] = 'npm run lint-js -- --fix && npm run lint-styles -- --fix';
manifest.scripts.format = 'npm run lint-fix && npm run prettier-fix';
// Core's generated output would otherwise be linted as authored source.
for (const output of ['dist', '.out', '.coverage', 'components']) {
  const flag = `--ignore-pattern '${output}/**'`;
  for (const task of ['lint-js', 'lint-styles']) {
    if (!manifest.scripts[task].includes(flag)) manifest.scripts[task] += ` ${flag}`;
  }
}
writeFileSync(manifestPath, `${JSON.stringify(manifest, null, 2)}\n`);
// The component mirror is build output, so formatting should target its source.
const ignorePath = join(directory, 'config/emulsify-core/.prettierignore');
const ignores = readFileSync(ignorePath, 'utf8').trimEnd().split('\n');
if (!ignores.includes('/components/')) ignores.push('/components/');
writeFileSync(ignorePath, `${ignores.join('\n')}\n`);

// Normalize copied UI Kit SCSS before its first lint and build.
function normalize(folder) {
  for (const entry of readdirSync(folder, { withFileTypes: true })) {
    if (entry.isSymbolicLink()) throw new Error(`Unexpected source symlink: ${join(folder, entry.name)}`);
    const file = join(folder, entry.name);
    if (entry.isDirectory()) normalize(file);
    else if (entry.name.endsWith('.scss')) {
      const source = readFileSync(file, 'utf8');
      const tree = scss.parse(source, { from: file });
      // Sass's current mixed-declaration semantics preserve this exact order.
      const wrappers = [];
      tree.walkRules((rule) => { if (rule.selector === '&') wrappers.push(rule); });
      for (const rule of wrappers.reverse()) rule.replaceWith(...rule.nodes);
      tree.walkDecls((declaration) => {
        if (declaration.prop === 'word-wrap') declaration.prop = 'overflow-wrap';
        if (declaration.prop === 'clip' && declaration.value === 'rect(1px, 1px, 1px, 1px)') {
          declaration.prop = 'clip-path';
          declaration.value = 'inset(50%)';
        }
        // Both declarations preserve the deprecated value's wrapping behavior.
        if (declaration.prop === 'word-break' && declaration.value === 'break-word') {
          declaration.value = 'normal';
          declaration.cloneAfter({ prop: 'overflow-wrap', value: 'anywhere' });
        }
      });
      writeFileSync(file, tree.toResult({ stringifier: scss.stringify }).css);
    }
  }
}
normalize(join(directory, 'src'));
// The Drupal variant omits the example SVGs.
// Its Twig already uses the shipped star icon.
const announcement = join(directory, 'src/components/announcement/announcement.stories.js');
writeFileSync(announcement, readFileSync(announcement, 'utf8').replaceAll(
  /\.\.\/\.\.\/\.\.\/assets\/images\/example\/announcement-icon--(?:dark|light)\.svg/g,
  '../../../assets/icons/star-solid.svg',
));
// Finish with the installed tools so the first build receives lintable source.
for (const task of ['lint-styles', 'prettier-fix']) {
  execFileSync('npm', ['run', task, ...(task === 'lint-styles' ? ['--', '--fix'] : [])], {
    cwd: directory, stdio: 'inherit',
  });
}
