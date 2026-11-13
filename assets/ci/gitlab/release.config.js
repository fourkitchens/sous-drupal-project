// GitLab sites publish releases with this file.
// Setup copies it over the default GitHub release config.
module.exports = {
  branches: ['main'],
  tagFormat: 'v${version}',
  plugins: [
    ['@semantic-release/commit-analyzer', {preset: 'conventionalcommits'}],
    ['@semantic-release/release-notes-generator', {preset: 'conventionalcommits'}],
    ['@semantic-release/gitlab', {
      successComment: false,
      failComment: false,
    }],
  ],
};
