<?php
/**
 * WP-CLI: create or update service_location pages from a CSV.
 *
 *   wp service-location import <file.csv> [--dry-run]
 *
 * CSV columns: slug,title,city,address,phone,hours,hero_heading,hero_text,services
 *   hours    = "Mon-Fri|8am-8pm;;Sat-Sun|9am-5pm"
 *   services = "Title|Text;;Title|Text"
 *
 * Load from functions.php:
 *   if ( defined( 'WP_CLI' ) && WP_CLI ) { require_once __DIR__ . '/inc/class-service-location-import.php'; }
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

class Service_Location_Import {

	const POST_TYPE = 'service_location';

	/**
	 * Import service locations from a CSV file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the CSV file.
	 *
	 * [--dry-run]
	 * : Report what would change without writing anything.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Flags.
	 */
	public function import( $args, $assoc_args ) {
		if ( ! function_exists( 'update_field' ) ) {
			WP_CLI::error( 'ACF Pro must be active.' );
		}

		$file    = $args[0];
		$dry_run = \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( ! is_readable( $file ) ) {
			WP_CLI::error( "Cannot read {$file}" );
		}

		$handle  = fopen( $file, 'r' );
		$headers = array_map( 'trim', fgetcsv( $handle ) );
		$counts  = array( 'created' => 0, 'updated' => 0, 'skipped' => 0 );
		$line    = 1;

		while ( ( $values = fgetcsv( $handle ) ) !== false ) {
			$line++;

			if ( count( $values ) !== count( $headers ) ) {
				WP_CLI::warning( "Line {$line}: column count does not match the header, skipped." );
				$counts['skipped']++;
				continue;
			}

			$row  = array_combine( $headers, array_map( 'trim', $values ) );
			$slug = sanitize_title( $row['slug'] );

			if ( '' === $slug || '' === $row['title'] ) {
				WP_CLI::warning( "Line {$line}: missing slug or title, skipped." );
				$counts['skipped']++;
				continue;
			}

			$existing = get_page_by_path( $slug, OBJECT, self::POST_TYPE );
			$action   = $existing ? 'updated' : 'created';

			if ( $dry_run ) {
				WP_CLI::log( "[dry-run] {$action}: {$slug}" );
				$counts[ $action ]++;
				continue;
			}

			$post_id = $existing
				? wp_update_post( array( 'ID' => $existing->ID, 'post_title' => $row['title'] ), true )
				: wp_insert_post(
					array(
						'post_type'   => self::POST_TYPE,
						'post_status' => 'draft', // Reviewed before publishing.
						'post_name'   => $slug,
						'post_title'  => $row['title'],
					),
					true
				);

			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( "Line {$line}: " . $post_id->get_error_message() );
				$counts['skipped']++;
				continue;
			}

			$this->write_fields( $post_id, $row );
			WP_CLI::log( "{$action}: {$slug} (#{$post_id})" );
			$counts[ $action ]++;
		}

		fclose( $handle );

		WP_CLI::success(
			sprintf(
				'%d created, %d updated, %d skipped%s.',
				$counts['created'],
				$counts['updated'],
				$counts['skipped'],
				$dry_run ? ' (dry run, nothing written)' : ''
			)
		);
	}

	/**
	 * Writes the ACF fields by field key, so they save correctly on new posts.
	 */
	private function write_fields( $post_id, array $row ) {
		update_field( 'field_sl_city', $row['city'], $post_id );
		update_field( 'field_sl_address', $row['address'], $post_id );
		update_field( 'field_sl_phone', $row['phone'], $post_id );

		$hours = array();
		foreach ( $this->pairs( $row['hours'] ) as $pair ) {
			$hours[] = array(
				'field_sl_hours_days' => $pair[0],
				'field_sl_hours_time' => $pair[1],
			);
		}
		update_field( 'field_sl_hours', $hours, $post_id );

		$sections = array(
			array(
				'acf_fc_layout'         => 'hero',
				'field_sl_hero_heading' => $row['hero_heading'],
				'field_sl_hero_text'    => $row['hero_text'],
			),
		);

		$services = array();
		foreach ( $this->pairs( $row['services'] ) as $pair ) {
			$services[] = array(
				'field_sl_services_item_title' => $pair[0],
				'field_sl_services_item_text'  => $pair[1],
			);
		}

		if ( $services ) {
			$sections[] = array(
				'acf_fc_layout'             => 'services',
				'field_sl_services_heading' => 'Services at this location',
				'field_sl_services_items'   => $services,
			);
		}

		update_field( 'field_sl_sections', $sections, $post_id );
	}

	/**
	 * Splits "A|B;;C|D" into array( array( 'A', 'B' ), array( 'C', 'D' ) ).
	 */
	private function pairs( $value ) {
		$pairs = array();
		foreach ( array_filter( array_map( 'trim', explode( ';;', (string) $value ) ) ) as $item ) {
			$parts   = array_map( 'trim', explode( '|', $item, 2 ) );
			$pairs[] = array( $parts[0], isset( $parts[1] ) ? $parts[1] : '' );
		}
		return $pairs;
	}
}

WP_CLI::add_command( 'service-location', 'Service_Location_Import' );
