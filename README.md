=== The Golden Ticket ===
Contributors: boxedgorilla
Tags: block-all-pages, restrict-access, page-exception, single-page-access, force-login, whitelist-pages, access-control, login-exception, vip-access, authentication
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block all pages but those you choose—perfect for quick development, landing pages, or limited access sites.

== Description ==

**The Golden Ticket** makes it dead simple to **block every page on your site except for the ones you select**. Instead of hunting down “whitelists” or writing custom code, you simply “Grant” a page a Golden Ticket and it becomes publicly accessible—everything else requires login. This is ideal for:

* **Quick Development & Staging** – Want to share a draft landing page or feature preview with a client, but keep all other pages hidden? GoldenTicket lets you open just that one page and block the rest.
* **Single‐Page Launches** – Publish a mini-site or promo page without exposing unfinished areas of your site.
* **Maintenance Mode Alternative** – Instead of a “coming soon” splash, let only your homepage or contact page remain live.
* **Event or Announcement Pages** – Make a single event registration page public while keeping internal pages private.
* **Microsites & Landing Pages** – Give specific posts or pages a “VIP pass” for public access, while everything else stays behind a login.

**In plain terms:**  
> “Need to ‘hide’ all but one page? GoldenTicket is your tool. Just pick the page(s) you want visible to everyone. All other URLs automatically redirect to the login screen.”

**Main Features:**
* **Block‐All, Except A Few**: Don’t know what a “whitelist” is? No problem—just select the page(s) you want open, and GoldenTicket does the rest.
* **Grant/Revoke with One Click**: A clean GUI lets you add or remove pages from your public roster in seconds.
* **Powered by Core WordPress**: Uses the Settings API, `is_page()`, and `wp_safe_redirect()` for guaranteed compatibility.
* **Live Preview**: See which pages are publicly accessible as you select or deselect them—no guesswork.
* **Developer Hooks**: Extend functionality with filters (for example, whitelist custom post types or user roles in Pro).

== Installation ==

1. **Download & Upload**  
   - Upload the entire `golden-ticket` folder to `/wp-content/plugins/` (using FTP or your hosting file manager).  
   - **OR** install directly from your dashboard: **Plugins → Add New**, search “Golden Ticket,” click **Install Now**.

2. **Activate**  
   - Go to **Plugins → Installed Plugins** and click **Activate** under **The Golden Ticket**.

3. **Configure Pages**  
   - Navigate to **Settings → Golden Ticket**.  
   - Choose **Grant** to open selected pages to everyone (no login).  
   - Choose **Revoke** to send pages back behind the login screen.  

4. **Select Your Pages**  
   - Click one (or hold Ctrl/Cmd and click multiple) page titles from the multi‐select list.  
   - Click **Save Changes**. Any pages granted a Golden Ticket will be publicly accessible; all others will redirect visitors to the login screen.

5. **Verify**  
   - In a private/incognito window, visit a page you did **not** grant. It should redirect to your site’s login page.  
   - Visit a page you **did** grant—it should load normally without requiring login.

== Frequently Asked Questions ==

= How do I “hide” all pages except one? =  
**Answer:** Install and activate GoldenTicket. In **Settings → Golden Ticket**, select **Grant**, choose the one page you want visible, and click **Save Changes**. All other pages will require login.  

= Can I make multiple pages public? =  
Yes—hold Ctrl (Cmd on Mac) and click multiple titles in the page list, then click **Save**. All selected pages will bypass the login screen.  

= What if I want to grant access only to logged-in users of a certain role? =  
GoldenTicket free only supports page IDs. If you need **role-based** exemptions, check out **Golden Ticket Pro** (coming soon), which will offer role-based whitelisting.  

= Is it possible to “reverse” the plugin, so it blocks only certain pages? =  
GoldenTicket is designed to **block all except selected pages**. If you need a plugin that hides only specific pages, you may want a different access-control tool. GoldenTicket’s specialty is making only your chosen page(s) public.

= Does this work with custom post types or WooCommerce products? =  
By default, GoldenTicket checks `is_page()`. You can extend it with a filter:  
add_filter( 'the_golden_tickets_allowed_posts', function( $ids ) {
// $ids is an array of page IDs already whitelisted
// To whitelist a custom post type item with ID 123:
// return array_merge( $ids, [123] );
return $ids;
});

pgsql
Copy
Edit
For more advanced integration, see **Golden Ticket Pro**.

= What happens when I deactivate the plugin? =  
All pages return to their normal visibility. If someone visits a page that previously required login, it no longer redirects—they’ll see the content as before.  

= How do I get support or report bugs? =  
Visit our GitHub repository: https://github.com/boxedgorilla/golden-ticket. Submit issues or feature requests there.  

== Screenshots ==

1. **Settings Page**  
   ![Settings Interface](assets/screenshot-1.png)  
   *Choose “Grant” or “Revoke” and pick which pages bypass login.*

2. **Live Preview**  
   ![Live Preview](assets/screenshot-2.png)  
   *Watch the whitelisted pages update instantly as you select/deselect.*

3. **Front‐End Behavior**  
   ![Front-End Redirect](assets/screenshot-3.png)  
   *Visitors to non-ticketed pages see the login page; ticketed pages load normally.*

== Changelog ==

= 1.0.0 =
* Initial release: block all pages except those granted a Golden Ticket.
* Simple GUI for page‐level exceptions.
* Live preview of whitelisted pages.

== Upgrade Notice ==

= 1.0.0 =
First public release. No prior versions to upgrade from.

== Credits ==

* Developed by Boxed Gorilla LLC (https://boxedgorilla.com).  
* Inspired by the need for fast, code-free page whitelisting on dev/staging sites by opening access to individual pages as needed.

== License ==  
This plugin is licensed under the GNU General Public License v2.0 or later.
