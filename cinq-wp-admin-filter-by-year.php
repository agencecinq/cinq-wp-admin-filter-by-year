<?php
/**
 * Plugin Name: CINQ Admin Filter By Year
 * Plugin URI: https://github.com/agencecinq/cinq-wp-admin-filter-by-year
 * Description: Adds a year drop-down filter to the admin list screens of custom post types. No settings screen.
 * Version: 2.0.0
 * Author: CINQ
 * Author URI: https://agencecinq.com/
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: cinq-wp-admin-filter-by-year
 *
 * @package CinqAdminFilterByYear
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types that get the year filter.
 *
 * @return array<int, string>
 */
function cinq_admin_filter_by_year_post_types(): array {
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
 * Follows the status and taxonomy filters of the main query, so every
 * option returns posts.
 *
 * @param string $post_type Post type slug.
 * @return array<int, int>
 */
function cinq_admin_filter_by_year_years( string $post_type ): array {
	global $wpdb, $wp_query;

	$post_status = $wp_query->get( 'post_status' );
	$status_sql  = is_string( $post_status ) && '' !== $post_status
		? $wpdb->prepare( 'AND post_status = %s', $post_status )
		: "AND post_status NOT IN ( 'auto-draft', 'trash' )";

	$tax_sql = $wp_query->tax_query instanceof \WP_Tax_Query
		? $wp_query->tax_query->get_sql( $wpdb->posts, 'ID' )
		: array(
			'join'  => '',
			'where' => '',
		);

	// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Same query as core months_dropdown(); the status and taxonomy clauses are built by $wpdb->prepare() and WP_Tax_Query.
	$years = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT YEAR( post_date )
			FROM $wpdb->posts
			{$tax_sql['join']}
			WHERE post_type = %s
			$status_sql
			{$tax_sql['where']}
			ORDER BY 1 DESC",
			$post_type
		)
	);
	// phpcs:enable

	return array_map( 'intval', $years );
}

/**
 * Print the year drop-down next to the core list table filters.
 *
 * The select is named after the public `year` query var, so WordPress
 * filters the list itself when the form is submitted.
 *
 * @param string $post_type Post type of the list screen.
 * @return void
 */
function cinq_admin_filter_by_year_dropdown( string $post_type ): void {
	if ( ! in_array( $post_type, cinq_admin_filter_by_year_post_types(), true ) ) {
		return;
	}

	$years    = cinq_admin_filter_by_year_years( $post_type );
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

add_action( 'restrict_manage_posts', 'cinq_admin_filter_by_year_dropdown' );
