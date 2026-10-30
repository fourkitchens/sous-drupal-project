// Keep syntax checks available before Drupal and the theme are installed.

import { globSync } from 'node:fs';
import { spawnSync } from 'node:child_process';

// Skip dependencies and uploaded files; only project PHP belongs in this check.
// Stable ordering makes lint failures easier to compare.
const files = globSync(
  [
    'devops/**/*.{php,inc,module,install,theme}',
    'web/modules/custom/**/*.{php,inc,module,install,theme}',
    'web/themes/custom/**/*.{php,inc,module,install,theme}',
    'web/sites/**/*.{php,inc,module,install,theme}',
  ],
  { exclude: ['**/node_modules/**', '**/vendor/**', '**/files/**', '**/private/**'] },
).sort();

if (files.length === 0) {
  console.log('PHP syntax: no custom PHP files found.');
}
for (const file of files) {
  // Direct calls preserve filenames and PHP's original diagnostics.
  const result = spawnSync('php', ['-l', file], { stdio: 'inherit' });
  // Propagate failures so npm cannot treat an incomplete syntax check as a pass.
  if (result.error) {
    console.error(result.error.message);
    process.exit(1);
  }
  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}
