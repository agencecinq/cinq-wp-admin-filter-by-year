# CINQ Admin Filter By Year

WordPress plugin that adds a year drop-down filter to the admin list screens of custom post types. No settings screen.

WordPress itself only filters these lists by month, with its “All dates” drop-down. It can filter them by year, through the public `year` query var (`edit.php?post_type=project&year=2024`), but offers no way to pick a year. This plugin adds that drop-down and nothing else: the filtering is done by WordPress.

**Repository:** [`agencecinq/cinq-wp-admin-filter-by-year`](https://github.com/agencecinq/cinq-wp-admin-filter-by-year)

## Requirements

- WordPress 6.0+
- PHP 8.1+

## Lint (WordPress Coding Standards)

```bash
composer install
composer lint          # phpcs
composer lint:fix     # phpcbf (auto-fix)
```

Use `./vendor/bin/phpcs`, not the global `phpcs` binary — the global install does not register the WordPress standards.

## Install

Copy the folder to `wp-content/plugins/cinq-wp-admin-filter-by-year` and activate **CINQ Admin Filter By Year** in the WordPress admin, or with WP-CLI:

```bash
wp plugin activate cinq-wp-admin-filter-by-year
```

To load it on every request without activation, copy the plugin file into `mu-plugins`. It is then listed under **Plugins → Must-Use**.

```bash
cp cinq-wp-admin-filter-by-year.php /path/to/wp-content/mu-plugins/
```

## Translations

French is included in [`languages/`](languages). The plugin does not load translations itself: WordPress loads them from its languages folder, which also works when the plugin is a must-use plugin.

Copy both French files to `wp-content/languages/plugins/`:

- `cinq-wp-admin-filter-by-year-fr_FR.l10n.php`, read by WordPress 6.5 and later;
- `cinq-wp-admin-filter-by-year-fr_FR.mo`, for earlier versions.

To update them after changing a string:

```bash
wp i18n make-pot . languages/cinq-wp-admin-filter-by-year.pot --exclude=vendor
msgmerge --update --backup=none languages/cinq-wp-admin-filter-by-year-fr_FR.po languages/cinq-wp-admin-filter-by-year.pot
# Translate the new strings in the .po file, then:
msgfmt -o languages/cinq-wp-admin-filter-by-year-fr_FR.mo languages/cinq-wp-admin-filter-by-year-fr_FR.po
wp i18n make-php languages
```

`wp i18n make-php` needs WP-CLI 2.8 or later.

## Behaviour

The drop-down sits next to the core filters, above the list table. It submits the public `year` query var, so WordPress filters the list itself: `edit.php?post_type=project&year=2024`.

It only lists years that have posts in the current view: the current status (All, Draft, Trash…) and taxonomy filters are taken into account.

### Optional filter

```php
// Post types that get the year filter (default: every non built-in post type).
add_filter( 'cinq_admin_filter_by_year_post_types', fn () => array( 'project', 'post' ) );
```

## Migration from `wp-admin-filter-by-year`

1. Deactivate and delete `wp-admin-filter-by-year`, then install and activate `cinq-wp-admin-filter-by-year`.
2. The URL parameter is now `year` instead of `y`. Update bookmarks or links such as `edit.php?post_type=project&y=2024`.
3. The functions are renamed and the parse query function is gone. Replace any call or `remove_action()` / `remove_filter()` that targets `wp_admin_filter_by_year_*` with `cinq_admin_filter_by_year_dropdown`.
4. The select no longer has the `filter-by-year` class. Target `#filter-by-year` instead.
5. Query parameters that are not fields of the filter form (for example a taxonomy term opened from a column link) are no longer kept when filtering by year, as with the core date filter.

## Scope

- Post types: every non built-in post type by default, more or fewer through `cinq_admin_filter_by_year_post_types`
- No options, no admin UI, no front-end output

## License

MIT © [CINQ](https://agencecinq.com)
