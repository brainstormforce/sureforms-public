<?php
/**
 * Migrator Bootstrap — wires REST routes, importer factory, and admin assets.
 *
 * Registers four REST endpoints under `/sureforms/v1/migrator/` by filtering
 * into `srfm_rest_api_endpoints`:
 *
 *   GET  /migrator/sources                          — list installable sources
 *   GET  /migrator/sources/(?P<key>[a-z0-9]+)/forms — list forms in one source
 *   POST /migrator/sources/(?P<key>[a-z0-9]+)/import — import selected forms
 *   POST /migrator/sources/(?P<key>[a-z0-9]+)/forms/(?P<id>[a-zA-Z0-9_-]+)/entries — entries
 *
 * Each route uses `Helper::get_items_permissions_check` for capability gating,
 * matching the existing pattern in `inc/rest-api.php`.
 *
 * @package sureforms
 * @since   x.x.x
 */

namespace SRFM\Inc\Migrator;

use SRFM\Inc\Helper;
use SRFM\Inc\Migrator\Importers\Cf7_Importer;
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
 * @since x.x.x
 */
class Bootstrap {
	use Get_Instance;

	/**
	 * Allowlist of source keys → importer classes.
	 *
	 * @var array<string,string>
	 */
	private $importer_classes = [
		'cf7' => Cf7_Importer::class,
	];

	/**
	 * Constructor — hook into the REST endpoint filter.
	 *
	 * @since x.x.x
	 */
	public function __construct() {
		add_filter( 'srfm_rest_api_endpoints', [ $this, 'register_routes' ] );
	}

	/**
	 * Append migrator routes to the SureForms REST endpoint registry.
	 *
	 * @since x.x.x
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
				'key'      => [
					'sanitize_callback' => 'sanitize_key',
				],
				'form_ids' => [
					'sanitize_callback' => [ $this, 'sanitize_form_ids' ],
					'default'           => [],
				],
				'dry_run'  => [
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
	 * @since x.x.x
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
			$forms_count = $importer->exist() ? count( $importer->list_forms() ) : 0;
			$out[]       = [
				'key'        => $importer->get_key(),
				'title'      => $importer->get_title(),
				'installed'  => $importer->exist(),
				'form_count' => $forms_count,
			];
		}
		return new WP_REST_Response( [ 'sources' => $out ], 200 );
	}

	/**
	 * GET /migrator/sources/{key}/forms — list forms inside one source.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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
		$dry_run = (bool) $request->get_param( 'dry_run' );
		$result  = $importer->import_forms( $form_ids, $dry_run );
		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Resolve a source key into an importer instance.
	 *
	 * @since x.x.x
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
	 * @since x.x.x
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

	/**
	 * Sanitize a list of form ids — accepts ints or alphanumeric strings (source
	 * plugins use both).
	 *
	 * @since x.x.x
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
}
