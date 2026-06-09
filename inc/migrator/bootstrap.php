<?php
/**
 * Migrator Bootstrap — wires REST routes, importer factory, and admin assets.
 *
 * Registers three REST endpoints under `/sureforms/v1/migrator/` by filtering
 * into `srfm_rest_api_endpoints`:
 *
 *   GET  /migrator/sources                          — list installable sources
 *   GET  /migrator/sources/(?P<key>[a-z0-9]+)/forms — list forms in one source
 *   POST /migrator/sources/(?P<key>[a-z0-9]+)/import — import selected forms
 *
 * Each route uses `Helper::get_items_permissions_check` for capability gating,
 * matching the existing pattern in `inc/rest-api.php`.
 *
 * @package sureforms
 * @since   2.11.0
 */

namespace SRFM\Inc\Migrator;

use SRFM\Inc\Helper;
use SRFM\Inc\Migrator\Importers\Cf7_Importer;
use SRFM\Inc\Migrator\Importers\Gravity_Importer;
use SRFM\Inc\Migrator\Importers\Ninja_Importer;
use SRFM\Inc\Migrator\Importers\Wpforms_Importer;
use SRFM\Inc\Traits\Get_Instance;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap
 *
 * @since 2.11.0
 */
class Bootstrap {
	use Get_Instance;

	/**
	 * Allowlist of source keys → importer classes.
	 *
	 * @var array<string,string>
	 */
	private $importer_classes = [
		'cf7'     => Cf7_Importer::class,
		'wpforms' => Wpforms_Importer::class,
		'gravity' => Gravity_Importer::class,
		'ninja'   => Ninja_Importer::class,
	];

	/**
	 * Constructor — hook into the REST endpoint filter.
	 *
	 * @since 2.11.0
	 */
	public function __construct() {
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_routes' ] );
	}

	/**
	 * Append migrator routes to the SureForms REST endpoint registry.
	 *
	 * @since 2.11.0
	 *
	 * @param array<string,array<string,mixed>> $endpoints Existing endpoint registry.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_routes( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			$endpoints = [];
		}

		$endpoints['migrator/sources'] = [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_list_sources' ],
			'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
		];

		$endpoints['migrator/sources/(?P<key>[a-z0-9]+)/forms'] = [
			'methods'             => 'GET',
			'callback'            => [ $this, 'rest_list_forms' ],
			'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
			'args'                => [
				'key' => [
					'sanitize_callback' => 'sanitize_key',
				],
			],
		];

		$endpoints['migrator/sources/(?P<key>[a-z0-9]+)/import'] = [
			'methods'             => 'POST',
			'callback'            => [ $this, 'rest_import_forms' ],
			'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
			'args'                => [
				'key'           => [
					'sanitize_callback' => 'sanitize_key',
				],
				'form_ids'      => [
					'sanitize_callback' => [ $this, 'sanitize_form_ids' ],
					'default'           => [],
				],
				'dry_run'       => [
					'sanitize_callback' => 'rest_sanitize_boolean',
					'default'           => false,
				],
				'behavior'      => [
					'sanitize_callback' => [ $this, 'sanitize_behavior' ],
					'default'           => [],
				],
				'post_status'   => [
					// Migrator imports default to draft so the user reviews the
					// migrated markup before publishing; pass 'publish' to override.
					'sanitize_callback' => static function ( $value ) {
						return in_array( $value, [ 'draft', 'publish' ], true ) ? $value : 'draft';
					},
					'default'           => 'draft',
				],
				'skip_existing' => [
					'sanitize_callback' => 'rest_sanitize_boolean',
					'default'           => false,
				],
			],
		];

		return $endpoints;
	}

	/**
	 * GET /migrator/sources — list importable plugins.
	 *
	 * @since 2.11.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_list_sources( $request ) {
		$nonce_error = $this->verify_nonce( $request );
		if ( $nonce_error instanceof WP_Error ) {
			return $nonce_error;
		}
		$out = [];
		foreach ( array_keys( $this->importer_classes ) as $key ) {
			$importer = $this->get_importer( $key );
			if ( null === $importer ) {
				continue;
			}
			$installed = $importer->exist();
			// Single per-form scan: list_forms() already resolves each form's
			// imported_srfm_id (via the memoized imported-map), so derive both
			// the total and imported counts from its rows in one pass — this
			// endpoint runs on every onboarding boot + Forms-listing page load
			// (review #2).
			$forms          = $installed ? $importer->list_forms() : [];
			$forms_count    = count( $forms );
			$imported_count = count(
				array_filter(
					$forms,
					static function ( $row ) {
						return ! empty( $row['imported_srfm_id'] );
					}
				)
			);
			// `pending` is what the onboarding picker actually offers.
			$pending_count = max( 0, $forms_count - $imported_count );
			$out[]         = [
				'key'            => $importer->get_key(),
				'title'          => $importer->get_title(),
				'installed'      => $installed,
				'form_count'     => $forms_count,
				'imported_count' => $imported_count,
				'pending_count'  => $pending_count,
			];
		}
		return new WP_REST_Response( [ 'sources' => $out ], 200 );
	}

	/**
	 * GET /migrator/sources/{key}/forms — list forms inside one source.
	 *
	 * @since 2.11.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_list_forms( $request ) {
		$nonce_error = $this->verify_nonce( $request );
		if ( $nonce_error instanceof WP_Error ) {
			return $nonce_error;
		}
		$key      = (string) $request->get_param( 'key' );
		$importer = $this->get_importer( $key );
		if ( null === $importer ) {
			return new WP_REST_Response(
				[ 'message' => __( 'Unknown migration source.', 'sureforms' ) ],
				404
			);
		}
		if ( ! $importer->exist() ) {
			return new WP_REST_Response(
				[ 'message' => __( 'Source plugin is not active.', 'sureforms' ) ],
				400
			);
		}
		return new WP_REST_Response(
			[ 'forms' => $importer->list_forms() ],
			200
		);
	}

	/**
	 * POST /migrator/sources/{key}/import — import (or dry-run) selected forms.
	 *
	 * @since 2.11.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function rest_import_forms( $request ) {
		$nonce_error = $this->verify_nonce( $request );
		if ( $nonce_error instanceof WP_Error ) {
			return $nonce_error;
		}
		$key      = (string) $request->get_param( 'key' );
		$importer = $this->get_importer( $key );
		if ( null === $importer ) {
			return new WP_REST_Response(
				[ 'message' => __( 'Unknown migration source.', 'sureforms' ) ],
				404
			);
		}
		if ( ! $importer->exist() ) {
			return new WP_REST_Response(
				[ 'message' => __( 'Source plugin is not active.', 'sureforms' ) ],
				400
			);
		}
		$form_ids = $request->get_param( 'form_ids' );
		if ( ! is_array( $form_ids ) ) {
			$form_ids = [];
		}
		$dry_run  = (bool) $request->get_param( 'dry_run' );
		$behavior = $request->get_param( 'behavior' );
		if ( ! is_array( $behavior ) ) {
			$behavior = [];
		}
		$post_status = (string) $request->get_param( 'post_status' );
		// `skip_existing` is the onboarding-step's safe-default — when no per-form
		// behavior is provided, any source form already mapped to a SureForms
		// post is skipped instead of overwritten. Per-form entries in $behavior
		// still take precedence (explicit beats implicit).
		$skip_existing = (bool) $request->get_param( 'skip_existing' );
		$result        = $importer->import_forms( $form_ids, $dry_run, $behavior, $post_status, $skip_existing );
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Sanitize the re-import behavior map — keys are source-form ids, values
	 * are one of `update`, `skip`, `create`. Unknown actions and non-scalar
	 * keys are dropped silently so the migrator falls back to its default
	 * `update` behavior.
	 *
	 * @since 2.11.0
	 *
	 * @param mixed $value Raw value from the REST request.
	 * @return array<string,string>
	 */
	public function sanitize_behavior( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}
		$allowed = [ 'update', 'skip', 'create' ];
		$out     = [];
		foreach ( $value as $source_id => $action ) {
			$action = is_string( $action ) ? strtolower( $action ) : '';
			if ( ! in_array( $action, $allowed, true ) ) {
				continue;
			}
			$key = is_int( $source_id ) || is_numeric( $source_id ) ? (string) (int) $source_id : sanitize_text_field( (string) $source_id );
			if ( '' === $key ) {
				continue;
			}
			$out[ $key ] = $action;
		}
		return $out;
	}

	/**
	 * Sanitize a list of form ids — accepts ints or alphanumeric strings (source
	 * plugins use both).
	 *
	 * @since 2.11.0
	 *
	 * @param mixed $value Raw value.
	 * @return array<int,string>
	 */
	public function sanitize_form_ids( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}
		$out = [];
		foreach ( $value as $v ) {
			if ( is_int( $v ) || is_numeric( $v ) ) {
				$out[] = (string) (int) $v;
				continue;
			}
			if ( is_string( $v ) ) {
				$out[] = sanitize_text_field( $v );
			}
		}
		return $out;
	}

	/**
	 * Resolve a source key into an importer instance.
	 *
	 * @since 2.11.0
	 *
	 * @param string $key Source key.
	 * @return Base_Migrator|null
	 */
	private function get_importer( $key ) {
		$key = sanitize_key( (string) $key );
		if ( ! isset( $this->importer_classes[ $key ] ) ) {
			return null;
		}
		$class = $this->importer_classes[ $key ];
		if ( ! class_exists( $class ) ) {
			return null;
		}
		$instance = new $class();
		return $instance instanceof Base_Migrator ? $instance : null;
	}

	/**
	 * Verify the WordPress REST cookie nonce.
	 *
	 * Returns a WP_Error the REST callback can short-circuit on. The shape matches
	 * REST conventions (rest_cookie_invalid_nonce, 403) so api.js receives a
	 * properly structured error response instead of an AJAX-shaped envelope.
	 *
	 * @since 2.11.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_Error|null
	 */
	private function verify_nonce( $request ) {
		$nonce = (string) $request->get_header( 'X-WP-Nonce' );
		if ( wp_verify_nonce( sanitize_text_field( $nonce ), 'wp_rest' ) ) {
			return null;
		}
		return new WP_Error(
			'rest_cookie_invalid_nonce',
			__( 'Security verification failed. Please refresh the page and try again.', 'sureforms' ),
			[ 'status' => 403 ]
		);
	}
}
