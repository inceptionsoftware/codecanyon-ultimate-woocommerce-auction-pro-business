# UWA License Server — Setup Guide

This guide walks a developer through deploying the `uwa-license-server` WordPress plugin on a dedicated subdomain so that the Ultimate WooCommerce Auction Pro client plugin can activate, deactivate, and validate licenses.

---

## Requirements

- PHP 7.4 or higher (PHP 8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- WordPress 5.8 or higher
- SSL certificate — HTTPS is **required** (license API calls must be encrypted)
- Envato Personal Token (optional but strongly recommended for automated purchase verification)

---

## Step 1: Subdomain Setup

1. Log in to your domain registrar or hosting control panel (cPanel, Plesk, etc.).
2. Create a new subdomain: `codecanyon.auctionplugin.net` (or your preferred subdomain).
3. Point the subdomain's DNS A-record to the IP address of the server that will host the license server.
4. Provision and install an SSL certificate for the subdomain. Most hosts offer Let's Encrypt free certificates — enable HTTPS and force redirect HTTP → HTTPS.
5. Confirm the subdomain is reachable over HTTPS before proceeding.

---

## Step 2: WordPress Installation

1. Download the latest version of WordPress from https://wordpress.org/download/
2. Upload and extract it to the document root of the subdomain (e.g., `/var/www/codecanyon.auctionplugin.net/public_html/`).
3. Create a new MySQL database and database user with full privileges for this installation.
4. Run the WordPress installer by visiting the subdomain in a browser and completing the five-minute install wizard.
5. Choose a strong admin password. This WordPress install does not need any public-facing themes — it exists purely to host the REST API endpoints.
6. Recommended: set `define( 'DISALLOW_FILE_EDIT', true );` in `wp-config.php` and disable unnecessary default plugins.

---

## Step 3: Plugin Installation

1. Compress the `license-server/` directory in this repository into a ZIP file named `uwa-license-server.zip`.
2. In the WordPress admin of your subdomain, navigate to **Plugins → Add New → Upload Plugin**.
3. Upload `uwa-license-server.zip` and click **Install Now**.
4. Click **Activate Plugin**.
5. Upon activation, the plugin creates its database table automatically (see Step 4).

---

## Step 4: Database

The plugin creates the required table (`{prefix}uwa_licenses`) automatically on activation via the WordPress `register_activation_hook`. No manual SQL execution is needed for a fresh install.

If you need to inspect or recreate the schema manually, the reference SQL file is located at:

```
license-server/database/schema.sql
```

To run it manually (replace `wp_` with your actual table prefix):

```bash
mysql -u your_db_user -p your_db_name < schema.sql
```

---

## Step 5: Configuration

After activating the plugin, navigate to the license server settings page in the WordPress admin:

1. Go to **License Server → Settings** (menu added by the plugin).
2. Fill in the following fields:

   - **Envato Personal Token**: Your token from https://build.envato.com/my-apps/ — used to verify purchases against the Envato marketplace API. Leave blank if you will manage licenses manually.
   - **CodeCanyon Item ID**: The numeric ID of your plugin's product page on CodeCanyon (visible in the URL of your item's page, e.g., `https://codecanyon.net/item/..../ITEM_ID`).
   - **Secret Key**: A long, random string shared between this server and the client plugin. Generate a strong secret, for example using: `openssl rand -hex 32`

3. Save the settings.

---

## Step 6: Update the Plugin Client

The `class-uwa-license.php` file inside the client plugin contains a constant for the secret key and the server endpoint URL. After configuring the server:

1. Open `includes/class-uwa-license.php` in the client plugin.
2. Update the `UWA_LICENSE_SECRET` constant to match the secret key you set in Step 5.
3. Update the `UWA_LICENSE_SERVER_URL` constant (or equivalent) to point to your subdomain, e.g., `https://codecanyon.auctionplugin.net`.
4. Save the file and deploy the updated plugin to your customers.

**Important:** Never commit `class-uwa-license.php` with the real secret key to a public repository. Use an environment variable or a gitignored config file for production secrets.

---

## Step 7: Test the System

### 7a — Add a test license manually

1. In the license server WordPress admin, go to **License Server → Licenses → Add New**.
2. Enter a test purchase code (e.g., `TEST-0000-0000-0000`), set status to `Active`, and save.

### 7b — Test activation from a WordPress site

1. Install the client plugin on a test WordPress site.
2. Navigate to **Settings → Ultimate WooCommerce Auction Pro → License** tab.
3. Enter the test purchase code and click **Activate License**.
4. Confirm the license status changes to "Active" and the site domain is recorded on the license server.

### 7c — Verify via REST API directly

You can test the endpoints using `curl` or a tool like Postman.

**Activate:**

```bash
curl -X POST https://codecanyon.auctionplugin.net/wp-json/uls/v1/activate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"TEST-0000-0000-0000","domain":"https://yoursite.com","secret":"YOUR_SECRET_KEY"}'
```

Expected successful response:
```json
{ "success": true, "message": "License activated successfully." }
```

**Validate:**

```bash
curl -X POST https://codecanyon.auctionplugin.net/wp-json/uls/v1/validate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"TEST-0000-0000-0000","domain":"https://yoursite.com","secret":"YOUR_SECRET_KEY"}'
```

**Deactivate:**

```bash
curl -X POST https://codecanyon.auctionplugin.net/wp-json/uls/v1/deactivate \
  -H "Content-Type: application/json" \
  -d '{"license_key":"TEST-0000-0000-0000","domain":"https://yoursite.com","secret":"YOUR_SECRET_KEY"}'
```

---

## Step 8: Envato Personal Token

To enable automatic purchase verification (so the server can confirm a customer actually bought the plugin on CodeCanyon before issuing a license):

1. Visit https://build.envato.com/my-apps/
2. Click **Create New Token**.
3. Give the token a descriptive name (e.g., "UWA License Server").
4. Under **Permissions**, enable: **View and search Envato sites** (specifically the "View the user's items' sales history" and "Verify purchases of the user's items" scopes).
5. Click **Create Token** and copy the token value immediately — it is shown only once.
6. Paste the token into the **Envato Personal Token** field in the license server settings (Step 5).

Without this token, the server will still manage licenses but cannot automatically cross-check with Envato to verify that a license key corresponds to a real purchase.

---

## API Reference

All endpoints are registered under the `uls/v1` namespace via the WordPress REST API.

Base URL: `https://your-license-server-domain.com/wp-json/uls/v1/`

All requests must be `POST` with a `Content-Type: application/json` body. The `secret` field in every request must match the `UWA_LICENSE_SECRET` configured on the server.

---

### POST /wp-json/uls/v1/activate

Activates a license key for a given domain.

**Request body:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "https://customer-site.com",
  "secret": "your_shared_secret_key"
}
```

**Success response (200):**
```json
{
  "success": true,
  "message": "License activated successfully."
}
```

**Error responses:**
```json
{ "success": false, "message": "Invalid license key." }
{ "success": false, "message": "License already active on another domain." }
{ "success": false, "message": "Invalid secret key." }
```

---

### POST /wp-json/uls/v1/deactivate

Deactivates a license key, freeing it from the registered domain so it can be activated elsewhere.

**Request body:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "https://customer-site.com",
  "secret": "your_shared_secret_key"
}
```

**Success response (200):**
```json
{
  "success": true,
  "message": "License deactivated successfully."
}
```

**Error responses:**
```json
{ "success": false, "message": "License not found or not active on this domain." }
{ "success": false, "message": "Invalid secret key." }
```

---

### POST /wp-json/uls/v1/validate

Validates that a license key is currently active for the given domain. Called by the client plugin's daily WP-Cron job (`uwa_daily_license_check`).

**Request body:**
```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "domain": "https://customer-site.com",
  "secret": "your_shared_secret_key"
}
```

**Success response (200):**
```json
{
  "success": true,
  "message": "License is valid."
}
```

**Error responses:**
```json
{ "success": false, "message": "License not found." }
{ "success": false, "message": "License is inactive or expired." }
{ "success": false, "message": "Domain mismatch." }
{ "success": false, "message": "Invalid secret key." }
```

---

## Security Notes

- **Keep the secret key private.** It is the shared credential between client and server. Anyone who knows it can make arbitrary API calls to activate or deactivate licenses.
- **Never commit credentials to version control.** Do not commit any file containing the real `UWA_LICENSE_SECRET`, the Envato Personal Token, or database credentials to a public Git repository. Add `config.php`, `.env`, and similar files to `.gitignore`.
- **Enforce HTTPS.** All API communication must travel over TLS. Disable HTTP on the subdomain entirely (force redirect to HTTPS via server config or WordPress plugin).
- **Restrict wp-admin access.** Consider restricting `/wp-admin/` to known IP addresses using `.htaccess` or your server's firewall rules, since the license server has no public visitors.
- **Monitor logs regularly.** Check your server's access logs and WordPress debug log (`wp-content/debug.log`) for repeated failed API calls, which may indicate a brute-force attempt against license keys.
- **Rotate the secret key periodically.** If you suspect the key has been compromised, update it on the server and redeploy the updated `class-uwa-license.php` to clients.
- **Rate-limit the API endpoints.** Consider adding rate limiting at the web server level (e.g., Nginx `limit_req` or an Apache mod) to prevent bulk license enumeration attempts.

---

## Troubleshooting

### "License key not found" when activating a valid key

- Confirm the license record exists in the `{prefix}uwa_licenses` table in the license server database.
- If Envato verification is enabled, ensure the Envato Personal Token is valid and has the correct scopes (Step 8).
- Check that the CodeCanyon Item ID in server settings matches the item ID of the plugin.

### REST API returns 404

- Ensure WordPress permalinks are set to anything other than "Plain" (go to **Settings → Permalinks** and click Save to flush rewrite rules).
- Confirm the `uwa-license-server` plugin is active.
- Test the base REST API is working: `curl https://your-domain.com/wp-json/`

### SSL/HTTPS errors from client plugin

- Verify the SSL certificate on the license server subdomain is valid and not self-signed.
- Check that the certificate chain is complete (some cheap certificates omit intermediate CA bundles).
- On the client site, if `CURLOPT_SSL_VERIFYPEER` failures occur in development, you may temporarily disable verification — but never in production.

### License validates locally but fails on customer site

- Ensure the `domain` value sent in the request exactly matches the registered domain (including or excluding `www`, trailing slash, HTTP vs. HTTPS).
- Check that the client plugin is sending the correct domain — it should use `home_url()` or `site_url()` rather than a hardcoded value.

### Daily cron validation stops working

- Confirm WP-Cron is running on the client site: install a cron management plugin (e.g., WP Crontrol) and check that `uwa_daily_license_check` appears in the scheduled events list.
- If the client site disables WP-Cron (e.g., `define( 'DISABLE_WP_CRON', true );` in `wp-config.php`), the system cron must be configured to hit `wp-cron.php` directly at regular intervals.

### "Invalid secret key" errors

- Double-check that `UWA_LICENSE_SECRET` in `includes/class-uwa-license.php` on the client exactly matches the secret key saved in the license server settings — no leading/trailing whitespace.
- If you recently rotated the secret key, ensure the updated client plugin has been deployed.

### Envato token returns authentication errors

- Personal Tokens expire or can be revoked. Regenerate the token at https://build.envato.com/my-apps/ and update the server settings.
- Confirm the token has the "Verify purchases of the user's items" permission enabled.
