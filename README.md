# Maintenance Mode for WordPress®

A free WordPress® plugin that enables a fully customizable **Maintenance Mode** and **Coming Soon Mode** with dedicated landing pages.

Built with the WordPress® core editor, this plugin locks down access to the frontend for non-logged-in users while keeping administrators in control.

## Features

- **Custom Maintenance & Coming Soon Pages:** Create and edit pages using the WordPress block editor.
- **Maintenance Mode (503 Status):** Displays a maintenance page and sends a `503 Service Unavailable` HTTP status to notify search engines of temporary downtime.
- **Coming Soon Mode (200 Status):** Displays a coming soon page without restricting access to search engines, useful for pre-launch SEO.
- **Admin-Only Access:** Allow logged-in administrators to bypass maintenance and coming soon mode.
- **REST API Control:** Restrict REST API access for non-logged-in users during maintenance mode.
- **Launch Scheduling:** Set an optional launch date for automatic mode deactivation.
- **Customizable Frontend Appearance:** Use the block editor to style your maintenance or coming soon page.
* * *

## Installation

1. Download the plugin files from the [GitHub repository](https://github.com/robertdevore/maintenance-mode-for-wordpress/).
2. Upload the plugin to your WordPress® installation via **Plugins > Add New > Upload Plugin**.
3. Activate the plugin via the **Plugins** screen in WordPress®.

## Usage

### 1. Enable Maintenance or Coming Soon Mode

1. Navigate to **Maintenance > Settings** in your WordPress® admin panel.
2. Check the box for either:
   - **Enable Maintenance Mode** (restricts site access with a `503` response).
   - **Enable Coming Soon Mode** (displays a coming soon page with a `200` response).
3. Only one mode can be active at a time.

### 2. Set a Maintenance or Coming Soon Page

1. Go to **Maintenance** in the WordPress® admin panel.
2. Create or select a page using the block editor.
3. Assign the page in **Maintenance > Settings** under **Maintenance Mode Page**.

### 3. Schedule a Launch Date (Optional)

1. In **Maintenance > Settings**, enter a date under **Launch Date**.
2. Save your settings.

If a launch date is specified, the active mode will automatically end on that date.

## Developer Notes

### Actions and Filters

- **Actions:**
    - `template_redirect`: Restricts frontend access for non-logged-in users when maintenance mode is enabled.
    - `rest_api_init`: Blocks REST API access for non-logged-in users during maintenance mode.
- **Filters:**
    - `the_content`: Used to render the content of the assigned maintenance or coming soon page.

### Code Highlights

- **Custom Post Type:**
    - Maintenance & Coming Soon pages are created as a custom post type `maintenance_page`.
    - They support title and editor fields.
- **HTTP Status Codes:**
    - **Maintenance Mode:** Sends a `503 Service Unavailable` status code to indicate temporary downtime.
    - **Coming Soon Mode:** Sends a `200 OK` status code, allowing search engines to index the page.
- **REST API Restriction:**
    - Non-logged-in users receive a `403 Forbidden` response when attempting to access the REST API (only in Maintenance Mode).

## FAQs

### What's the difference between Maintenance Mode and Coming Soon Mode?

- **Maintenance Mode:** Temporarily restricts access to your site and sends a `503` status code to prevent search engines from indexing during downtime.
- **Coming Soon Mode:** Allows you to display a coming soon page **without** restricting search engine access (sends a `200` status).

### Why can't I see the maintenance or coming soon page?

Make sure you've assigned a published page under **Maintenance > Settings > Maintenance Mode Page**.

### How do I bypass Maintenance or Coming Soon Mode?

Log in to WordPress® as an administrator. The restrictions do not apply to logged-in users.

### Will this affect my site's SEO?

- **Maintenance Mode:** Sends a `503` status, notifying search engines that the downtime is temporary (recommended for short-term maintenance).
- **Coming Soon Mode:** Sends a `200` status, allowing indexing (recommended for site pre-launch).

## Troubleshooting

1. **Settings Not Saving:**
    - Ensure your WordPress installation has sufficient permissions to save plugin options.

2. **REST API Issues:**
    - Check that you are logged in as an administrator if you need REST API access during maintenance mode.

3. **Maintenance Mode Not Enforcing a 503 Response:**
    - Some caching plugins may interfere with HTTP response headers. Try clearing your cache.

## Contributing

1. Fork the repository on [GitHub](https://github.com/robertdevore/maintenance-mode-for-wordpress/).
2. Create a feature branch for your changes.
3. Submit a pull request for review.

## License

This plugin is licensed under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).