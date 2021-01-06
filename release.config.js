module.exports = {
  branches: [
    '+([0-9])?(.{+([0-9]),x}).x',
    'stable',
    {name: 'rc', prerelease: true},
    {name: 'beta', prerelease: true},
    {name: 'alpha', prerelease: true},
    {name: 'dev', prerelease: true}
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
    '@semantic-release/git',
  ],
};
