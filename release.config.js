// Generated sites publish releases with this file.
// The starter overrides the tag format in its own workflow.
module.exports = {
  branches: ['main'],
  tagFormat: 'v${version}',
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
