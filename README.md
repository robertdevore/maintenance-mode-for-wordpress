# Maintenance Mode for WordPress®

A free WordPress® plugin that enables a fully customizable maintenance mode with a dedicated landing page. 

Built with the WordPress® core editor, this plugin locks down access to the frontend for non-logged-in users while keeping administrators in control.

## Features

- **Custom Maintenance Pages:** Create and edit maintenance pages using the WordPress block editor.
- **Admin-Only Access:** Allow logged-in administrators to bypass maintenance mode.
- **REST API Control:** Restrict REST API access for non-logged-in users during maintenance mode.
- **Launch Scheduling:** Set an optional launch date for your maintenance mode.
- **Customizable Frontend Appearance:** Use the built-in block editor to style your maintenance page.
- **503 HTTP Status Code:** Ensure search engines recognize your site's temporary downtime.
* * *

## Installation

1. Download the plugin files from the [GitHub repository](https://github.com/robertdevore/maintenance-mode-for-wordpress/).

2. Upload the plugin to your WordPress® installation via the **Plugins > Add New > Upload Plugin** menu.

3. Activate the plugin via the **Plugins** screen in WordPress®.

## Usage

### 1. Enable Maintenance Mode

1. Navigate to **Maintenance > Settings** in your WordPress® admin panel.
2. Check the box labeled **Enable Maintenance Mode**.

### 2. Set a Maintenance Page

1. Go to **Maintenance** in the WordPress® admin panel.
2. Create or select a maintenance page using the block editor.
3. Assign the maintenance page in the **Maintenance > Settings** menu under **Maintenance Mode Page**.

### 3. Schedule a Launch Date (Optional)

1. In the **Maintenance > Settings** menu, enter a date under **Launch Date**.

2. Save your settings.

If a launch date is specified, maintenance mode will automatically end on that date.

## Developer Notes

### Actions and Filters

- **Actions:**
    - `template_redirect`: Restricts frontend access for non-logged-in users when maintenance mode is enabled.
    - `rest_api_init`: Blocks REST API access for non-logged-in users during maintenance mode.
- **Filters:**
    - `the_content`: Used to render the content of the assigned maintenance page.

### Code Highlights

- **Custom Post Type:**
    - Maintenance pages are created as a custom post type `maintenance_page`.
    - They support title and editor fields.
- **503 Status Header:**
    - The plugin sends a `503 Service Unavailable` HTTP status code when maintenance mode is active.
- **REST API Restriction:**
    - Non-logged-in users receive a `403 Forbidden` response when attempting to access the REST API.

## FAQs

### Why can't I see the maintenance page?

Make sure you've assigned a published page under **Maintenance > Settings > Maintenance Mode Page**.

### How do I bypass maintenance mode?

Log in to WordPress® as an administrator. Maintenance mode restrictions do not apply to logged-in users.

### Will this affect my site's SEO?

No, the plugin sends a `503 Service Unavailable` status code to notify search engines that the downtime is temporary.

## Troubleshooting

1. **Settings Not Saving:**
    - Ensure your WordPress installation has sufficient permissions to save plugin options.

2. **REST API Issues:**
    - Check that you are logged in as an administrator if you need REST API access during maintenance mode.

## Contributing

1. Fork the repository on [GitHub](https://github.com/robertdevore/maintenance-mode-for-wordpress/).
2. Create a feature branch for your changes.
3. Submit a pull request for review.

## License

This plugin is licensed under the [GPL-2.0+ License](http://www.gnu.org/licenses/gpl-2.0.txt).