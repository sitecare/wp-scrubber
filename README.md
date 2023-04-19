# WordPress Scrubber

> This WordPress plugin provides a command-line interface for scrubbing sensitive user and comment data from a WordPress installation.

[![Support Level](https://img.shields.io/badge/support-beta-blueviolet.svg)](#support-level) [![GPLv2 License](https://img.shields.io/github/license/10up/wp-scrubber.svg)](https://github.com/10up/wp-scrubber/blob/develop/LICENSE.md)

## Installation

1. Clone or download the plugin files into your WordPress plugins directory.
   1. Note: If on WordPress VIP, the plugin should be added to the `/client-mu-plugins` directory.
2. Activate the plugin through the WordPress admin interface or via WP-CLI.
3. Set the `WP_ENVIRONMENT_TYPE` to `local` or `staging`.

## Usage

The plugin provides a WP-CLI command called `wp scrub all` that will scrub all user and comment data from the WordPress database. This command can only be run on non-production environments, unless overridden with `wp_scrubber_allow_on_production`.

To use the command, open up your terminal and navigate to your WordPress installation. Then run the following command:

```
wp scrub all
```

When creating an export for local development, best practice is to export a scrubbed database from a lower environment:
 * Copy production into a lower environment (staging, develop, preprod, etc...)
 * Scrub the data on the lower environment with the `wp scrub all` command.
 * Export the scrubbed database.

Note: On WordPress VIP, scrubbing commands will occur automatically when copying from production to a lower environment. 

## Scrubbed Data

### Users

 * All passwords are replaced with `password`.
 * Emails are replaced with dummy values.
 * `display_name` is replaced with `user_login` values.

### Comments

 * Comment and Comment Meta tables are completely emptied.

## CLI Arguments

 * `--allowed-domains` - Comma separated list of email domains. Any WordPress user with this email domain will be ignored by the scrubbing scripts. `10up.com` and `get10up.com` are ignored by default.
   * ex: `10updocker wp scrub all --allowed-domains=example.com,example.net`
 * `--allowed-emails` - Comma separated list of email addresses. Any WordPress user with this email will be ignored by the scrubbing scripts.
   * ex: `10updocker wp scrub all --allowed-emails=user1@example.com,user2@example.com`

## Extensibility

WP Scrubber includes several filters and actions which allows developers to hook into the scrubbing process and add their own rules.

### How can I add my own scrubbing rules?

Additional scrubbing commands can be added before or after the default commands with `wp_scrubber_before_scrub` and `wp_scrubber_after_scrub`.

### How do I allow this on production environments?

WP Scrubber uses `wp_get_environment_type()` to ensure it is not accidentally run on a production environment. By default, unless `WP_ENVIRONMENT_TYPE` is defined, WordPress will assume all environments are production environments.

It is encouraged that `WP_ENVIRONMENT_TYPE` be updated to `local` and `staging`, however if you do indeed need to run on production you can add a filter to `wp_scrubber_allow_on_production`, changing the value to `true`.

### How can I change which users are scrubbed?

In addition to CLI arguments, three filters are available to developers

1. `wp_scrubber_allowed_email_domains` - Allows for ignoring users based on their email domain. By default, `10up.com` and `get10up.com` are ignored.
2. `wp_scrubber_allowed_emails` - Allows for ignoring specific users based on their full email address. Helpful if you want to save certain users from an organization, but not all of them.
3. `wp_scrubber_should_scrub_user` - If you conditions are more complex, you can use this filter to check each user individually.

## Support Level

**Beta:** This project is quite new and we're not sure what our ongoing support level for this will be. Bug reports, feature requests, questions, and pull requests are welcome. If you like this project please let us know, but be cautious using this in a Production environment!

## Like what you see?

<p align="center">
<a href="http://10up.com/contact/"><img src="https://10up.com/uploads/2016/10/10up-Github-Banner.png" width="850"></a>
</p>
