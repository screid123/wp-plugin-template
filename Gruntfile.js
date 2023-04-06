module.exports = function(grunt) {
	const pkg = grunt.file.readJSON('package.json');

	const meta = {
		name: 'WP Plugin Template', // Human-readable name of the plugin
		slug: '<%= pkg.name %>', // This should match the entry file's name
		download_uri: 'https://cdn.ccstatic.com/wordpress-plugins/<%= pkg.name %>/', // This is where the plugin ZIP will be uploaded when new versions are released. Trailing slash!
		text_domain: '<%= pkg.name %>',
		version: '<%= pkg.version %>',
		uri: 'https://github.com/screid123/wp-plugin-template',
		author: '<%= pkg.author.name %>',
		author_uri: '<%= pkg.author.url %>',
		requires: '5.5.1', // Minimum WP version required
		tested: '5.8.1', // Latest WP version tested
		requires_php: '7.4', // Minimum PHP version (should match composer.json)
		license: 'GPLv3 or later',
		license_uri: 'https://www.gnu.org/licenses/gpl-3.0.txt',
		contributors: ['[screid123](https://github.com/screid123)'], // GitHub handles
		short_description: '<%= pkg.description %>', // Short description from package.json!
		description: false, // Long description (optional)
		installation: false, // Installation instructions (optional)
		faqs: false, // FAQs (optional; eg- [{ q: 'Why did the chicken cross the road?', a: 'To get to the other side.' }])
		screenshots: false, // Screenshots (optional; eg- [{ url: 'https://cdn.ccstatic.com/wordpress-plugins/<%= pkg.name %>/assets/screenshot-1.png', caption: 'The screenshot description corresponds to ./assets/screenshot-1.png.'}, {url: 'https://cdn.ccstatic.com/wordpress-plugins/<%= pkg.name %>/assets/screenshot-2.png', caption: 'The screenshot description corresponds to ./assets/screenshot-2.png.'}])
		banners: false, // Banner files (optional; eg- { low: './assets/banner-low.png', high: './assets/banner-high.png' })
		last_updated: new Date().toISOString(), // Build timestamp
	};

	// Project configuration.
	grunt.initConfig({
		pkg,
		meta,
		clean: {
			build: ['./build/*'],
			dist: ['./dist/*'],
		},
		copy: {
			lib: {
				expand: true,
				src: 'lib/*',
				dest: 'build/',
			},
			vendor: {
				expand: true,
				src: 'vendor/*',
				dest: 'build/',
			},
		},
		exec: {
			build: 'NODE_ENV=production wp-scripts build assets/js/admin.js assets/js/frontend.js --output-path=build/assets',
			composer: 'composer update --no-scripts',
			test: 'test -z "$(git status --porcelain)"',
		},
		'compile-handlebars': {
			manifest: {
				files: [{
					src: './.ci/templates/manifest.hbs',
					dest: './dist/manifest.json',
				}],
				templateData: { plugin: meta },
			},
			readme: {
				files: [{
					src: './.ci/templates/readme.hbs',
					dest: './build/readme.txt',
				}],
				templateData: { plugin: meta },
			},
		},
		replace: {
			php: {
				options: {
					patterns: [
						{ match: /{{VERSION}}/g, replacement: '<%= meta.version %>' },
						{ match: /{{NAME}}/g, replacement: '<%= meta.name %>' },
						{ match: /{{SLUG}}/g, replacement: '<%= meta.slug %>' },
						{ match: /{{TEXT_DOMAIN}}/g, replacement: '<%= meta.text_domain %>' },
						{ match: /{{DESCRIPTION}}/g, replacement: '<%= meta.description %>' },
						{ match: /{{SHORT_DESCRIPTION}}/g, replacement: '<%= meta.short_description %>' },
						{ match: /{{REQUIRES}}/g, replacement: '<%= meta.requires %>' },
						{ match: /{{REQUIRES_PHP}}/g, replacement: '<%= meta.requires_php %>' },
						{ match: /{{URI}}/g, replacement: '<%= meta.uri %>' },
						{ match: /{{AUTHOR}}/g, replacement: '<%= meta.author %>' },
						{ match: /{{AUTHOR_URI}}/g, replacement: '<%= meta.author_uri %>' },
						{ match: /{{DOWNLOAD_URI}}/g, replacement: '<%= meta.download_uri %>' },
					]
				},
				files: [
					{
						src: ['*.php', 'includes/**/*.php'],
						dest: 'build/',
						expand: true,
						flatten: false,
						force: true,
					},
				],
			},
		},
		// Update this section with any dependencies needed for local development.
		unzip: {
			'acf-pro': {
				src: './.ci/plugins/advanced-custom-fields-pro.zip',
				dest: './.plugins',
			},
		},
		watch: {
			php: {
				files: ['./includes/**/*', './*.php'],
				tasks: ['replace:php'],
				options: {
					atBegin: true,
				},
			},
			composer: {
				files: ['./build/includes/**/*.php'],
				tasks: ['exec:composer'],
				options: {
					event: ['added', 'deleted'],
				},
			},
			lib: {
				files: ['./lib/**/*'],
				tasks: ['copy:lib'],
				options: {
					atBegin: true,
				},
			},
			vendor: {
				files: ['./vendor/**/*'],
				tasks: ['copy:vendor'],
				options: {
					atBegin: true,
				},
			},
			assets: {
				files: ['./assets/**/*.js', './assets/**/*.scss'],
				tasks: ['exec:build'],
				options: {
					atBegin: true,
				},
			},
		},
		zip: {
			dist: {
				src: ['./build/**/*'],
				cwd: './build/',
				dest: './dist/<%= meta.slug %>-v<%= meta.version %>.zip',
			},
		},
	});

	// Load plugins.
	grunt.loadNpmTasks('grunt-compile-handlebars');
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-exec');
	grunt.loadNpmTasks('grunt-replace');
	grunt.loadNpmTasks('grunt-zip');

	// Create tasks.
	grunt.registerTask('build', 'Compile plugin assets', ['clean', 'replace:php', 'exec:build', 'copy', 'compile-handlebars:readme']);
	grunt.registerTask('release', 'Prepare plugin for release', ['build', 'compile-handlebars:manifest']);
	grunt.registerTask('test', 'Test compiled plugin for release', ['exec:test']);
	grunt.registerTask('install', 'Install WP plugins/dependencies for local development', ['unzip']);
};