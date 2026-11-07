# Kriswa Consultant and Construction Pvt. Ltd. – WordPress website

A fast, modern, bilingual (English / नेपाली) website for a Kathmandu construction company.
Everything here is **free and open source**; no paid plugins or services are needed.

```
wp-content/
  themes/kriswa/          Block theme (design, templates, patterns, fonts, logo)
  plugins/kriswa-core/    Companion plugin (projects, services, quote form, leads, settings, BS dates, setup)
```

## What is included

- Home, About, Services, Projects (filterable portfolio), Blog, FAQ, Contact, Get a Quote pages
- Custom content types: Projects (type, location, status, before/after slider, video), Services, Testimonials, FAQs
- **Request-a-quote form** (3 steps, works without JavaScript, spam-protected) – leads are saved in WordPress (**Leads** menu, CSV export) and emailed to you
- Floating WhatsApp and Call buttons, WhatsApp follow-up after a quote request
- Trust badges (company registration, PAN/VAT, Engineering Council, contractor class)
- Google Maps/OpenStreetMap embed (no API key), offices list, social links
- Newsletter sign-up (stored locally, or point it at a free Mailchimp/Brevo form)
- SEO basics: LocalBusiness + FAQ structured data, meta description fallback, optional Google Analytics 4 and Search Console
- English + Nepali using the free **Polylang** plugin, Bikram Sambat (BS) dates and Devanagari numerals on the Nepali site
- Performance: self-hosted fonts, no jQuery, tiny CSS/JS, lazy images, emoji script removed

## Install

1. Copy `wp-content/themes/kriswa` and `wp-content/plugins/kriswa-core` into your WordPress site (WordPress 6.5+, PHP 7.4+; tested on WordPress 7.1, PHP 8.3).
2. Activate the **Kriswa** theme and the **Kriswa Core** plugin.
3. Go to **Tools > Kriswa Setup** and click **Run setup**. This creates the pages, sets the static front page, pretty links and the Kathmandu timezone, and adds clearly marked *sample* services, projects, testimonials, FAQs and posts. (CLI: `wp kriswa setup`.)
4. Go to **Settings > Kriswa** and replace every placeholder: phone, WhatsApp number, email, address, offices, registration numbers, social links, map embed URL, homepage statistics.
5. Replace the sample content with your real content and photos (set a featured image on every project).

### Nepali (नेपाली)

1. Install and activate the free **Polylang** plugin. Add *English* and *नेपाली* (ne_NP) under **Languages**, and set the language switcher via the `[kriswa_lang_switcher]` already in the header.
2. Run **Tools > Kriswa Setup** again. It creates linked Nepali versions of the pages and sample content.
3. Theme and form text is translated through `languages/ne_NP.l10n.php` (theme) and `languages/kriswa-core-ne_NP.l10n.php` (plugin). **Please have a native Nepali speaker review the wording.**

### Recommended free plugins

Polylang (languages), The SEO Framework or Rank Math (sitemaps, titles), a caching plugin such as WP Super Cache or LiteSpeed Cache, UpdraftPlus (backups), WP Mail SMTP with a free SMTP account so quote-request emails always arrive.

## Brand

Colours and fonts are set in `theme.json` (brand blue `#1747b8`, navy `#0b1b3f`, accent red from the logo `#e11d2a`). The logo is in `wp-content/themes/kriswa/assets/images/` (`logo.svg`, `logo.png`).

## Notes

- The BS calendar table covers BS 2000–2090 (AD 1943–2034).
- Google Analytics, if enabled, loads for visitors without a consent banner; add a cookie-consent plugin if your legal advice requires it.
- Sample testimonials and demo text are placeholders. Do not publish them as real reviews.
