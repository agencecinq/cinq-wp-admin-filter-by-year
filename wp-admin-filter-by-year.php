<?php
/**
 * Plugin Name:       WP Admin Filter By Year
 * Description:       Adds a year dropdown filter on admin post list screens for custom post types.
 * Version:           0.0.0
 * Author:            Jérémy Levron
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-admin-filter-by-year
 */

add_action( 'restrict_manage_posts', 'wp_admin_filter_by_year_restrict_manage_runs', 10, 2 );
add_filter( 'pre_get_posts', 'wp_admin_filter_by_year_parse_query', 10, 1 );



/**
 * Parse query
 *
 * @param WP_Query $query The WP_Query instance (passed by reference).
 *
 * @see https://developer.wordpress.org/reference/hooks/parse_query/
 *
 * @return WP_Query $query
 */
function wp_admin_filter_by_year_parse_query( WP_Query $query ): WP_Query {
	if ( ! ( is_admin() && $query->is_main_query() ) ) {
		return $query;
	}

	$post_types = get_post_types( array( '_builtin' => false ) );

	if ( ! isset( $_REQUEST['y'] ) ) {
		return $query;
	}

	if ( ! in_array( $query->get( 'post_type' ), $post_types ) ) {
		return $query;
	}

	$y = isset( $_REQUEST['y'] ) ? absint( $_REQUEST['y'] ) : 0;
	if ( 0 === $y ) {
		return $query;
	}

	$query->set( 'year', $y );

	return $query;
}

/**
 * Restrict manage runs
 *
 * @param string $post_type The post type slug.
 * @param string $which The location of the extra table nav markup: 'top' or 'bottom' for WP_Posts_List_Table, 'bar' for WP_Media_List_Table.
 *
 * @see https://developer.wordpress.org/reference/hooks/restrict_manage_posts/
 */
function wp_admin_filter_by_year_restrict_manage_runs( string $post_type, string $which ): void {
	$post_types = get_post_types( array( '_builtin' => false ) );

	foreach ( $post_types as $post_type_name ) {

		// only add filter to post type you want
		if ( $post_type === $post_type_name ) {
			$taxonomies = get_object_taxonomies( $post_type, 'names' );
			$tax_query  = array();

			foreach ( $taxonomies as $taxonomy ) {
				if ( empty( $_GET[ $taxonomy ] ) ) {
					continue;
				}
				$term_param = is_array( $_GET[ $taxonomy ] ) ? $_GET[ $taxonomy ][0] : $_GET[ $taxonomy ];
				$term_param = sanitize_text_field( wp_unslash( $term_param ) );
				if ( '' === $term_param ) {
					continue;
				}
				$term = is_numeric( $term_param )
					? get_term( (int) $term_param, $taxonomy )
					: get_term_by( 'slug', $term_param, $taxonomy );
				if ( ! $term instanceof WP_Term ) {
					continue;
				}
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term->term_id,
				);
			}

			$args = array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			);
			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax_query );
			}

			$posts = get_posts( $args );
			$years = array();
			foreach ( $posts as $post ) {
				if ( ! empty( $post->post_date ) ) {
					$years[ (int) gmdate( 'Y', strtotime( $post->post_date ) ) ] = true;
				}
			}
			$values = array_keys( $years );
			rsort( $values );
			$values = array_combine( $values, $values );

			$y = isset( $_GET['y'] ) ? absint( $_GET['y'] ?? 0 ) : '';

			// Keep other GET parameters.
			foreach ( $_GET as $key => $val ) {
				if ( 'y' === $key ) {
					continue;
				}
				if ( is_array( $val ) ) {
					foreach ( $val as $v ) {
						printf(
							'<input type="hidden" name="%s[]" value="%s">',
							esc_attr( sanitize_key( $key ) ),
							esc_attr( is_string( $v ) ? sanitize_text_field( wp_unslash( $v ) ) : '' )
						);
					}
				} else {
					printf(
						'<input type="hidden" name="%s" value="%s">',
						esc_attr( sanitize_key( $key ) ),
						esc_attr( is_string( $val ) ? sanitize_text_field( wp_unslash( $val ) ) : (string) $val )
					);
				}
			}
			?>
			<select name="y" class="filter-by-year">
				<option value="0"><?php _e( 'All years', 'wp-admin-filter-by-year' ); ?></option>
				<?php
				foreach ( $values as $label => $value ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $value ),
						selected( $value, $y, false ),
						esc_html( $label )
					);
				}
				?>
			</select>
			<?php
		}
	}
}
