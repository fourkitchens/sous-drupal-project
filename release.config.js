module.exports = {
  branches: [
    '4.x',
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
