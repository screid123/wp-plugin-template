<?php
/**
 * Helper functions
 *
 * @package WP_Plugin_Template
 */
namespace WP_Plugin_Template;

/**
 * Display a notice about missing dependencies.
 */
function display_missing_dependencies_notice(): void {
	$message = sprintf(
	/* translators: %s: documentation URL */
		__( '{{NAME}} is missing required dependencies. <a href="%s" target="_blank" rel="noopener noreferer">Learn more.</a>', '{{TEXT_DOMAIN}}' ),
		'{{URI}}#installation'
	);

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		wp_kses(
			$message,
			[
				'a' => [
					'href'   => true,
					'rel'    => true,
					'target' => true,
				],
			]
		)
	);
}
