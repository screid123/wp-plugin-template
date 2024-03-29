const fs = require('fs');
const path = require('path');
const del = require('del');
const log = require('fancy-log');
const gulp = require('gulp');
const rename = require('gulp-rename');
const replace = require('gulp-replace');
const handlebars = require('gulp-hb');
const merge = require('merge-stream');
const lazypipe = require('lazypipe');
const composer = require('gulp-composer');

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
	const json_editor = require('gulp-json-editor');
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
		.pipe(json_editor(json => json))
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
	const release = changelog.releases.find(({ tag_name = '' }) => tag_name === `v${version}`);
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
	const {
		author,
		author_uri,
		description,
		download_uri,
		name,
		requires,
		requires_php,
		short_description,
		slug,
		text_domain,
		uri,
	} = getPluginInfo();

	const replacePlaceholders = lazypipe()
		.pipe(replace, /\{\{VERSION\}\}/g, version)
		.pipe(replace, /\{\{NAME\}\}/g, name)
		.pipe(replace, /\{\{SLUG\}\}/g, slug)
		.pipe(replace, /\{\{TEXT_DOMAIN\}\}/g, text_domain)
		.pipe(replace, /\{\{DESCRIPTION\}\}/g, description)
		.pipe(replace, /\{\{SHORT_DESCRIPTION\}\}/g, short_description)
		.pipe(replace, /\{\{REQUIRES\}\}/g, requires)
		.pipe(replace, /\{\{REQUIRES_PHP\}\}/g, requires_php)
		.pipe(replace, /\{\{URI\}\}/g, uri)
		.pipe(replace, /\{\{AUTHOR\}\}/g, author)
		.pipe(replace, /\{\{AUTHOR_URI\}\}/g, author_uri)
		.pipe(replace, /\{\{DOWNLOAD_URI\}\}/g, download_uri);

	log('Compiling plugin PHP files...');

	return merge(
		// Root files (plugin base files)
		gulp.src('./*.php')
			.pipe(replacePlaceholders())
			.pipe(gulp.dest('./build', { overwrite: true })),
		// Include files (classes)
		gulp.src('./includes/**/*')
			.pipe(replacePlaceholders())
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
	);
}

/**
 * Create the ZIP file for distribution.
 */
function zip() {
	const { version } = getPackageJSON();
	const { slug } = getPluginInfo();

	log('Creating ZIP file for distribution...');

	return gulp.src('./build/**/*')
		.pipe(rename((file) => {
			file.dirname = path.join(slug, file.dirname);
		}))
		.pipe(require('gulp-zip')(`${slug}-v${version}.zip`, { modifiedTime: new Date() }))
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

function composerUpdate(cb) {
	composer( 'update' );
	return cb();
}

/**
 * Watch files and build on change.
 */
function watch() {
	gulp.watch(['./includes/**/*', './*.php'], gulp.series(composerUpdate, build));
	gulp.watch(['./lib/**/*', './vendor/**/*'], copy);
}

/**
 *
 * @param dir
 * @returns {string[]}
 */
function getZips(dir) {
	return fs.readdirSync(dir)
		.filter(function (file) {
			return file.endsWith(".zip");
		});
}

/**
 * Unzip included plugins to an ignored repo for local dev
 */
function installPlugins(cb) {
	log('Installing plugins for local dev...');

	const dir = './.ci/plugins/';
	if (!fs.existsSync(dir)) {
		log('Nothing to install!');
		return cb();
	}

	const zips = getZips(dir);
	if (zips.length < 1) {
		log('Nothing to install!');
		return cb();
	}

	return zips.map(function( zip ) {
		return gulp.src(`${dir}${zip}`)
			.pipe(require('gulp-unzip')({ keepEmpty: true }))
			.pipe(gulp.dest('./.plugins/', { overwrite: true }))
			.on('end', function() {
				cb();
			});
	});
}

exports.build = gulp.series(
	clean,
	gulp.parallel(build, copy),
	gulp.parallel(changelog, releaseNotes, manifest, readme),
);
exports.clean = clean;
exports.test = test;
exports.watch = watch;
exports.zip = zip;
exports.install = installPlugins;
