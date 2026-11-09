# Kriswa Consultant and Construction Pvt. Ltd. – WordPress website

A fast, modern, bilingual (English / नेपाली) website for a Kathmandu construction company.
Everything here is **free and open source**; no paid plugins or services are needed.

```
wp-content/
  themes/kriswa/          Block theme (design, templates, patterns, fonts, logo)
  plugins/kriswa-core/    Companion plugin (projects, services, quote form, leads, settings, BS dates, setup)
```

## What is included

- Home, About, Services, Projects (filterable portfolio), Blog, FAQ, Social, Contact, Get a Quote pages
- Services: residential construction, land and building valuation, renovation, interior, architecture and consultancy
- Custom content types: Projects (type, location, status, before/after slider, video), Services, Testimonials, FAQs
- **Request-a-quote form** (3 steps, works without JavaScript, spam-protected) – leads are saved in WordPress (**Leads** menu, CSV export) and emailed to you
- Floating WhatsApp and Call buttons, WhatsApp follow-up after a quote request
- **Instagram and TikTok feed**: photos and videos appear on the home page and on a Social page; tap a post to watch or view it in an Apple-style viewer without leaving the site
- Trust badges (company registration, PAN/VAT, Engineering Council, contractor class)
- Google Maps/OpenStreetMap embed (no API key), offices list, social links
- Newsletter sign-up (stored locally, or point it at a free Mailchimp/Brevo form)
- SEO basics: LocalBusiness + FAQ structured data, meta description fallback, optional Google Analytics 4 and Search Console
- English + Nepali using the free **Polylang** plugin, Bikram Sambat (BS) dates and Devanagari numerals on the Nepali site
- Performance: system fonts (SF Pro on Apple devices, Inter bundled as fallback), no jQuery, tiny CSS/JS, lazy images, emoji script removed

## Install

1. Copy `wp-content/themes/kriswa` and `wp-content/plugins/kriswa-core` into your WordPress site (WordPress 6.5+, PHP 7.4+; tested on WordPress 7.1, PHP 8.3).
2. Activate the **Kriswa** theme and the **Kriswa Core** plugin.
3. That is all the setup there is: **activating the plugin automatically creates every page the menu links to** (Home, About, Services, Get a Free Quote, FAQ, Social, Contact, Blog), turns on pretty links and the Kathmandu timezone, and adds clearly marked *sample* services, projects, testimonials, FAQs and posts. No link leads to "page not found". If a page is ever deleted, an admin notice offers a one-click fix (**Kriswa Site > Setup & Help**), links fall back to the home page in the meantime, and trashed/missing pages are restored automatically. (CLI: `wp kriswa setup`.)
4. Open **Kriswa Site > Site Details** (top of the left menu, also reachable from the admin bar on every page) and replace the placeholders.
5. Replace the sample content with your real content and photos.

### Everyday editing for the site owner

| I want to change... | Go to |
|---|---|
| Projects completed, happy clients, years, staff numbers | **Kriswa Site > Site Details > Numbers** |
| Phone, WhatsApp, email, address, office hours | **Site Details > Phone, email, address** |
| Facebook / Instagram / TikTok / YouTube links | **Site Details > Social media links** |
| Offices and map | **Site Details > Offices and map** |
| Registration, PAN/VAT, licence | **Site Details > Company and registration** |
| Add a project | **Projects > Add New** (4 simple steps shown at the top; add a cover photo, a type, details and any number of gallery photos) |
| Testimonials, FAQs, services | the menus of the same name |
| Quote requests | **Leads** (with CSV export); new ones are counted on the Dashboard |

Project types are Residential, Renovation, Valuation, Interior and Architecture. The sample projects cover residential buildings, renovations and land/building valuation cases; each shows a coloured placeholder until a photo is added.

### Instagram and TikTok

All free. Settings live under **Settings > Kriswa > Instagram and TikTok feed**.

**Instagram (photos and playable videos, updates automatically)**
1. Make sure the Instagram account is a free *Professional* account (Business or Creator): Instagram app > Settings > Account type.
2. At developers.facebook.com create an app, add the **Instagram** product and choose *API setup with Instagram login*. Add your Instagram account and press **Generate token** (Meta occasionally renames these buttons).
3. Paste the token into **Instagram access token** and save. The site loads your latest posts, caches them for an hour and refreshes the token automatically every week so it never expires.
4. Set your profile link under *Social media* so the "Follow @name" link and handle appear.

**TikTok (videos)**
1. Open a video on TikTok, copy its link, and paste one link per line into **TikTok video links**. Save.
2. TikTok only lists a whole profile automatically through an app that TikTok must approve, so this site uses the free link-based method. Add new videos by pasting their links.

The panel shows a tab bar when both are connected, falls back to a tidy "Follow us" card when neither is, and shows a note (to admins only) if Instagram reports a problem. **Settings > Kriswa > Refresh feeds now** forces an update.

Privacy: Instagram images load from Meta's servers. TikTok loads nothing from TikTok until a visitor taps a video.

### Design

The look follows Apple's Human Interface principles: clarity (large type, one accent colour, generous space), deference (content first, quiet footer, translucent slim navigation), depth (soft layered surfaces and gentle motion), 44 px touch targets, visible keyboard focus, automatic Light and Dark appearance, and reduced-motion support. Colours and spacing are tokens at the top of `assets/css/theme.css` and in `theme.json`.

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
