# WP Admin Filter By Year

WordPress plugin that adds a year dropdown filter in the admin for custom post type list screens.

## Features

- Year dropdown on edit screens (edit.php) for non–built-in post types
- Year options respect active filters (e.g. taxonomy such as author)
- Other query params (taxonomy, pagination, etc.) are preserved when filtering by year

## Installation

1. Download or clone this repository
2. Copy the `wp-admin-filter-by-year` folder into `wp-content/plugins/`
3. Activate the plugin under **Plugins** (or via WP-CLI: `wp plugin activate wp-admin-filter-by-year`)

## Requirements

- WordPress 5.x+
- PHP 7.4+

## Publishing to GitHub

1. **Create a new repository on GitHub** (empty, no README)
2. **In a terminal**, from the plugin folder:

```bash
cd wp-content/plugins/wp-admin-filter-by-year
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/YOUR_USER/YOUR_REPO.git
git push -u origin main
```

3. Replace `YOUR_USER` and `YOUR_REPO` with your GitHub username and repository name.

To install the plugin from GitHub on another site:

```bash
cd wp-content/plugins
git clone https://github.com/YOUR_USER/YOUR_REPO.git wp-admin-filter-by-year
```

## License

GPL v2 or later
