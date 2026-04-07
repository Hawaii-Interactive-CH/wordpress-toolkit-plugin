# Security Regression Checklist

Use this checklist after security-related changes.

## Admin Post Authorization and CSRF

1. Login as an admin and submit each API auth action from `admin.php?page=api-authentication`:
   - Generate encryption key
   - Generate master token
   - Set transient expiry
   - Add whitelist entry
   - Remove whitelist entry
2. Confirm each action succeeds with a valid nonce.
3. Replay each request with an invalid/removed nonce and confirm the request is rejected.
4. Login as a non-admin user and POST directly to `admin-post.php` for each action.
5. Confirm each request is rejected by capability checks.

## Cookie Consent Script Injection

1. In cookie settings, save:
   - one external script tag with `src` from an allowed host,
   - one inline script (should be ignored),
   - one external script tag from a disallowed host (should be ignored).
2. Accept cookie consent on the frontend.
3. Confirm only the allowed external script is injected into `document.head`.
4. Confirm no inline script executes.
5. Confirm disallowed host scripts are not loaded.

## Public Events API Data Exposure

1. Request:
   - `/wp-json/toolkit/v1/events`
   - `/wp-json/toolkit/v1/events/upcoming`
   - `/wp-json/toolkit/v1/events/{id}`
2. Confirm responses do not expose internal sync metadata:
   - `_google_event_id`
   - `_google_calendar_link`
   - `_last_synced`
3. Confirm public event fields are returned as expected.

## Docs Index Regeneration Flow

1. Open toolkit docs page and verify no file write occurs on GET.
2. Click `Regenerate index` and confirm nonce/capability-protected POST flow succeeds.
3. Repeat with an invalid nonce and confirm request rejection.
4. Repeat as non-admin and confirm capability rejection.
