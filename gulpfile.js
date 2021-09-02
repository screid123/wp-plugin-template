const fs = require('fs');
const path = require('path');
const del = require('del');
const log = require('fancy-log');
const gulp = require('gulp');
const rename = require('gulp-rename');
const replace = require('gulp-replace');
const handlebars = require('gulp-hb');
const merge = require('merge-stream');

// Load .env file if present
require('dotenv').config();

/**
 * Parse the "package.json" file instead of using `require` because `require` caches
 * multiple calls (so the version number won't be updated).
 * @returns {number | string | boolean}
 */
const getPackageJSON = () => JSON.parse(fs.readFileSync('./package.json', 'utf-8'));

/**
 * Retrieve data from a YAML file.
 * @param {string} path
 * @returns {object}
 */
const getYaml = (path) => require('yaml-js').load(fs.readFileSync(path));

/**
 * Get the changelog data
 * @returns {object}
 */
const getChangelogInfo = () => getYaml('./.ci/data/changelog.yml');

/**
 * Get the plugin data
 * @returns {object}
 */
const getPluginInfo = () => getYaml('./.ci/data/plugin-info.yml');

/**
 * Remove build artifacts.
 */
function clean(cb) {
	log('Cleaning up build artifacts...');
	del.sync(['./build/**', './dist/**']);
	cb();
}

/**
 * Create the CHANGELOG.md
 */
function changelog() {
	const changelog = getChangelogInfo();
	const plugin = getPluginInfo();
	const hb = handlebars({ handlebars: require('handlebars') }).data({ changelog, plugin });

	log('Creating “CHANGELOG.md”.');

	return gulp.src('./.ci/templates/changelog.hbs')
		.pipe(hb)
		.pipe(rename('CHANGELOG.md'))
		.pipe(gulp.dest('./', { overwrite: true }));
}

/**
 * Create the manifest.json
 */
function manifest() {
	const jeditor = require('gulp-json-editor');
	const { version } = getPackageJSON();
	const changelog = getChangelogInfo();
	const plugin = getPluginInfo();
	const releases = changelog.releases
		.filter(release => !release.prerelease) // No pre-releases!
		.filter((release, index) => index < 3); // Get the last 3
	const hb = handlebars({ handlebars: require('handlebars') })
		.helpers({
			'markdown': require('helper-markdown')({ html: true, typographer: true }),
			'format': (str) => {
				if (typeof str !== 'string') return '';
				return str.replace(/"/g, "'").replace(/(<\/?p>|\n)/g, '');
			},
			'iso_date': (str) => {
				if (typeof str !== 'string') return '';
				return new Date(`${str} GMT-0500`).toISOString();
			},
			'nice_date': (str) => {
				if (typeof str !== 'string') return '';
				const date = new Date(`${str} GMT-0500`);
				const options = { year: 'numeric', month: 'long', day: 'numeric' };
				return new Intl.DateTimeFormat('en-US', options).format(date);
			},
			'first': (arr, n) => {
				if (!Array.isArray(arr)) return '';
				if (typeof n !== 'number') return arr[0];
				return arr[n];
			},
		})
		.data({ changelog, plugin, releases, version });

	log('Creating “manifest.json”...');

	return gulp.src('./.ci/templates/manifest.hbs')
		.pipe(hb)
		.pipe(jeditor(json => json))
		.pipe(rename('manifest.json'))
		.pipe(gulp.dest('./dist', { overwrite: true }));
}

/**
 * Create the plugin's readme.txt
 */
function readme() {
	const changelog = getChangelogInfo();
	const plugin = getPluginInfo();
	const hb = handlebars({ handlebars: require('handlebars') })
		.helpers({
			'count': (index) => parseInt(index) + 1,
		})
		.data({ changelog, plugin });

	log('Creating “readme.txt”.');

	return gulp.src('./.ci/templates/readme.hbs')
		.pipe(hb)
		.pipe(rename('readme.txt'))
		.pipe(gulp.dest('./build', { overwrite: true }));
}

/**
 * Create release notes from changelog data
 */
function releaseNotes() {
	const { version } = getPackageJSON();
	const changelog = getChangelogInfo();

	// Get a the associated release changes from the changelog data.
	const release = changelog.releases.find(item => item.tag_name === `v${version}`);
	if (!release) {
		throw Error(`ERROR: "v${version}" is not a valid release in the changelog!`);
	}

	const hb = handlebars({ handlebars: require('handlebars') }).data(release);

	log('Creating “release_notes.txt”...');

	return gulp.src('./.ci/templates/release.hbs')
		.pipe(hb)
		.pipe(rename('release_notes.txt'))
		.pipe(gulp.dest('./dist', { overwrite: true }));
}

/**
 * Compile plugin assets and create a ZIP
 */
function build() {
	const { version } = getPackageJSON();

	log('Compiling plugin PHP files...');

	return merge(
		gulp.src('./media-credit.php')
			.pipe(replace(/__VERSION__/g, version))
			.pipe(gulp.dest('./build', { overwrite: true })),
		gulp.src('./includes/**/*')
			.pipe(replace(/__VERSION__/g, version))
			.pipe(gulp.dest('./build/includes', { overwrite: true }))
	);
}

/**
 * Copy files into the dist directory.
 */
function copy() {
	log('Copying over assets and vendor PHP files...');

	return merge(
		gulp.src('./vendor/**/*')
			.pipe(gulp.dest('./build/vendor', { overwrite: true })),
		gulp.src('./lib/**/*')
			.pipe(gulp.dest('./build/lib', { overwrite: true })),
		gulp.src(['./assets/**/*', '!./assets/src', '!./assets/src/*', '!./assets/src/**/*'])
			.pipe(gulp.dest('./build/assets', { overwrite: true })),
	);
}

/**
 * Create the ZIP file for distribution.
 */
function zip() {
	const { version } = getPackageJSON();

	log('Creating ZIP file for distribution...');

	return gulp.src('./build/**/*')
		.pipe(rename((file) => {
			file.dirname = path.join('rv-media-credit', file.dirname);
		}))
		.pipe(require('gulp-zip')(`rv-media-credit-v${version}.zip`, { modifiedTime: new Date() }))
		.pipe(gulp.dest('./dist', { overwrite: true }));
}

/**
 * Run tests
 */
function test(cb) {
	const { execSync } = require('child_process');
	const { version } = getPackageJSON();

	// Make sure the tag name matched the version in "package.json"
	if (process.env.TAG_NAME && process.env.TAG_NAME !== `v${version}`) {
		return cb(new Error('The version in “package.json” does not match the tag name!'));
	}

	// Check `git status` for any changes. If found, recommend running a build and committing files.
	// See: https://unix.stackexchange.com/a/155077
	let stdout = execSync('git status --porcelain', { encoding: 'utf-8' });

	// If `stdout` contains anything, it means there are uncommitted changes.
	if (stdout) {
		return cb(new Error('Changes detected! Make sure to run a build and commit any changed files before deploying!'));
	}

	return cb();
}

exports.clean = clean;
exports.release = gulp.series(
	clean,
	gulp.parallel(build, copy),
	gulp.parallel(changelog, releaseNotes, manifest, readme),
	zip
);
exports.test = test;
exports.zip = zip;
