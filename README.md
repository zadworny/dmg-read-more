# DMG Read More Plugin

## Description
This WordPress plugin adds a custom Gutenberg block and a WP-CLI command. The block allows editors to search for and insert a post link into the content. The WP-CLI command searches for posts containing the custom block within a specified date range. The plugin has been tested with 2.5 million posts in one WordPress blog.

## Installation
1. Upload the `dmg-read-more` folder to the `/wp-content/plugins/` directory.
2. Alternatively, use the WP `Plugins` page in the admin dashboard: `Add New Plugin` > `Upload Plugin` > `Choose file` (.zip).
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Gutenberg Block
- **Block Name:** Read More
- **Features:**
  - Search and select posts from the InspectorControls.
  - Displays an anchor link with the post title as the text and the post URL as the href.

## Adding the Block
1. In the WordPress editor, add the "Read More" block.
2. Use the InspectorControls to search for and select a post.
3. The block will display a link with the post title and URL.

## WP-CLI Command
- **Command:** `wp dmg-read-more search`
- **Arguments:**
  - `--date-before`: Optional. Default is 30 days ago.
  - `--date-after`: Optional. Default is today. The date format is YYYY-MM-DD.
  - `--batch=50000`: Optional. Default is 100000. The number of results displayed in a batch. Can be lowered on very slow systems.
  - `--max_execution_time=0`: Optional. Not recommended. Can be used when experiencing very bad network issues. Sets an infinite timeout.
  - `--dev`: Optional. Displays extra stats at the end: number of posts found, execution time in seconds, and memory used in MB.
  - `> posts.txt`: Optional. Can be added at the end of the query to save data to a file instead of displaying it on the screen.
  - `--allow-root`: Optional. Usually needs to be used when running the CLI as root.
- **Description:** Searches for posts containing the custom block within the specified date range.
- **Results in a file:** When used with `> posts.txt`, the results can be read with `vim posts.txt`, or using `cat`, `less`, or `nano`.

## Notes
Ensure you have WP-CLI installed and configured to use the CLI command.
The plugin is optimized for large databases and uses efficient querying methods.

## Notes for CLI Command
The CLI command can be used in a blog with an infinite number of posts as it works in batches.
It works even in a very poor internet connection environment with regular connection failures (it supports up to 63 seconds of connection loss and this can be increased in the source code).

## Changelog
1.0.0
Initial release with the Gutenberg block and WP-CLI command.

## Author
Sam Zadworny
