import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { mkdtemp, readFile, rm, symlink, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { Writable } from 'node:stream';
import { fileURLToPath, pathToFileURL } from 'node:url';
import semanticRelease from 'semantic-release';

const require = createRequire(import.meta.url);
const root = fileURLToPath(new URL('../../', import.meta.url));
const config = require(join(root, 'release.config.js'));
const pluginName = (plugin) => Array.isArray(plugin) ? plugin[0] : plugin;
assert.ok(Array.isArray(config.branches));
assert.ok(config.branches.includes('main'));
assert.equal(config.tagFormat, '${version}');
assert.deepEqual(config.plugins.map(pluginName), [
  '@semantic-release/commit-analyzer',
  '@semantic-release/release-notes-generator',
  '@semantic-release/github',
]);
for (const plugin of config.plugins.slice(0, 2)) {
  assert.equal(plugin[1].preset, 'conventionalcommits');
}
const github = config.plugins[2][1];
for (const option of ['successCommentCondition', 'failCommentCondition', 'labels', 'releasedLabels', 'addReleases']) {
  assert.equal(github[option], false);
}

// Both the caller and reusable publisher must independently exclude review
// branches and validation-only package overrides from real publication.
for (const filename of ['semantic-release.yml', 'release.yml']) {
  const workflow = await readFile(join(root, '.github/workflows', filename), 'utf8');
  const guard = workflow.split('\n').find((line) => line.trim().startsWith('if:') && line.includes('github.ref'));
  assert.ok(guard?.includes("github.repository == 'fourkitchens/sous-drupal-project'"), 'Publisher is canonical-repository only.');
  assert.ok(guard.includes("github.ref == 'refs/heads/main'"), 'Review branch pushes cannot publish.');
  assert.ok(guard.includes("github.event_name != 'pull_request'"), 'Pull requests cannot publish.');
  assert.ok(guard.includes('!inputs.packages_ref') || guard.includes('!github.event.inputs.packages_ref'), 'Candidate ZIP overrides cannot publish.');
}

const cases = [
  { name: 'first stable release', tag: null, message: 'feat: add recipe capabilities', version: '1.0.0' },
  { name: 'ordinary maintenance', tag: '1.0.3', message: 'chore: maintain release tooling', version: null },
  { name: 'patch', tag: '1.0.3', message: 'fix: repair recipe installation', version: '1.0.4' },
  { name: 'minor', tag: '1.0.3', message: 'feat: add recipe capabilities', version: '1.1.0' },
  { name: 'breaking header', tag: '1.0.3', message: 'feat!: require Drupal 11.4', version: '2.0.0' },
  { name: 'breaking footer', tag: '1.0.3', message: 'feat: require Drupal 11.4\n\nBREAKING CHANGE: Drupal 11.4 is the minimum supported baseline.', version: '2.0.0' },
  { name: 'Sous 8 installer contract', tag: '7.0.1', message: 'feat!: require the Sous 8 installer contract', version: '8.0.0' },
];
const results = [];
for (const test of cases) {
  const work = await mkdtemp(join(tmpdir(), 'sous-release-test-'));
  const repository = join(work, 'repository');
  const bare = join(work, 'origin.git');
  const repositoryUrl = 'https://github.com/sous-validation/recipe.git';
  const env = { ...process.env };
  for (const key of Object.keys(env)) {
    if (key.toUpperCase().startsWith('GIT_')) delete env[key];
  }
  for (const key of ['GH_TOKEN', 'GITHUB_TOKEN', 'GITHUB_ACTIONS', 'CIRCLECI', 'TRAVIS', 'GITLAB_CI']) delete env[key];
  Object.assign(env, {
    GIT_ALLOW_PROTOCOL: 'file',
    GIT_TERMINAL_PROMPT: '0',
    GIT_CONFIG_NOSYSTEM: '1',
    GIT_CONFIG_GLOBAL: join(work, 'gitconfig'),
    CI: 'false',
  });
  const git = (args, cwd = repository) => execFileSync('git', args, {
    cwd, env, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'],
  }).trim();
  let log = '';
  const output = new Writable({ write(chunk, encoding, callback) { log += chunk; callback(); } });
  try {
    await writeFile(env.GIT_CONFIG_GLOBAL, '');
    git(['init', '--bare', '--initial-branch=main', bare], work);
    git(['init', '--initial-branch=main', repository], work);
    git(['config', '--local', 'user.name', 'Sous Release Validation']);
    git(['config', '--local', 'user.email', 'release-validation@example.invalid']);
    git(['config', '--local', `url.${pathToFileURL(bare).href}.insteadOf`, repositoryUrl]);
    git(['remote', 'add', 'origin', repositoryUrl]);
    await writeFile(join(repository, 'package.json'), JSON.stringify({ name: 'sous-release-test', private: true }));
    await writeFile(join(repository, '.gitignore'), 'node_modules/\n');
    await symlink(join(root, 'node_modules'), join(repository, 'node_modules'), 'dir');
    git(['add', '.']);
    git(['commit', '-m', 'chore: baseline']);
    if (test.tag) git(['tag', test.tag]);
    git(['push', 'origin', 'main', '--tags']);
    await writeFile(join(repository, 'change.txt'), 'A prospective source change.\n');
    git(['add', '.']);
    git(['commit', '-m', test.message]);
    git(['push', 'origin', 'main']);
    const tagsBefore = git(['ls-remote', '--tags', 'origin']);
    const result = await semanticRelease({
      ...config,
      repositoryUrl,
      dryRun: true,
      ci: false,
      // Exercise native versioning/notes locally. GitHub credentials and API
      // hooks are deliberately excluded; production publisher options are
      // asserted above, and Git permits only the rewritten local file remote.
      plugins: config.plugins.slice(0, 2),
    }, { cwd: repository, env, stdout: output, stderr: output });
    if (test.version === null) assert.equal(result, false, test.name);
    else {
      assert.equal(result.nextRelease.version, test.version, test.name);
      assert.ok(result.nextRelease.notes.length > 0, 'Release notes were generated.');
      if (test.name === 'breaking header') {
        assert.match(result.nextRelease.notes, /BREAKING CHANGE/i);
        assert.match(result.nextRelease.notes, /require Drupal 11\.4/);
      }
    }
    assert.equal(git(['ls-remote', '--tags', 'origin']), tagsBefore);
    assert.equal(git(['tag', '--list']), test.tag ?? '');
    results.push({ case: test.name, version: test.version, tagsUnchanged: true });
  } catch (error) {
    process.stderr.write(log);
    throw error;
  } finally {
    await rm(work, { recursive: true, force: true });
  }
}
console.log(JSON.stringify({ nativeDryRun: true, githubApiHooksRun: false, npmPublisherConfigured: false, results }, null, 2));
