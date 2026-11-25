# WAWTech Demo

An in-house CMS for [WAWTech 2026](https://wawtech.io) (Warsaw, 26–27 November 2026), built with [Laravel Nova](https://nova.laravel.com). It manages the conference's speakers, agenda, partners and FAQ, and is the demo project for the talk *Building an In-House CMS on Laravel Nova*.

The content is a snapshot of wawtech.io taken on 24 November 2026: 117 speakers, 103 sessions, 54 partners and 29 FAQs.

**Stack:** Laravel 13, PHP 8.4, Laravel Nova 5, SQLite, Pest.

## Getting started

You need PHP 8.4, Composer, Node.js and a Laravel Nova licence. Give Composer your Nova credentials first; they are stored in `auth.json`, which is git-ignored:

```bash
composer config http-basic.nova.laravel.com your-email@example.com your-license-key
```

Then install, seed and run:

```bash
composer setup
php artisan db:seed
php artisan storage:link
php artisan serve --port=8001
```

`composer setup` installs dependencies, publishes Nova's assets, creates `.env`, generates the app key, migrates and builds the frontend.

Open <http://localhost:8001/nova> and sign in with the seeded account `test@example.com` / `password`. A second account, `editor@example.com`, is there to try impersonation.

### Optional settings (`.env`)

| Setting | What it does |
|---|---|
| `NOVA_LICENSE_KEY` | Removes Nova's "Unregistered" badge. |
| `NOVA_ADMIN_EMAILS` | Comma-separated emails allowed into Nova outside your laptop. Locally everyone is let in. |
| `NOVA_DEMO_EMAIL`, `NOVA_DEMO_PASSWORD` | Pre-fills the login form, so a demo needs no typing. Locally this works with any account; elsewhere only with a read-only one, because the password is visible in the page source. |

### Public demo account

To let anyone browse the live admin without being able to change anything:

1. Set `NOVA_DEMO_EMAIL` and `NOVA_DEMO_PASSWORD` on the server to a new address and a password you use nowhere else.
2. Run `php artisan demo:account`. It creates (or updates) that account as **read-only** and refuses to touch an existing regular account.
3. The login page is now pre-filled with it.

Read-only accounts see the admin as an editor does: every content page, dashboard, form, button and action. Nothing they submit is saved. Creating, editing, deleting, reordering, attaching or running an action shows "This is a read-only demo account, so your changes were not saved." That is enforced on the server by `App\Http\Middleware\RejectChangesFromReadOnlyAccounts`, which turns away every request from a read-only account that would change data.

The shared password can't be changed for the same reason. One page stays closed to read-only accounts: the user list (`App\Policies\UserPolicy`), so admin emails aren't shown, which also means they can't impersonate anyone. Admins can switch any user to read-only in Users → edit. Locally, `visitor@example.com` / `password` is a read-only account to try it with.

## What's inside

Each page shows off a different group of Nova features.

| Page | Highlights |
|---|---|
| Everywhere | Custom menu with live count badges, breadcrumbs, notifications, user-menu link, custom footer, brand logo and colours, impersonation (Users → Content Editor). |
| **Main** dashboard | Headline numbers, featured speakers with photos, sessions per hour with a day picker, and charts for stages, formats and partner tiers, all in the real stage colours. |
| **Editorial** dashboard | How much of the agenda is announced, and a "Needs attention" list of placeholders and speakers without a session. |
| **Agenda Overview** / **Content Overview** | Hours of content, topics, session lengths, panel discussions with moderators, companies, featured speakers and "who's speaking". |
| Agenda Items | Stage and time column, format badges with icons, conference-day filter, "Move to stage" action, auto-filled end time, slides upload, tags. |
| Speakers | Profile/Sessions tabs, preview, homepage actions, CSV export, a "Busiest Speakers" lens, and a drag-and-drop photo grid for homepage order. |
| Partners | Tier badges, trash and restore, a contacts repeater. |
| FAQ | Preview on click and a drag-and-drop list for ordering. |
| Stages, Tags | Colour field, hover previews. |

### Custom pieces, without a build step

- **Theme:** `resources/css/nova-theme.css`, inlined on every Nova page, including the login page, by `resources/views/vendor/nova/partials/meta.blade.php`.
- **Custom cards:** plain-JavaScript Vue components in `resources/js/nova/`, loaded with `Nova::script()`. Nova ships Vue with its template compiler, so no bundler is needed.
  - `sortable-card.js`: drag-and-drop ordering, as a list or a photo grid (`App\Nova\Cards\SortableCard`).
  - `speaker-spotlight-card.js`: featured speakers on the Main dashboard (`App\Nova\Cards\SpeakerSpotlight`).
- **Reorder endpoint:** `POST /nova-vendor/{list}/reorder`, behind Nova's own authentication. Which models can be reordered is listed in `App\Http\Requests\ReorderRequest::LISTS`.

## Data

- **Location:** the seed data is in `database/seeders/data/`, as JSON files plus speaker photos and partner logos. `php artisan migrate:fresh --seed` rebuilds the database from it.
- **Times:** session times are stored in UTC and shown in Europe/Warsaw time.
- **Ownership:** names, photos and logos belong to WAWTech, its speakers and its partners, and are used here for demo purposes only.
- **Refreshing:** wawtech.io is behind Cloudflare, so the snapshot was collected with a real browser rather than a script in this repository.

## Tests

```bash
php artisan test --compact
vendor/bin/pint
```

## Not in the repository

Git ignores everything secret or generated:
- `.env` and its variants
- `auth.json`
- the SQLite database
- uploaded and seeded files in `storage/`
- `vendor/` and `node_modules/`
- local editor and AI settings
- **Nova's compiled assets in `public/vendor`:** Nova is licensed software, so they are restored from `vendor/` by `composer setup`, `composer update`, or `php artisan vendor:publish --tag=laravel-assets --force`.
