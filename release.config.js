module.exports = {
  tagFormat: '${version}',
  branches: [
    '+([0-9])?(.{+([0-9]),x}).x',
    'main',
    'next',
    'next-major',
    {name: 'beta', prerelease: true},
    {name: 'alpha', prerelease: true}
  ],
  plugins: [
    ['@semantic-release/commit-analyzer', {preset: 'conventionalcommits'}],
    ['@semantic-release/release-notes-generator', {preset: 'conventionalcommits'}],
    ['@semantic-release/github', {
      successCommentCondition: false,
      failCommentCondition: false,
      labels: false,
      releasedLabels: false,
      addReleases: false,
    }],
  ],
};
