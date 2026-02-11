![The Golden Ticket Banner](https://github.com/user-attachments/assets/b34b8e65-79f3-49d1-8f2d-736c59f675e9)

=== The Golden Ticket ===
Contributors: boxedgorilla
Tags: restrict-access, whitelist-pages, blacklist-pages, force-login, access-control, private-pages, membership, woocommerce, login-exception
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 2.2.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Lock down your entire WordPress site by default, then **selectively open** or **protect** individual pages, posts, or products — no code required.

**Choose your mode:**
* **Golden Ticket (Whitelist) Mode** – All content requires login **except** those you "Grant" a Golden Ticket.
* **Inventing Room (Blacklist) Mode** – All content is public **except** those you "Protect" behind a login wall.

Whether you need to share a draft landing page, run a members-only area, or stage a microsite, Golden Ticket gives you:

* **One-Click Grant/Revoke** – Intuitive GUI to add or remove exceptions in seconds.
* **Live Preview** – See your public/private roster update in real time before saving.
* **Pages, Posts & Products** – Manage access for pages, blog posts, and WooCommerce products all in one place.
* **Search & Filter** – Quickly find content in the multi-select list with instant search.
* **Custom Redirect URL** – Send visitors to any login page (including WooCommerce My Account).
* **Admin Bar & Dashboard Widget** – See your factory status at a glance from anywhere in wp-admin.
* **Whimsical Animations** – Confetti, sparkles, Oompa Loompa revoke animations, and ambient golden dust.
* **Onboarding Wizard** – First-time setup guidance so you're never lost.
* **Achievement Milestones** – Fun toasts when you hit ticket milestones.
* **Clean Uninstall** – All options removed when you delete the plugin.
* **Zero Code** – Built on the WordPress Settings API and `wp_safe_redirect()` for rock-solid compatibility.


== Installation ==
1. **Download & Upload**
   - Unzip and upload the entire `golden-ticket-by-boxed-gorilla` folder to `/wp-content/plugins/`.
   - **OR** search "Golden Ticket" under **Plugins → Add New** and click **Install Now**.
2. **Activate**
   - Go to **Plugins → Installed Plugins**, locate "The Golden Ticket" and click **Activate**.
3. **Configure**
   - Visit **Settings → Golden Ticket**.
   - Pick **Golden Ticket (Whitelist)** or **Inventing Room (Blacklist)** mode.
   - Select your exception pages, posts, or products and click **Save Changes**.
4. **Verify**
   - In a private/incognito window, visit both a granted and a protected page to confirm your settings.

== Frequently Asked Questions ==

= How do I open only one page to the public? =
Select **Golden Ticket Mode**, choose that page in the multi-select list, then save. All others redirect to login.

= Can I protect multiple pages? =
Yes — click each title to toggle selection (no Ctrl/Cmd needed), then **Save Changes**.

= Does this work for blog posts and WooCommerce products? =
Yes. Pages, posts, and products all appear in the content selector grouped by type. You can also use the bulk product checkboxes for WooCommerce.

= Can I use a custom login page? =
Yes. Enter your custom login URL in the **Login Redirect URL** field (e.g., your WooCommerce My Account page).

= What happens if I deactivate the plugin? =
All pages revert to their default visibility — no forced login or redirects.

= What happens if I delete the plugin? =
All plugin options are removed from the database via `uninstall.php`. No traces left behind.

= Where can I report bugs or request features? =
On GitHub: https://github.com/boxedgorilla/golden-ticket. Feel free to open an issue!

== Screenshots ==
1. **Settings Interface** – Select mode, pick pages/posts/products, and get instant live feedback.
2. **Live Preview** – Watch your public roster animate as you make changes with ticket-stub styling and type badges.
3. **Front-End Redirect** – Protected content automatically sends visitors to your login screen.

== Changelog ==
= 2.2.0 =
* Added **cursor sparkle trail** — golden sparkles follow your mouse across the settings page.
* Added **animated ticket counter** with smooth count-up/down and bounce effect.
* Added **grant stamp flash** — visual stamp overlay when items are added to the preview.
* Added **chocolate river border** — flowing gradient animation on the preview column.
* Added **jelly wobble** hover effect on preview items.
* Added **mode-aware ambient particles** — golden dust in Golden Ticket mode, iridescent soap bubbles in Inventing Room mode.
* Added **multi-shape sparkles** (stars, circles, diamonds) on mode switch cascade.
* Added **logo easter egg** — click the Golden Ticket heading 5 times for a jackpot surprise.
* Added **Wonka wisdom footer** with rotating inspirational quotes.
* Added **save button shimmer** idle animation.
* Added **revoke button sweep** hover effect.
* Expanded **Oompa Loompa quotes** from 15 to 20 messages with mini sparkle burst on revoke.
* Improved **confetti** with star shapes, chocolate colors, and floating golden ticket pieces.
* Enhanced **inventing mode** with purple/magenta neon glow theme.
* Visual polish inspired by Wonka, Nerds, and classic whimsical brands.

= 2.1.0 =
* Added support for **blog posts** and any **custom post type** (not just pages).
* Added **search/filter** for the content selector.
* Added **custom redirect URL** for login pages (WooCommerce My Account, etc.).
* Added **admin bar** showing ticket/protection count.
* Added **dashboard widget** with factory status overview.
* Added **list table columns** showing ticket status on Pages and Posts screens.
* Added **onboarding notice** for first-time users.
* Added **achievement milestones** (first ticket, 10 items, 50 items).
* Added **uninstall.php** for clean database removal.
* Added content **type badges** (Page/Post/Product) in the live preview.
* Added **ambient golden dust** particles and mode-switch sparkle effects.
* Added **ticket-stub design** for preview list items.
* Improved **confetti** with multiple shapes (rectangles, circles, tickets).
* Used `wp_safe_redirect()` instead of `wp_redirect()` for better security.
* Used `is_singular()` for broader content type coverage.
* Internationalization: all strings wrapped for translation.
* Performance: static-cached `gt_get_all_items()` helper avoids duplicate DB queries.

= 2.0.0 =
* Added **Inventing Room (Blacklist) Mode** (reverse logic).
* Enhanced WooCommerce bulk-grant/revoke UI.
* Live preview performance improvements.
* Extracted CSS and JS into separate files for maintainability.
* Renamed all internal prefixes from `fle_` to `gt_` for consistency.

= 1.0.0 =
* Initial release: Whitelist-only mode, page-level exceptions, force-login redirect.

== Upgrade Notice ==
### 2.2.0
Major visual polish: cursor sparkle trails, animated counters, chocolate river borders, mode-aware ambient particles, logo easter egg, Wonka wisdom footer, and more whimsical animations. No settings changes required.

### 2.1.0
Adds blog post support, search filtering, custom redirect URLs, admin bar, dashboard widget, and visual polish. Existing settings are preserved.

### 2.0.0
Introduces Blacklist Mode and WooCommerce bulk operations. This is a clean break from v1.x — settings will need to be reconfigured after updating.

== Credits ==
Developed by Boxed Gorilla LLC — https://boxedgorilla.com

== License ==
This plugin is licensed under the GNU General Public License v2.0 or later.
