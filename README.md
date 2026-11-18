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

Each project can show: a **main photo** (choose it with the star in the Photos box; it is used in the Projects list and at the top of the project), before/after photos (uploaded from the media library, no links needed), year, built-up area, budget range, duration, architect/engineer, location, a photo gallery, before/after photos, a video and **related services** (tick them in the right-hand box). Visitors see these as a tidy fact list, the service page lists its related projects, "Get a free quote" on a project preselects the matching service and tells you which project the enquiry came from, and each project carries local-SEO structured data (place, year, company).

Project types are Residential, Renovation, Valuation, Interior and Architecture. The sample projects cover residential buildings, renovations and land/building valuation cases; each shows a coloured placeholder until a photo is added.

### Search engine optimisation (works with The SEO Framework)

Install **The SEO Framework** (free). The plugin detects it and, the next time an admin page loads, sets it up once (blanks only; it never overwrites something you wrote): site title "Kriswa Construction", a keyword-first home title and a 120-160 character home description, the organization name and logo, a default 1200x630 share picture, the theme colour and the browser-tab icon. Check progress under **Site Details > SEO** (a live checklist: indexing allowed, readable links, HTTPS, icon, titles, share picture, alt text, sample content, sitemap).

What the site feeds into TSF through its public filters (so nothing is duplicated):
- **Titles and descriptions** for the template-built pages (their content is empty, so TSF could not generate any), the Projects archive and every project (type, location, year, area, builder, service). Titles stay short so "title - site name" fits in about 60 characters. Anything you type in TSF's own SEO box for a page always wins.
- **One business entity in the schema**: TSF's Organization becomes a `GeneralContractor` with legal name, tagline, area served (Nepal), founding year and your social profiles. Phone, email and address are added **only when they are real** (placeholders are never published). Each project adds a `CreativeWork` with place and year; the FAQ page keeps its `FAQPage` data. Breadcrumb data comes from TSF; visible breadcrumbs appear in every page banner.
- **Sample content is `noindex`** and so left out of the sitemap until you edit and save it. The default WordPress "Hello world!" post and "Sample Page" are removed.
- Project photos get alt text automatically (project name, location, photo number) when empty; service links have descriptive link text for screen readers and search engines; footer headings are no longer page headings.

After launch: submit `/sitemap.xml` in Google Search Console, create a Google Business Profile with the same name, address and phone as the website, and replace every placeholder in Site Details.

### Trust, credentials and team

A **Credentials and Team** page (also linked from the footer and the home-page trust band) gathers: company registration, PAN/VAT, Engineering Council registration, contractor class, any extra licences or certificates (with optional links to the certificate), years of experience, projects completed, technical expertise tags, the team (**Team** menu: photo, role, qualification, NEC number) and client testimonials. Edit numbers and registrations under **Site Details**.

**Sample content never has to go live by accident.** Everything created as a sample (projects, testimonials, team, blog posts) is flagged. The dashboard and Site Details show how many are still visible. Editing and saving a sample in the admin makes it yours; **Site Details > Extras > Hide sample content** hides the rest from visitors, and **Setup & Help > Delete all sample content** removes them. Sections with nothing to show (for example testimonials) disappear from the pages instead of showing an empty heading.

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

How it behaves (**Site Details > Instagram and TikTok** shows all of this live):
- **Visitors never trigger Instagram or TikTok requests.** Pages only read a cache. One background job refreshes hourly; if something is stale a single de-duplicated background refresh is scheduled. After a failure the site waits (10 minutes, 30 minutes for Meta rate limits, 1 hour for a rejected token) before asking again.
- **Failures are graceful.** The last good posts keep showing; only admins see a note, and the dashboard shows a warning. If the token is rejected you also get an email (at most every 3 days).
- **Token:** Instagram long-lived tokens last 60 days; a refresh needs a token that is at least 24 hours old and not yet expired, and gives another 60 days. The site refreshes weekly, records the exact expiry after the first refresh (until then it shows an estimate), and warns when fewer than 10 days remain. An expired token cannot be refreshed: paste a new one. **Refresh Instagram token now** and **Refresh feeds now** buttons are provided.
- **Pictures are copied to your own server** (`uploads/kriswa-social`), so they never expire and visitors do not contact Meta or TikTok just by scrolling. Videos play from Instagram's link and TikTok videos play in TikTok's own player.
- **TikTok:** links are validated when you save (https only, tiktok.com video links, tracking parameters removed, duplicates dropped; bad lines are rejected with an explanation). TikTok's player and scripts load only when a visitor taps a video.
- **Preview:** thumbnails of the latest posts, per-link OK/problem status, the last update time and the next automatic check.

Privacy: no request to Meta or TikTok is made from a visitor's browser until they tap a video.

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
