<?php
/**
 * Stubs for WP 6.9 Abilities API and MCP Adapter.
 *
 * These symbols are not yet available in phpstan-wordpress stubs.
 *
 * @package sureforms
 * @since x.x.x
 */

namespace {
	/**
	 * Stub for WP_Ability.
	 */
	class WP_Ability {
		/**
		 * Get ability name.
		 *
		 * @return string
		 */
		public function get_name() {
			return '';
		}

		/**
		 * Get ability metadata.
		 *
		 * @return array<string,mixed>
		 */
		public function get_meta() {
			return [];
		}
	}

	/**
	 * Register an ability.
	 *
	 * @param string              $id   Ability ID.
	 * @param array<string,mixed> $args Ability arguments.
	 * @return void
	 */
	function wp_register_ability( $id, $args ) {}

	/**
	 * Get all registered abilities.
	 *
	 * @return array<string,WP_Ability>
	 */
	function wp_get_abilities() {
		return [];
	}

	/**
	 * Check if an ability is registered.
	 *
	 * @param string $id Ability ID.
	 * @return bool
	 */
	function wp_has_ability( $id ) {
		return false;
	}

	/**
	 * Register an ability category.
	 *
	 * @param string              $slug Category slug.
	 * @param array<string,mixed> $args Category arguments.
	 * @return void
	 */
	function wp_register_ability_category( $slug, $args ) {}

	/**
	 * Check if an ability category is registered.
	 *
	 * @param string $slug Category slug.
	 * @return bool
	 */
	function wp_has_ability_category( $slug ) {
		return false;
	}
}

namespace WP\MCP {
	/**
	 * Stub for MCP Plugin class.
	 */
	class Plugin {}
}

namespace WP\MCP\Transport {
	/**
	 * Stub for HTTP Transport.
	 */
	class HttpTransport {}
}

namespace WP\MCP\Transport\Http {
	/**
	 * Stub for REST Transport.
	 */
	class RestTransport {}
}

namespace WP\MCP\Infrastructure\ErrorHandling {
	/**
	 * Stub for Error Log MCP Error Handler.
	 */
	class ErrorLogMcpErrorHandler {}
}

namespace WP\MCP\Infrastructure\Observability {
	/**
	 * Stub for Null MCP Observability Handler.
	 */
	class NullMcpObservabilityHandler {}
}

namespace WP\MCP\Adapter {
	/**
	 * Stub for MCP Adapter.
	 */
	class Adapter {
		/**
		 * Create a new MCP server.
		 *
		 * @param string              $slug          Server slug.
		 * @param string              $namespace     REST namespace.
		 * @param string              $route         REST route.
		 * @param string              $name          Server name.
		 * @param string              $description   Server description.
		 * @param string              $version       Server version.
		 * @param string[]            $transports    Transport classes.
		 * @param string              $error_handler Error handler class.
		 * @param string              $observability Observability handler class.
		 * @param string[]            $tools         Tool ability names.
		 * @param array<string,mixed> $resources     Resources.
		 * @param array<string,mixed> $prompts       Prompts.
		 * @return void
		 */
		public function create_server( $slug, $namespace, $route, $name, $description, $version, $transports, $error_handler, $observability, $tools, $resources, $prompts ) {}
	}
}
