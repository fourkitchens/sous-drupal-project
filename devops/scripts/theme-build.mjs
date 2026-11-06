// Build the configured theme once Core discovers asset entrypoints.
// Run npm run theme-build; a new component-neutral theme has nothing to compile.
import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

process.chdir(resolve('web/themes/custom', process.env.npm_package_config_theme));
const { default: configure } = await import(pathToFileURL(
  resolve('node_modules/@emulsify/core/config/vite/vite.config.js'),
));
// Use Core's actual configuration so project entrypoints and extensions count.
const config = await configure({ command: 'build', mode: 'production' });

if (Object.keys(config.build.rollupOptions.input).length === 0) {
  console.log('Theme build: no asset entrypoints yet. Add a Sass or JavaScript entrypoint under src/ or install a component library.');
} else {
  const result = spawnSync('npm', ['run', 'build'], { stdio: 'inherit' });
  if (result.error) throw result.error;
  process.exit(result.status ?? 1);
}
