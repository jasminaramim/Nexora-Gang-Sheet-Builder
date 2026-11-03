=== Nexora Gang Sheet Builder ===
Contributors: nexorateam
Tags: gang sheet, dtf, dtf gang sheet, woocommerce dtf, print on demand, direct to film, heat transfer
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional Direct-to-Film (DTF) Gang Sheet Builder for WordPress & WooCommerce with real-time nesting, 300 DPI export, and dynamic pricing.

== Description ==

**Nexora Gang Sheet Builder** is a commercial-grade Direct-to-Film (DTF) Gang Sheet creation and ordering suite built specifically for WordPress and WooCommerce store owners, print shops, and heat transfer manufacturers.

Inspired by industry standard gang sheet applications, Nexora delivers a modern dark-mode canvas experience that empowers your customers to build, optimize, and order custom gang sheets with 100% true physical print accuracy.

### Key Features:

* **Interactive Fabric.js Canvas:** Real-time drag and drop, rotate, scale, aspect ratio preservation, lock/unlock, and duplicate tools.
* **Auto-Nesting Engine:** Intelligent 2D packing algorithm arranges multiple artwork items to maximize sheet utilization and minimize film waste.
* **Real-Time DPI Resolution Checker:** Computes actual printed DPI ($pixels / inches$) with live visual badges (Optimal 300+ DPI, Good, Medium, Low Quality warning).
* **300 DPI Print-Ready Export:** Generates commercial-grade transparent 300 DPI PNG and vector-accurate PDF files directly.
* **Deep WooCommerce Integration:**
  - One-click "Build Your DTF Gang Sheet" button on single product pages.
  - Dynamically calculates price based on sheet dimensions or square footage.
  - Generates custom gang sheet thumbnail previews in Cart, Checkout, and Order details.
  - Saves 300 DPI print files in WordPress media and attaches them to WooCommerce orders for admin fulfillment.
* **Multiple Sheet Support:** Customers can build multiple sheets in a single session.
* **Cloud Artwork Import:** Direct import from Google Drive, Dropbox, and cloud URLs.
* **Shortcode Support:** Works seamlessly with Elementor, Gutenberg, and classic editors: `[nexora_gang_sheet_builder]` and `[nexora_gang_sheet_button]`.
* **Complete Admin Suite:** 12-tab control center to manage presets, pricing rules, upload limits, and orders without writing a single line of code.

== Installation ==

1. Upload the `nexora-gang-sheet-builder.zip` package via **Plugins > Add New > Upload Plugin** in your WordPress dashboard.
2. Click **Activate Plugin**.
3. Go to **Nexora Gang Sheet** in your admin menu to configure your default sheet sizes, pricing rules, and WooCommerce settings.
4. Add the shortcode `[nexora_gang_sheet_builder]` to any page, or enable the builder button on your WooCommerce product pages.

== Shortcodes ==

* `[nexora_gang_sheet_builder]` - Renders the complete interactive gang sheet builder.
  - Optional parameters: `product_id="123"`, `width="22"`, `height="60"`, `theme="dark"`
* `[nexora_gang_sheet_button]` - Displays a customizable button that opens the builder.
  - Optional parameters: `product_id="123"`, `text="Build Your Gang Sheet"`, `class=""`

== Frequently Asked Questions ==

= Does this plugin work without WooCommerce? =
Yes! In standalone mode, customers can build, auto-nest, and export high-resolution 300 DPI PNG and PDF files directly from any page.

= Does it support true transparent backgrounds? =
Yes. Alpha channel transparency is 100% preserved so white ink and color layers are processed correctly by DTF RIP software (CADlink, AcroRIP, DTF RIP).

= Can I set custom sheet sizes? =
Yes. Administrators can create unlimited sheet presets (e.g. 22"x24", 22"x36", 22"x60", 22"x120", 22"x240") and allow customers to enter custom dimensions.

== Changelog ==

= 1.0.0 =
* Initial commercial release.
* Real-time 2D nesting algorithm.
* Live DPI resolution checker.
* WooCommerce cart, checkout, and order line item integration.
* High-resolution 300 DPI PNG and PDF export.

