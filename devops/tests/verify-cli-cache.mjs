#!/usr/bin/env node

import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { access, mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { homedir, tmpdir } from 'node:os';
import { basename, dirname, join } from 'node:path';
import { pathToFileURL } from 'node:url';

// Exercise the installed CLI; no remote repository or application database is used.
const require = createRequire(import.meta.url);
const cli = dirname(require.resolve('@emulsify/cli/package.json'));
const originalDirectory = process.cwd();
const originalHome = process.env.HOME;
const work = await mkdtemp(join(tmpdir(), 'sous-cli-cache-'));
// Native `cache clear` clears every bucket. Isolate HOME before loading the CLI
// so this regression can never clear a developer's real Emulsify cache.
process.env.HOME = join(work, 'home');
const load = (file) => import(pathToFileURL(join(cli, 'dist', file)).href);
const { default: cloneIntoCache } = await load('util/cache/cloneIntoCache.js');
const { default: getCachedItemPath } = await load('util/cache/getCachedItemPath.js');
const { simpleGit } = createRequire(join(cli, 'package.json'))('simple-git');
const bucket = basename(work);
const cache = join(homedir(), '.emulsify', 'cache', bucket);
const repository = join(work, 'starter');
const project = join(work, 'project');
const git = (...args) => execFileSync('git', args, { cwd: repository, encoding: 'utf8' }).trim();
const commit = (message) => {
  git('add', '.');
  git('-c', 'user.name=Sous Regression', '-c', 'user.email=sous-test@example.invalid', 'commit', '-m', message);
  return git('rev-parse', 'HEAD');
};

try {
  await mkdir(repository);
  await mkdir(project);
  git('init', '--initial-branch=main');
  await writeFile(join(repository, 'package.json'), JSON.stringify({ name: 'sous-cli-probe', version: '1.0.0', private: true }));
  const configuration = JSON.stringify({ project: { name: 'Cache Probe', platform: 'none', machineName: 'probe' } });
  await writeFile(join(repository, 'project.emulsify.json'), configuration);
  await writeFile(join(project, 'project.emulsify.json'), configuration);
  const before = commit('Initial cache fixture');

  // Keep the secure Git major without patching upstream CLI code or granting
  // allowUnsafeSshCommand. The dependency itself must reject an unsafe setting.
  await assert.rejects(async () => simpleGit().env({ GIT_SSH_COMMAND: 'blocked-probe-value' }).version());

  process.chdir(project);
  const options = { bucket, itemPath: [], repository, checkout: 'main' };
  const destination = getCachedItemPath(options);
  assert.equal(dirname(destination), cache);
  await cloneIntoCache(bucket, [])(options);
  assert.equal(execFileSync('git', ['rev-parse', 'HEAD'], { cwd: destination, encoding: 'utf8' }).trim(), before);
  await writeFile(join(repository, 'refresh.txt'), 'A new branch revision\n');
  const after = commit('Move cached branch');
  assert.notEqual(after, before);
  await cloneIntoCache(bucket, [], { refresh: true })(options);
  // CLI 2.4.1 catches the secure dependency's rejected environment during its
  // remote lookup. A moving-ref refresh therefore retains the old cache; expose
  // that upstream limitation and verify its native cold-cache recovery instead.
  assert.equal(execFileSync('git', ['rev-parse', 'HEAD'], { cwd: destination, encoding: 'utf8' }).trim(), before);
  execFileSync(process.execPath, [join(cli, 'dist', 'index.js'), 'cache', 'clear'], { encoding: 'utf8' });
  await assert.rejects(access(destination));
  await cloneIntoCache(bucket, [])(options);
  assert.equal(execFileSync('git', ['rev-parse', 'HEAD'], { cwd: destination, encoding: 'utf8' }).trim(), after);

  execFileSync(process.execPath, [
    join(cli, 'dist', 'index.js'), 'init', 'Native Clone Probe', work,
    '--machineName', 'native_probe', '--platform', 'none',
    '--starter', repository, '--checkout', 'main', '--yes',
  ], { cwd: project, encoding: 'utf8' });
  const generated = join(work, 'native_probe');
  await access(join(generated, 'package.json'));
  await assert.rejects(access(join(generated, '.git')));
  const result = JSON.parse(await readFile(join(generated, 'project.emulsify.json'), 'utf8'));
  assert.equal(result.project.machineName, 'native_probe');
  console.log(JSON.stringify({ secure_git_guard: true, cache_clone: true, moving_ref_refresh_supported: false, native_cache_clear_recovery: true, native_cli_init: true }));
} finally {
  process.chdir(originalDirectory);
  // The unique bucket is the only native CLI cache directory owned by this probe.
  assert.equal(dirname(cache), join(homedir(), '.emulsify', 'cache'));
  assert.equal(basename(cache), bucket);
  await rm(cache, { recursive: true, force: true });
  if (originalHome === undefined) delete process.env.HOME;
  else process.env.HOME = originalHome;
  await rm(work, { recursive: true, force: true });
}
