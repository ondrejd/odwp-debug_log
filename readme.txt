=== Debug Log Viewer ===
Contributors: ondrejd
Donate link: https://www.paypal.me/ondrejd
Tags: log,debug,development
Requires at least: 4.7
Tested up to: 4.8
Stable tag: 1.0.0

Small [WordPress](https://wordpress.org/) plugin especially for developers that allows better work with debug.log file.

== Description ==

Main features:

* enabling/disabling _WP_ debug mode directly from _WP_ administration
* widget for _WP_ admin dashboard that displays content of the `debug.log` file
* _WP_ admin page (__Administration__ > __Tools__ > __Log Viewer__) that displays content of the `debug.log` file
* rich table list for displaying log records implements all what _WP_ offers - sorting, filtering, pagination, screen options etc.
* source files referenced in log can be easily viewed by built-in viewer (for source code highlighting is used JavaScript library __[highlight.js]__(https://highlightjs.org/))
* stack trace (if present) is displayed as collapsible pane
* Czech and English locales

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload plugin's folder `odwp-debug_log` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set up the plugin and its widgets

== Screenshots ==

1. `screenshot-01.png`
2. `screenshot-02.png`

== Changelog ==

= 1.0.0 =
* initial version
* added to [GitHub](https://github.com/ondrejd/odwp-debug_log)
