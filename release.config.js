module.exports = {
  branches: [
    '+([0-9])?(.{+([0-9]),x}).x',
    'main'
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
