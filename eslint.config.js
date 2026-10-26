const js = require('@eslint/js');
const globals = require('globals');

module.exports = [
  {
    ignores: [
      '**/node_modules/**',
      '**/vendor/**',
      'reference/**',
      '.integration/**',
      '.integration-results/**',
      '.sfdx/**',
      'web/core/**',
      'web/libraries/**',
      'web/modules/contrib/**',
      'web/modules/features/**',
      'web/profiles/**',
      'web/recipes/**',
      'web/sites/**',
      'web/storybook/**',
      'web/themes/**',
      'web/modules/custom/**/dist/**',
    ],
  },
  js.configs.recommended,
  {
    files: ['*.{js,cjs,mjs}', 'devops/**/*.{js,cjs,mjs}'],
    languageOptions: { globals: globals.node },
  },
  {
    files: ['*.js', '*.cjs'],
    languageOptions: { sourceType: 'commonjs' },
  },
  {
    files: ['web/modules/custom/**/*.{js,cjs,mjs}'],
    languageOptions: {
      globals: {
        ...globals.browser,
        Drupal: 'readonly',
        drupalSettings: 'readonly',
        drupalTranslations: 'readonly',
        jQuery: 'readonly',
        once: 'readonly',
      },
    },
  },
];
