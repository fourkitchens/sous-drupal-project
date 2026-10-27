#!/usr/bin/env node

// Prove generated-output exclusions do not hide authored-source failures.
import assert from 'node:assert/strict';
import { createHash, randomUUID } from 'node:crypto';
import { mkdirSync, readFileSync, readdirSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

assert.equal(process.env.SOUS_DISPOSABLE_TEST, '1', 'Use an explicitly disposable generated theme.');
const theme = resolve(process.argv[2]);
const initializer = resolve(dirname(fileURLToPath(import.meta.url)), '../scripts/theme-initialize.mjs');
const snapshot = () => {
  const hash = createHash('sha256');
  const visit = (directory) => {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
      const path = join(directory, entry.name);
      if (entry.isDirectory()) visit(path);
      else hash.update(path).update(readFileSync(path));
    }
  };
  visit(join(theme, 'src'));
  for (const file of ['package.json', 'config/emulsify-core/.prettierignore']) hash.update(readFileSync(join(theme, file)));
  return hash.digest('hex');
};
const before = snapshot();
for (const mode of [[], ['--check']]) {
  const repeated = spawnSync(process.execPath, [initializer, ...mode, theme], { encoding: 'utf8' });
  assert.notEqual(repeated.status, 0, 'Existing project source was accepted for initialization again.');
  assert.match(repeated.stderr, /already initialized/);
}
assert.equal(snapshot(), before, 'A repeated initialization changed project-owned source.');
const marker = `sous-acceptance-${randomUUID()}`;
const source = join(theme, 'src', marker);
const output = join(theme, 'dist/js', `${marker}.js`);
const run = (task) => {
  const result = spawnSync('npm', ['run', task, ...(task === 'prettier' ? ['--', '--check'] : [])], {
    cwd: theme, encoding: 'utf8',
  });
  if (result.error) throw result.error;
  return { status: result.status, output: `${result.stdout}${result.stderr}` };
};
try {
  mkdirSync(join(theme, 'dist/js'), { recursive: true });
  writeFileSync(output, 'const = ;\n');
  const generated = run('lint-js');
  assert.equal(generated.status, 0, generated.output);
  writeFileSync(`${source}.js`, 'const = ;\n');
  const javascript = run('lint-js');
  assert.notEqual(javascript.status, 0, 'Authored invalid JavaScript was ignored.');
  assert.match(javascript.output, /Parsing error/);
  rmSync(`${source}.js`);
  writeFileSync(`${source}.scss`, '.acceptance { color: #ggg; }\n');
  const styles = run('lint-styles');
  assert.notEqual(styles.status, 0, 'Authored invalid SCSS was ignored.');
  assert.match(styles.output, /color-no-invalid-hex/);
  rmSync(`${source}.scss`);
  writeFileSync(`${source}.js`, 'export const acceptance="format me";\n');
  const formatting = run('prettier');
  assert.notEqual(formatting.status, 0, 'Authored formatting failure was ignored.');
  assert.match(formatting.output, new RegExp(marker));
  console.log('PASS: repeat initialization preserves project source; generated output is excluded while authored JavaScript, SCSS and formatting failures remain enforced.');
}
finally {
  for (const file of [output, `${source}.js`, `${source}.scss`]) rmSync(file, { force: true });
}
