![The Golden Ticket Banner](https://github.com/user-attachments/assets/b34b8e65-79f3-49d1-8f2d-736c59f675e9)

=== The Golden Ticket ===
Contributors: boxedgorilla  
Tags: restrict-access, whitelist-pages, blacklist-pages, force-login, access-control, private-pages, membership, woocommerce, login-exception  
Requires at least: 5.0  
Tested up to: 6.5  
Stable tag: 2.0  
Requires PHP: 5.6  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

== Description ==
Lock down your entire WordPress site by default, then **selectively open** or **protect** individual pages, posts or products—no code required.

**Choose your mode:**
* **Golden Ticket (Whitelist) Mode** – All pages require login **except** those you “Grant” a Golden Ticket.  
* **Inventing Room (Blacklist) Mode** – All pages are public **except** those you “Revoke” behind a login wall.  

Whether you need to share a draft landing page, run a members‑only area, or stage a microsite, Golden Ticket gives you:

* **One‑Click Grant/Revoke** – Intuitive GUI to add or remove exceptions in seconds.  
* **Live Preview** – See your public/private roster update in real time before saving.  
* **WooCommerce Support** – Bulk‑grant or revoke access to products.  
* **Zero Code** – Built on the WordPress Settings API, `is_page()`, and `wp_safe_redirect()` for rock‑solid compatibility.  
* **Developer Hooks** – Extend with filters to whitelist custom post types, user roles, or REST endpoints.  


== Installation ==
1. **Download & Upload**  
   - Unzip and upload the entire `golden-ticket` folder to `/wp-content/plugins/`.  
   - **OR** search “Golden Ticket” under **Plugins → Add New** and click **Install Now**.  
2. **Activate**  
   - Go to **Plugins → Installed Plugins**, locate “The Golden Ticket” and click **Activate**.  
3. **Configure**  
   - Visit **Settings → Golden Ticket**.  
   - Pick **Whitelist** or **Blacklist** mode.  
   - Select your exception pages (or products) and click **Save Changes**.  
4. **Verify**  
   - In a private/incognito window, visit both a granted and a protected page to confirm your settings.

== Frequently Asked Questions ==

= How do I open only one page to the public? =  
Select **Whitelist Mode**, choose that page in the multi‑select list, then save. All others redirect to login.

= Can I protect multiple pages? =  
Yes—hold Ctrl/Cmd and click each title, then **Save Changes**.

= Does this work for WooCommerce products? =  
Absolutely. Switch to the **Products** panel in your settings, choose Grant or Revoke, and bulk‑select all or individual products.

= I need role‑based exceptions. Possible? =  
Free version only handles IDs. Use the `the_golden_tickets_allowed_posts` filter to add IDs conditionally, or upgrade when Pro is available.

= What happens if I deactivate the plugin? =  
All pages revert to their default visibility—no forced login or redirects.

= Where can I report bugs or request features? =  
On GitHub: https://github.com/boxedgorilla/golden-ticket. Feel free to open an issue!

== Screenshots ==
1. **Settings Interface**  
   ![Settings Interface](assets/screenshot-1.png)  
   Select mode, pick pages/products, and get instant live feedback.  
2. **Live Preview**  
   ![Live Preview](assets/screenshot-2.png)  
   Watch your public roster animate as you make changes.  
3. **Front‑End Redirect**  
   ![Front-End Redirect](assets/screenshot-3.png)  
   Protected pages automatically send visitors to your login screen.

== Changelog ==
= 2.0.0 =
* Added **Blacklist Mode** (reverse logic).  
* Enhanced WooCommerce bulk‑grant/revoke UI.  
* Live preview performance improvements.  
* New developer filters for custom post types & roles.  

= 1.0.0 =
* Initial release: Whitelist‑only mode, page‑level exceptions, force‑login redirect.

== Upgrade Notice ==
### 2.0.0
Introduces Blacklist Mode and WooCommerce bulk operations—please recheck your exception lists after updating.

== Credits ==
Developed by Boxed Gorilla LLC — https://boxedgorilla.com

== License ==
This plugin is licensed under the GNU General Public License v2.0 or later.  
