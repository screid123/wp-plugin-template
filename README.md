# wp-plugin-template

Template repo for WordPress plugin development that uses Composer to manage dependencies (along with 
[package scoping](https://github.com/TypistTech/imposter-plugin) to prevent collisions), a gulp workflow for development, 
external hosting via AWS, and automated releases through GitHub Actions. Also utilizes the 
[WordPress Plugin Library](https://github.com/cedaro/wp-plugin) to help structure plugin classes.

## Getting started with this template

1. Replace all the following values with your own namespace (recommended format: `Vendor\Namespace`)
   - `namespace WP_Plugin_Template`
   - `@package WP_Plugin_Template`
   - `use WP_Plugin_Template\...`
   > **NOTE:** Basically find all instances of "WP_Plugin_Template" and replace with your custom "Vendor\Namespace" value.
2. Update `composer.json` with your settings, paying special attention to the following keys:
   - `name` - Adjust to match your namespace, but lower-cased
   - `autoload.psr-4` - Adjust to match your namespace
   - `extra.imposter.namespace` - Adjust to match your namespace (keep the "\\Dependencies" tail)
3. Update `.ci/data/plugin-info.yml` with your own information.
4. Update your project/repo's Secrets for use with GitHub Actions:
   - `AWS_S3_BUCKET`
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_REGION`
5. Update the "Release" workflow (`./github/workflows/release.yml`) environment variables (lines 13-15):
   - `PLUGIN_SLUG` - Should match `slug` in `plugin-info.yml`
   - `DOWNLOAD_URI` - Should match `download_uri` in `plugin-info.yml`
   - `DEST_DIR` - Should be the relative path of `DOWNLOAD_URI`, e.g. - 
     `https://cdn.ccstatic.com/wordpress-plugins/wp-plugin-template/` would be `wordpress-plugins/wp-plugin-template`
     (no leading or trailing slash!)
6. Update `phpcs.xml`, specifically:
   - `minimum_supported_wp_version`
   - `testVersion`
   - `text_domain` (in `WordPress.WP.I18n`)
   - `minimum_supported_version` (in `WordPress.WP.DeprecatedFunctions`)
   - `prefixes` (in `WordPress.NamingConventions.PrefixAllGlobals`)
7. Update this README.md file with your own info!
8. Follow the [Development](#development) and [Release](#release) instructions below!

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
   $ npm ci
   $ composer install
   ```
4. Start the local development server.
   ```bash
   $ npm start
   ```
5. Navigate to http://localhost:8888 in your web browser to see WordPress running with the local WordPress plugin or
   theme running and activated.
6. **The plugin is not active by default!** This is on purpose in order to help test any activation hooks. Log into the 
   WordPress Admin (http://localhost:8888/wp-admin/), navigate to the plugins menu and activate the plugin (install and 
   activate any other plugins desired as well).
   > **NOTE:** The default credentials are username: `admin` password: `password`.
7. Start the webpack dev server to watch PHP, CSS and JS files for changes, and automatically recompile:
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
   > **NOTE:** If the `version` in `package.json` does not match a `tag_name` in `changelog.yml`, the build will fail.
3. Run the release command to compile all files and generate corresponding markdown files (e.g. - `CHANGELOG.md`).
   ```bash
   $ npm run release
   ```
4. Commit all changed files to your branch.
5. Open a Pull Request for your branch to be merged into the `main` branch.
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
   release with the changelog update, attaches the ZIP as a release artifact, and pushes the ZIP and a `manifest.json` 
   to the S3 bucket for distribution.
   > **NOTE:** Pre-release assets **will not** be uploaded to S3 and thus **will not** prompt for an update in the
   > WordPress Admin.