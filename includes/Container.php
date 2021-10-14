<?php
/**
 * Container class.
 *
 * @package WP_Plugin_Template
 */
declare( strict_types=1 );

namespace WP_Plugin_Template;

use WP_Plugin_Template\Dependencies\Pimple\Container as PimpleContainer;
use WP_Plugin_Template\Dependencies\Psr\Container\ContainerInterface;

/**
 * Extend the PimpleContainer to satisfy the ContainerInterface requirement.
 *
 * @package WP_Plugin_Template
 */
class Container extends PimpleContainer implements ContainerInterface {

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 * @return mixed
	 */
	public function get( string $id ) {
		return $this->offsetGet( $id );
	}

	/**
	 * Whether the container has an entry for the given identifier.
	 *
	 * @param string $id Identifier of the entry to look for.
	 * @return bool
	 */
	public function has( string $id ): bool {
		return $this->offsetExists( $id );
	}

}
