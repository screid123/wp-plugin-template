# wp-plugin-template

Template repo for WordPress plugin development that uses Composer to manage dependencies.

## Getting started with this template

1. Replace all the following values with your own namespace (recommended format: `Vendor\Namespace`)
   - `namespace WP_Plugin_Template`
   - `@package WP_Plugin_Template`
2. Update `composer.json` with your settings, paying special attention to the following keys:
   - `name` - Adjust to match your namespace, but lower-cased.
   - `autoload.psr-4` - Adjust to match your namespace.
   - `extra.imposter.namespace` - Adjust to match your namespace, and keep the "\\Dependencies" tail.
3. Update `.ci/data/plugin-info.yml` with your own information.
4. Follow the [Development](#development) and [Release](#release) instructions below!

## Development

This plugin uses [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
package for local development.

1. Ensure [Docker](https://docs.docker.com/docker-for-mac/install/) is installed and running.
2. Clone this project to your local machine.
   ```bash
   $ git clone git@github.com:screid123/wp-plugin-template.git
   ```
3. Install NPM and Composer dependencies.
   ```bash
   $ npm install
   $ composer install
   ```
4. Start the local development server.
   ```bash
   $ npm start
   ```
5. Navigate to http://localhost:8888 in your web browser to see WordPress running with the local WordPress plugin or
   theme running and activated.
6. **The plugin is not active by default!** Log into the WordPress Admin (http://localhost:8888/wp-admin/), navigate to
   the plugins menu and activate the plugin (install and activate any other plugins desired as well).
   > **NOTE:** The default credentials are username: `admin` password: `password`.
7. Start the webpack dev server to watch CSS and JS files for changes, and automatically recompile:
   ```bash
   $ npm run dev
   ```
   Press `Ctrl + C` to stop watching.
8. Stop the local development server when finished.
   ```bash
   $ npm stop
   ```

## Release

1. Update the `package.json` version number to the appropriate version (this project adheres to
   [Semantic Versioning](https://semver.org/spec/v2.0.0.html)).
2. Update the `.ci/data/changelog.yml` file with the corresponding version and changes. Changelog updates are
   **required** for publishing a release.
   > **NOTE:** If `version` in `changelog.yml` does not match the `version` in `package.json`, the build will fail.
3. Run the release command to compile all files and generate corresponding markdown files (e.g. - `CHANGELOG.md`).
   ```bash
   $ npm run release
   ```
4. Commit all changed files to your branch.
5. Open a [Pull Request](https://github.com/CreditCardsCom/wp-media-credit/pulls) for your branch to be merged into the
   `main` branch.
6. Upon approval, merge the Pull Request using the "Squash & Merge" option.
7. To release the new version, add a lightweight tag with a semantic version prefixed with `v` (if a pre-release, add a
   `-rc.X` suffix, where `X` is the release candidate increment):
   ```bash
   $ git checkout main
   $ git pull
   $ git tag v1.0.0 # or "v.1.0.0-rc.1" for a pre-release
   $ git push --tags
   ```
   This will kick off the automated release workflow in GitHub Actions which builds the plugin ZIP, publishes a new
   [release](https://github.com/CreditCardsCom/wp-media-credit/releases) with the changelog update, attaches the ZIP as
   a release artifact, and pushes the ZIP and a `manifest.json` to the S3 bucket for distribution.
   > **NOTE:** Pre-release assets **will not** be uploaded to S3 and thus **will not** prompt for an update in the
   > WordPress Admin.