<?php
/**
 * The Libraries class file.
 *
 * @package GenerateBlocks\Pattern_Library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for handling with libraries.
 *
 * @since 1.9.0
 */
class GenerateBlocks_Libraries extends GenerateBlocks_Singleton {
	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		/**
		 * Whether to load the editor Pattern Library.
		 *
		 * @since 2.5.0
		 * @param bool $enabled Whether the editor library is enabled.
		 */
		if ( ! apply_filters( 'generateblocks_enable_pattern_library', true ) ) {
			// Older Pro versions cannot hide their link to the disabled library themselves.
			wp_add_inline_style( 'generateblocks', '.gblocks-legacy-pattern-library-notice { display: none; }' );
			return;
		}

		$assets = generateblocks_get_enqueue_assets( 'pattern-library' );
		$preview_assets = generateblocks_get_enqueue_assets( 'pattern-preview' );

		wp_enqueue_script(
			'generateblocks-pattern-library',
			GENERATEBLOCKS_DIR_URL . 'dist/pattern-library.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		wp_set_script_translations(
			'generateblocks-pattern-library',
			'generateblocks'
		);

		wp_localize_script(
			'generateblocks-pattern-library',
			'generateBlocksPatternLibrary',
			array(
				'useLegacySidebar' => generateblocks_use_legacy_editor_sidebar(),
				'previewExcludedStyleHandles' => $this->get_preview_excluded_style_handles(),
				'patternPreviewScriptUrl' => add_query_arg(
					'ver',
					$preview_assets['version'],
					GENERATEBLOCKS_DIR_URL . 'dist/pattern-preview.js'
				),
				'siteUrl' => trailingslashit( site_url() ),
				'defaultOpenLibrary' => apply_filters(
					'generateblocks_default_open_pattern_library',
					'gb_default_pro_library'
				),
				'defaultOpenCategory' => apply_filters(
					'generateblocks_default_open_pattern_category',
					''
				),
			)
		);

		wp_enqueue_style(
			'generateblocks-pattern-library',
			GENERATEBLOCKS_DIR_URL . 'dist/pattern-library.css',
			array( 'wp-components' ),
			GENERATEBLOCKS_VERSION
		);
	}

	/**
	 * Editor controls and block edit-state CSS must not style rendered previews.
	 *
	 * Keep handles also registered for frontend use. The legacy GB/Pro bundles
	 * predate block.json and contain selection-dependent rules too.
	 *
	 * @return array Editor-only stylesheet handles.
	 */
	private function get_preview_excluded_style_handles(): array {
		$editor_handles = [ 'wp-edit-blocks', 'generateblocks', 'generateblocks-editor', 'generateblocks-pro', 'generateblocks-pro-editor' ];
		$shared_handles = [];

		foreach ( WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_type ) {
			$editor_handles = array_merge( $editor_handles, (array) $block_type->editor_style_handles );
			$shared_handles = array_merge( $shared_handles, (array) $block_type->style_handles, (array) ( $block_type->view_style_handles ?? [] ) );
		}

		return array_values( array_unique( array_diff( $editor_handles, $shared_handles ) ) );
	}

	/**
	 * The default id.
	 *
	 * @var string The default library id.
	 */
	protected $default_library_id = 'gb_default_free_library';

	/**
	 * Returns all the registered libraries.
	 *
	 * @param bool $enabled_only Filter disabled libraries.
	 *
	 * @return array
	 */
	public function get_all( bool $enabled_only = true ): array {
		// Get saved library data.
		$saved_libraries = get_option( 'generateblocks_pattern_libraries', [] );

		if ( ! is_array( $saved_libraries ) ) {
			$saved_libraries = [];
		}

		// Create library instances for any remote sites that provide the complete set of data.
		$remote_libraries = array_map(
			[ $this, 'create' ],
			array_filter(
				$saved_libraries,
				function ( $saved_library ) {
					return is_array( $saved_library ) &&
						isset( $saved_library['isLocal'] ) &&
						! rest_sanitize_boolean( $saved_library['isLocal'] ) &&
						! empty( $saved_library['id'] ) &&
						is_scalar( $saved_library['id'] ) &&
						! empty( $saved_library['domain'] ) &&
						is_string( $saved_library['domain'] );
				}
			)
		);

		// Allow other libraries to be added.
		$libraries = apply_filters(
			'generateblocks_pattern_libraries',
			$remote_libraries
		);

		if ( ! is_array( $libraries ) ) {
			$libraries = [];
		}

		$libraries = array_filter(
			$libraries,
			function ( $library ) {
				return $this->is_valid_library( $library );
			}
		);

		// Add our default library at the start of the list.
		if ( ! self::exists( $libraries, $this->default_library_id ) ) {
			$default_library = self::get_default();
			$libraries = array_merge( array( $default_library ), $libraries );
		}

		// Loop our libraries and set their status based on their saved value.
		foreach ( $libraries as $key => $library ) {
			$saved_data = array_filter(
				$saved_libraries,
				function ( $saved_library ) use ( $library ) {
					if ( ! is_array( $saved_library ) || ! isset( $saved_library['id'] ) ) {
						return false;
					}

					return $saved_library['id'] === $library->id;
				}
			);

			$saved_data = array_values( $saved_data );

			if ( isset( $saved_data[0]['isEnabled'] ) ) {
				$library->setStatus( $this->normalize_boolean_value( $saved_data[0]['isEnabled'] ) );
			}
		}

		return array_filter(
			$libraries,
			function ( GenerateBlocks_Library_DTO $library ) use ( $enabled_only ) {
				if ( $enabled_only ) {
					return $library->is_enabled;
				}

				return true;
			}
		);
	}

	/**
	 * Returns a single library by id.
	 *
	 * @param string $id The id.
	 *
	 * @return GenerateBlocks_Library_DTO|null
	 */
	public function get_one( string $id ): ?GenerateBlocks_Library_DTO {
		$libraries = self::get_all( false );

		return array_reduce(
			$libraries,
			function ( $result, GenerateBlocks_Library_DTO $library ) use ( $id ) {
				if ( $id === $library->id ) {
					return $library;
				}

				return $result;
			},
			null
		);
	}

	/**
	 * Creates a library from array.
	 *
	 * @param array $data The library in array format.
	 *
	 * @return GenerateBlocks_Library_DTO
	 */
	public function create( array $data ): GenerateBlocks_Library_DTO {
		$data = wp_parse_args(
			$data,
			array(
				'id' => '',
				'name' => '',
				'domain' => '',
				'publicKey' => '',
				'isEnabled' => false,
				'isDefault' => false,
				'isLocal' => false,
			)
		);

		return ( new GenerateBlocks_Library_DTO() )
			->set( 'id', $this->normalize_string_value( $data['id'] ) )
			->set( 'name', $this->normalize_string_value( $data['name'] ) )
			->set( 'domain', $this->normalize_string_value( $data['domain'] ) )
			->set( 'public_key', $this->normalize_string_value( $data['publicKey'] ) )
			->set( 'is_enabled', $this->normalize_boolean_value( $data['isEnabled'] ) )
			->set( 'is_default', $this->normalize_boolean_value( $data['isDefault'] ) )
			->set( 'is_local', $this->normalize_boolean_value( $data['isLocal'] ) );
	}

	/**
	 * Normalize values that are expected to be strings.
	 *
	 * @param mixed $value The value to normalize.
	 * @return string
	 */
	private function normalize_string_value( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return (string) $value;
	}

	/**
	 * Normalize values that are expected to be booleans.
	 *
	 * @param mixed $value The value to normalize.
	 * @return bool
	 */
	private function normalize_boolean_value( $value ): bool {
		if ( ! is_scalar( $value ) ) {
			return false;
		}

		return rest_sanitize_boolean( $value );
	}

	/**
	 * Check that a library has values safe to access through the DTO.
	 *
	 * @param mixed $library The library object to validate.
	 * @return bool
	 */
	private function is_valid_library( $library ): bool {
		if ( ! $library instanceof GenerateBlocks_Library_DTO ) {
			return false;
		}

		$data = $library->serialize();

		if ( empty( $data['id'] ) || ! is_scalar( $data['id'] ) ) {
			return false;
		}

		foreach ( array( 'name', 'domain', 'publicKey' ) as $key ) {
			if ( isset( $data[ $key ] ) && ! is_scalar( $data[ $key ] ) ) {
				return false;
			}
		}

		foreach ( array( 'isEnabled', 'isDefault', 'isLocal' ) as $key ) {
			if ( isset( $data[ $key ] ) && ! is_scalar( $data[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Return the default library.
	 *
	 * @return GenerateBlocks_Library_DTO
	 */
	protected function get_default(): GenerateBlocks_Library_DTO {
		$use_legacy_pattern_library = generateblocks_use_v1_blocks();
		$domain                     = 'https://patterns.generatepress.com';
		$public_key                 = 'betRRDVi8W0Td2eKAS52oVkG4HxqEj9r';

		if (
			$use_legacy_pattern_library ||
			apply_filters( 'generateblocks_force_v1_pattern_library', false )
		) {
			$domain     = 'https://patterns.generateblocks.com';
			$public_key = 'GxroZpidKoLZ2ofWNJdXtanAK9ZozWKo';
		}

		return ( new GenerateBlocks_Library_DTO() )
			->set( 'id', $this->default_library_id )
			->set( 'name', __( 'Free', 'generateblocks' ) )
			->set( 'domain', $domain )
			->set( 'public_key', $public_key )
			->set( 'is_enabled', true )
			->set( 'is_default', true );
	}

	/**
	 * Checks if exists the library.
	 *
	 * @param array  $libraries Array of libraries.
	 * @param string $id The library id.
	 *
	 * @return bool
	 */
	public function exists( array $libraries, string $id ): bool {
		return array_reduce(
			$libraries,
			function ( $result, GenerateBlocks_Library_DTO $library ) use ( $id ) {
				if ( $id === $library->id ) {
					return true;
				}

				return $result;
			},
			false
		);
	}

	/**
	 * Get our cached library data by collection.
	 *
	 * @param string $cache_key The key to look up.
	 * @param array  $query_args Args to filter the results with.
	 * @param string $collection The collection to check.
	 */
	public static function get_cached_data( $cache_key = '', $query_args = [], $collection = '' ) {
		if ( ! $cache_key ) {
			return [];
		}

		if ( 'patterns' !== $collection ) {
			return get_transient( $cache_key );
		}

		$manifest = self::get_cache_manifest( $cache_key );

		// No manifest means no cache. A manifest with a count of 0 means
		// an empty result set was cached deliberately.
		if ( false === $manifest ) {
			return false;
		}

		$cached_data = [];

		for ( $index = 0; $index < $manifest['count']; $index++ ) {
			$chunk = get_transient( self::get_chunk_key( $cache_key, $index ) );

			// External object caches can evict individual chunks, and chunk
			// keys are reused across rebuilds. A missing chunk, or one from
			// a different generation than the manifest, makes the whole set
			// a miss so the data is re-fetched instead of serving partial
			// or mixed results.
			if (
				! is_array( $chunk ) ||
				! isset( $chunk['gen'], $chunk['items'] ) ||
				$manifest['gen'] !== $chunk['gen'] ||
				! is_array( $chunk['items'] )
			) {
				return false;
			}

			$cached_data += $chunk['items'];
		}

		if ( ! empty( $query_args['categoryId'] ) ) {
			$cached_data = array_filter(
				$cached_data,
				function ( $data ) use ( $query_args ) {
					if ( ! isset( $data['categories'] ) || ! is_array( $data['categories'] ) ) {
						return false;
					}

					return in_array( $query_args['categoryId'], $data['categories'] );
				}
			);
		}

		if ( ! empty( $query_args['search'] ) ) {
			$cached_data = array_filter(
				$cached_data,
				function ( $data ) use ( $query_args ) {
					if ( ! is_array( $data ) ) {
						return false;
					}

					foreach ( $data as $key => $value ) {
						if ( is_string( $value ) && stripos( $value, $query_args['search'] ) !== false ) {
							return true;
						}

						continue;
					}

					return false;
				}
			);
		}

		$cached_data = array_values( $cached_data );
		return $cached_data;
	}

	/**
	 * Set the collection cache expiry.
	 */
	public static function get_cache_expiry() {
		return 86400;
	}

	/**
	 * Set our cached data. This function will split our patterns into chunks.
	 *
	 * Chunk keys are stable and reused across rebuilds, so the stored key
	 * count never grows. Each chunk value carries its write's generation and
	 * the manifest — written last — names that generation: a read only
	 * succeeds when every chunk matches the manifest's generation, so a torn
	 * or concurrent rewrite reads as a full miss, never as mixed data. A
	 * write that fails partway unpublishes the manifest (unless the previous
	 * set is still untouched) so the next read re-fetches cleanly.
	 *
	 * @param array  $data The data to cache.
	 * @param string $cache_key The key to set.
	 * @param string $collection The collection to check.
	 */
	public static function set_cached_data( $data = [], $cache_key = '', $collection = '' ) {
		if ( ! $cache_key ) {
			return;
		}

		if ( ! is_array( $data ) ) {
			return;
		}

		$expiration = self::get_cache_expiry();

		if ( 'patterns' !== $collection ) {
			set_transient( $cache_key, $data, $expiration );
			return;
		}

		$gen    = uniqid( '', true );
		$chunks = empty( $data ) ? [] : array_chunk( $data, 20, true );

		foreach ( $chunks as $index => $chunk ) {
			$stored = set_transient(
				self::get_chunk_key( $cache_key, $index ),
				[
					'gen'   => $gen,
					'items' => $chunk,
				],
				$expiration
			);

			if ( ! $stored ) {
				// A failed write after earlier chunks were overwritten leaves
				// the previous set torn, so unpublish the manifest and let
				// the next read miss cleanly. If nothing was overwritten
				// yet, the previous cache is still complete — keep it.
				if ( $index > 0 ) {
					delete_transient( $cache_key );
				}

				return;
			}
		}

		$published = set_transient(
			$cache_key,
			[
				'gen'     => $gen,
				'count'   => count( $chunks ),
				'expires' => time() + $expiration,
			],
			$expiration
		);

		if ( ! $published && ! empty( $chunks ) ) {
			// A surviving stale manifest can only reference the previous
			// generation, which the chunk writes above just overwrote.
			// Remove it so reads miss cleanly. With no chunks written, the
			// previous set is still complete and stays published.
			delete_transient( $cache_key );
		}
	}

	/**
	 * Delete cached library data by collection.
	 *
	 * @param string $cache_key The key to delete.
	 * @param string $collection The collection to delete.
	 */
	public static function delete_cached_data( $cache_key = '', $collection = '' ) {
		if ( ! $cache_key ) {
			return;
		}

		if ( 'patterns' !== $collection ) {
			delete_transient( $cache_key );
			return;
		}

		// Delete the manifest first so concurrent readers miss immediately.
		delete_transient( $cache_key );

		// Best-effort chunk cleanup. Chunk indexes are contiguous at write
		// time, but eviction can leave holes that end the walk early.
		// Anything left behind is unreachable without a manifest and
		// expires on its own.
		$index = 0;

		while ( false !== get_transient( self::get_chunk_key( $cache_key, $index ) ) ) {
			delete_transient( self::get_chunk_key( $cache_key, $index ) );
			++$index;
		}

		// Same best-effort sweep for chunks in the pre-manifest format
		// (`{key}_0`, `{key}_1`, …) left behind by older plugin versions.
		$index = 0;

		while ( false !== get_transient( $cache_key . '_' . $index ) ) {
			delete_transient( $cache_key . '_' . $index );
			++$index;
		}
	}

	/**
	 * Get the expiration timestamp of a patterns cache from its manifest.
	 *
	 * Unlike reading the `_transient_timeout_` option directly, this works
	 * on hosts where transients live in an external object cache.
	 *
	 * @since 2.5.0
	 *
	 * @param string $cache_key The base cache key.
	 * @return int The Unix timestamp the cache expires at, or 0 if no cache.
	 */
	public static function get_cache_expiration( $cache_key = '' ) {
		if ( ! $cache_key ) {
			return 0;
		}

		$manifest = self::get_cache_manifest( $cache_key );

		if ( false === $manifest || empty( $manifest['expires'] ) || ! is_numeric( $manifest['expires'] ) ) {
			return 0;
		}

		return (int) $manifest['expires'];
	}

	/**
	 * Read and validate the chunk manifest for a patterns cache.
	 *
	 * @since 2.5.0
	 *
	 * @param string $cache_key The base cache key.
	 * @return array|false The manifest with `gen` and `count`, or false if missing or invalid.
	 */
	private static function get_cache_manifest( $cache_key ) {
		$manifest = get_transient( $cache_key );

		if (
			! is_array( $manifest ) ||
			empty( $manifest['gen'] ) ||
			! is_scalar( $manifest['gen'] ) ||
			! isset( $manifest['count'] ) ||
			! is_numeric( $manifest['count'] )
		) {
			return false;
		}

		$manifest['gen']   = (string) $manifest['gen'];
		$manifest['count'] = (int) $manifest['count'];

		if ( $manifest['count'] < 0 ) {
			return false;
		}

		return $manifest;
	}

	/**
	 * Build the transient key for a single patterns chunk.
	 *
	 * Keys are stable across rebuilds — the write's generation lives inside
	 * the chunk value, not the key — and the cache key is hashed so chunk
	 * keys stay a fixed length regardless of the library ID, inside
	 * WordPress's 172-character transient name budget for database-stored
	 * transients.
	 *
	 * @since 2.5.0
	 *
	 * @param string $cache_key The base cache key.
	 * @param int    $index The chunk index.
	 * @return string
	 */
	private static function get_chunk_key( $cache_key, $index ) {
		return 'gb_library_chunk_' . md5( $cache_key ) . '_' . $index;
	}

	/**
	 * Previously added the pattern preview query variable.
	 *
	 * @deprecated 2.5.0 Pattern previews are built in the editor and no longer use a frontend route.
	 *
	 * @param array $vars The variables.
	 *
	 * @return array
	 */
	public function add_query_vars( array $vars ): array {
		return $vars;
	}

	/**
	 * Previously added the pattern preview rewrite endpoint.
	 *
	 * @deprecated 2.5.0 Pattern previews are built in the editor and no longer use a frontend route.
	 *
	 * @return void
	 */
	public function rewrite_endpoints(): void {}

	/**
	 * Previously loaded the pattern preview template.
	 *
	 * @deprecated 2.5.0 Pattern previews are built in the editor and no longer use a frontend route.
	 *
	 * @param string $template The current template.
	 *
	 * @return string
	 */
	public function template_viewer( string $template ): string {
		return $template;
	}

	/**
	 * Previously hid the admin bar in the pattern preview template.
	 *
	 * @deprecated 2.5.0 Pattern previews are built in the editor and no longer use a frontend route.
	 *
	 * @param bool $show_admin_bar Whether to show the admin bar.
	 * @return bool
	 */
	public function hide_admin_bar( $show_admin_bar ) {
		return $show_admin_bar;
	}
}

GenerateBlocks_Libraries::get_instance()->init();
