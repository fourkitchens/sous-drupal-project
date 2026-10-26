/**
 * Check custom PHP syntax without bootstrapping Drupal or requiring a theme.
 * Run from the project root with npm run lint:php in the selected Node/PHP
 * runtime. Files are read only; the first PHP error or failed process stops lint.
 * This is a syntax check, not a Drupal coding-standard or static-analysis tool.
 */

import { globSync } from 'node:fs';
import { spawnSync } from 'node:child_process';

// Include Drupal's PHP-bearing extensions. Exclude downloaded/runtime content
// and sort paths so failures are reported in a repeatable order.
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
  // An empty custom-code scope is a successful check, not an installation error.
  console.log('PHP syntax: no custom PHP files found.');
}
for (const file of files) {
  // Use argument arrays rather than a shell so spaces in filenames stay intact.
  // Inherit output to retain PHP's original filename/line-number diagnostics.
  const result = spawnSync('php', ['-l', file], { stdio: 'inherit' });
  if (result.error) {
    console.error(result.error.message);
    process.exit(1);
  }
  if (result.status !== 0) {
    // A process terminated by a signal has no numeric status; still fail lint.
    process.exit(result.status ?? 1);
  }
}
