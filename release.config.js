module.exports = {
  branches: [
    '2.x',
    'master'
  ],
  plugins: [
    ['@semantic-release/commit-analyzer'],
    '@semantic-release/release-notes-generator',
    [
      '@semantic-release/changelog',
      {
        changelogFile: 'CHANGELOG.md',
      },
    ],
    "@semantic-release/github",
  ],
};
