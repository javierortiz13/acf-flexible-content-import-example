# ACF Pro: flexible-content template + bulk import

A small, self-contained example of how I structure an ACF Pro build for location/service pages that editors manage themselves and that can be created in bulk.

Files:

| File | What it shows |
| --- | --- |
| `acf-json/group_service_location.json` | Field group: location details, an hours repeater, and a flexible-content `sections` field with three layouts (hero, services grid, call to action). Loaded through ACF Local JSON so it lives in Git. |
| `single-service_location.php` | The page template: location header, then the flexible sections. |
| `template-parts/flexible-sections.php` | One loop over `sections`, one partial per layout, everything escaped. |
| `inc/class-service-location-import.php` | A WP-CLI command that creates or updates pages from a CSV, writes the ACF fields by field key (repeaters and flexible content included), and supports `--dry-run`. |
| `sample-import.csv` | Two example rows. |

## How the import works

```
wp service-location import sample-import.csv --dry-run
wp service-location import sample-import.csv
```

- Each row is matched by `slug`, so running it twice updates the same pages instead of duplicating them.
- Fields are written with their field keys (`field_…`), which is what ACF needs when the post has no saved values yet.
- `services` holds `Title|Text` pairs separated by `;;`, and becomes the services-grid layout.
- New pages are created as drafts, so nothing goes live until someone reviews it.

The same mapping works in WP All Import (ACF add-on) when the client prefers a UI. The script is useful when the content needs cleanup on the way in, or when the import has to be repeatable on staging and production.

## Notes

- Register the `service_location` post type in your theme or a plugin. It isn't included here.
- Local JSON: set `acf/settings/save_json` and `acf/settings/load_json` to the theme's `acf-json` folder, so field changes are versioned with the code.
