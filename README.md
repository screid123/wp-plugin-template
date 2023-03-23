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
3. Update `package.json` and reset the version to `1.0.0`. 
4. Update the "meta" `Gruntfile.js` with your own information.
5. Update your project/repo's Secrets for use with GitHub Actions:
   - `AWS_S3_BUCKET`
   - `AWS_ACCESS_KEY_ID`
   - `AWS_SECRET_ACCESS_KEY`
   - `AWS_REGION`
   - `GHA_RELEASE_TOKEN` (see [docs]())
6. Update the "Release" workflow (`./github/workflows/release.yml`) environment variables (lines 13-15):
   - `PLUGIN_SLUG` - Should match `meta.slug` in `Gruntfile.js`
   - `DOWNLOAD_URI` - Should match `meta.download_uri` in `Gruntfile.js`
   - `DEST_DIR` - Should be the relative path of `DOWNLOAD_URI`, e.g. - 
     `https://cdn.ccstatic.com/wordpress-plugins/wp-plugin-template/` would be `wordpress-plugins/wp-plugin-template`
     (no leading or trailing slash!)
7. Update `phpcs.xml`, specifically:
   - `minimum_supported_wp_version`
   - `testVersion`
   - `text_domain` (in `WordPress.WP.I18n`)
   - `minimum_supported_version` (in `WordPress.WP.DeprecatedFunctions`)
   - `prefixes` (in `WordPress.NamingConventions.PrefixAllGlobals`)
8. Add or remove any plugin ZIP files dependencies for local development to `.ci/plugins`
   - These should be "private" plugins that otherwise cannot be found through WP Admin, or the minimum supported version
     of required plugins (install plugins from the marketplace via the WP Admin)
   - Make sure to update the `"mappings"` or `"plugins"` config in `.wp-env.json` to point to the plugins (which will be
     unzipped into `.plugins/`)
9. Update this README.md file with your own info!
10. Delete both `composer.lock` and `package-lock.json` and then run the following:
    ```bash
    $ composer install
    $ npm install
    ```
    > **NOTE:** Make sure to commit the new `composer.lock` and `package-lock.json` files that are generated!
11. Follow the [Development](#development) and [Release](#release) instructions below!

## Development

This plugin uses [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
package for local development.

1. Ensure Docker is installed and running. [Colima](https://github.com/abiosoft/colima) is highly recommended!
2. Clone this project to your local machine.
   ```bash
   $ git clone git@github.com:screid123/wp-plugin-template.git
   ```
3. Install NPM and Composer dependencies.
   ```bash
   $ npm ci
   $ composer install
   ```
4. Install the plugin dependencies and start the local development server.
   ```bash
   $ npm run install
   $ npm start
   ```
5. Navigate to http://localhost:8888 in your web browser to see WordPress running with the local WordPress plugin or
   theme running and activated.
6. **The plugin is not active by default!** This is on purpose in order to help test any activation hooks. Log into the 
   WordPress Admin (http://localhost:8888/wp-admin/), navigate to the plugins menu and activate the plugin (install and 
   activate any other plugins desired as well).
   > **NOTE:** The default credentials are username: `admin` password: `password`.
7. Run the "dev" command to watch PHP, CSS and JS files for changes, and automatically recompile:
   ```bash
   $ npm run dev
   ```
   Press `Ctrl + C` to stop watching.
8. Stop the local development server when finished.
   ```bash
   $ npm stop
   ```

## Release

1. Commit all changed files to your branch.
2. Open a Pull Request for your branch to be merged into the `main` branch.
3. Upon approval, merge the Pull Request. **DO NOT** use the "Squash & Merge" option!
4. GitHub Actions will run [semantic-release](https://semantic-release.gitbook.io/) to determine the new version/tag.
   This process will check the commit messages to determine the proper version to create, update said version in
   `package.json`, update `CHANGELOG.md` with the latest changes, and create a new tag.
5. If a new tag is created, GitHub Actions will kick off another automated workflow to build the plugin ZIP, publish a
   new Release with the changelog update, attach the ZIP as a release artifact, and push the ZIP and a `manifest.json`
   to the S3 bucket for distribution.

## Installation

To install this plugin on a WordPress site:

1. [Download the latest release](https://github.com/screid123/wp-plugin-template/releases) from GitHub (the saved file
   should be named wp-plugin-template-{version}.zip).
2. Go to the _Plugins → Add New_ screen in the WordPress Admin.
3. Click the **Upload** button at the top next to the "Add Plugins" title.
4. Upload the zip file downloaded in the first step.
5. Click the **Activate Plugin** link after installation completes.

> **NOTE:** Updates can be installed through the "Plugins" menu in the site's WP Admin.