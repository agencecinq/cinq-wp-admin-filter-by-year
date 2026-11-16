<?php
/**
 * Plugin Name: CINQ Admin Filter By Year
 * Plugin URI: https://github.com/agencecinq/cinq-wp-admin-filter-by-year
 * Description: Adds a year drop-down filter to the admin list screens of custom post types. No settings screen.
 * Version: 2.0.2
 * Author: CINQ
 * Author URI: https://agencecinq.com/
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: cinq-wp-admin-filter-by-year
 *
 * @package CinqWpAdminFilterByYear
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types that get the year filter.
 *
 * @return array<int, string>
 */
function cinq_wp_admin_filter_by_year_post_types(): array {
	$default_post_types = array_values( get_post_types( array( '_builtin' => false ) ) );

	/**
	 * Filters the post types whose admin list screens get the year filter.
	 *
	 * @since 2.0.0 Renamed from `cinq_admin_filter_by_year_post_types`.
	 *
	 * @param string[] $post_types Post type names. Default every non built-in post type.
	 */
	$post_types = apply_filters( 'cinq_wp_admin_filter_by_year_post_types', $default_post_types );

	if ( ! is_array( $post_types ) ) {
		return array();
	}

	return array_values( array_filter( $post_types, 'is_string' ) );
}

/**
 * Years that have posts in the current list view, most recent first.
 *
 * Runs the query of the list without its year, so the years follow every
 * other filter (status, author, search, month, taxonomy…) and every option
 * returns posts.
 *
 * @return array<int, int>
 */
function cinq_wp_admin_filter_by_year_years(): array {
	global $wpdb, $wp_query;

	$years_query = new \WP_Query();
	$query_vars  = array_merge(
		$wp_query->query_vars,
		array(
			'year'                   => 0,
			'nopaging'               => true,
			'no_found_rows'          => true,
			'fields'                 => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	// Selects the distinct years instead of the IDs, which the `ids` fields return as integers.
	$select_years = static function ( array $clauses, \WP_Query $query ) use ( $wpdb, $years_query ): array {
		if ( $query !== $years_query ) {
			return $clauses;
		}

		return array_merge(
			$clauses,
			array(
				'distinct' => 'DISTINCT',
				'fields'   => "YEAR( {$wpdb->posts}.post_date )",
				'groupby'  => '',
				'orderby'  => '1 DESC',
				'limits'   => '',
			)
		);
	};

	add_filter( 'posts_clauses', $select_years, PHP_INT_MAX, 2 );
	$years = $years_query->query( $query_vars );
	remove_filter( 'posts_clauses', $select_years, PHP_INT_MAX );

	return $years;
}

add_action( 'restrict_manage_posts', 'cinq_wp_admin_filter_by_year_dropdown' );

/**
 * Print the year drop-down next to the core list table filters.
 *
 * The select is named after the public `year` query var, so WordPress
 * filters the list itself when the form is submitted.
 *
 * @param string $post_type Post type of the list screen.
 * @return void
 */
function cinq_wp_admin_filter_by_year_dropdown( string $post_type ): void {
	if ( ! in_array( $post_type, cinq_wp_admin_filter_by_year_post_types(), true ) ) {
		return;
	}

	$years    = cinq_wp_admin_filter_by_year_years();
	$selected = (int) get_query_var( 'year' );

	if ( $selected > 0 && ! in_array( $selected, $years, true ) ) {
		$years[] = $selected;
		rsort( $years );
	}

	if ( empty( $years ) ) {
		return;
	}
	?>
	<label for="filter-by-year" class="screen-reader-text"><?php esc_html_e( 'Filter by year', 'cinq-wp-admin-filter-by-year' ); ?></label>
	<select name="year" id="filter-by-year">
		<option value="0"><?php esc_html_e( 'All years', 'cinq-wp-admin-filter-by-year' ); ?></option>
		<?php foreach ( $years as $year ) : ?>
			<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $year, $selected ); ?>><?php echo esc_html( $year ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}
