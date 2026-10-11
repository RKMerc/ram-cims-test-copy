# Panelist revisions

This note records what was implemented on the `panelist-revisions` branch. It does not replace the Final Paper or SRS. Those documents are not in this repository.

RAMsey remains a rule-based assistant. It does not call a generative AI API. QR patient identification was not added.

## Authentication

Email and password registration remains the working public sign-in path. New passwords must be at least 8 characters and include a letter and a number. Passwords are hashed by Laravel. Email addresses are stored in lowercase. Duplicate emails are rejected. A new registration does not grant clinic staff or administrator access from anything the user types. Staff access still comes from the existing clinic allowlist.

Registration sends a signed email confirmation link. Existing accounts are not locked out when `email_verified_at` is empty, because the clinic mailer is still `log` and older accounts were never confirmed.

The login page is one page for every role: Sign in, Create an account, Forgot password, and Continue with Microsoft.

## Microsoft sign-in

The Microsoft button still uses the authorization-code flow already configured for this app. A completed sign-in with an Asia Pacific College Microsoft account was not run in this pass, so live single sign-on stays unverified.

If Microsoft returns an identity that is already linked, that RAM-CIMS user is signed in. If the email matches an existing account and the person is not already signed in as that account, the app asks for the existing password before linking. A matching name and email does not merge accounts by itself. Two different accounts are not merged. The link stores the Microsoft subject identifier, tenant, and email on `linked_identities`. The original user id, patient profile, medical record, and appointments stay on the same records. The link is written to `audit_logs`.

Profile shows Connected Accounts. Connecting Microsoft from a signed-in profile is allowed when the Microsoft email matches that same account.

## Inventory

Dispensing subtracts the quantity entered, not a dosage schedule. A dispense of 8 from 100 leaves 92. Zero, negative, over-stock, and expired medicine dispenses are rejected. Each change is stored in `inventory_transactions` inside a database transaction. The log stores the item code, type, quantity change, previous quantity, new quantity, user, and time. It does not store a patient name.

## Low-stock mail and notifications

When stock first reaches the item threshold, or the default of 10, the app creates an in-app alert and queues `LowStockAlertMail`. The message uses the requested subject and wording. A second dispense while the same shortage is still open does not send another alert. `clinic:stock-reminders` sends one reminder after 24 hours and is scheduled daily. Reaching zero opens an out-of-stock alert. Stock above the threshold resolves the open alert. Marking a notification read does not restock the item or close the shortage.

The recipient is `CLINIC_ALERT_EMAIL`. If it is empty, only the in-app alert is created. A mail exception is logged and does not roll back the stock change. The queued mail tries up to 3 times.

Live Gmail delivery was not tested. `.env.example` documents `smtp.gmail.com` port 587. The running app still uses `MAIL_MAILER=log` until a Gmail app password is supplied. Do not switch the mailer to `sendmail`.

## RAMsey

Typed messages are matched with normalized keywords, phrases, and a short session topic. Supported replies cover greetings, appointment questions in English and Filipino, a follow-up such as "Saan ko makikita yun?", thanks, and a refusal to diagnose symptoms such as "Masakit ulo ko." Unsupported questions use the existing fallback. Suggested questions are still optional.

## Deployment

The additive migration `2026_10_11_120000_add_panelist_security_tables` was applied to the local `ram_cims` database. It does not delete accounts, patients, records, appointments, or inventory.

The reminder command is scheduled at midnight:

`0 0 * * * php artisan clinic:stock-reminders`

Production still needs a cron entry for `php artisan schedule:run`, a queue worker because low-stock mail implements `ShouldQueue`, HTTPS, and the Microsoft redirect URI that matches the deployed host. This pass did not deploy the app or change production hosting.

## Tests

`php artisan test` was run after these changes: 31 passed, 428 assertions. That includes the existing clinic portal tests and `tests/Feature/PanelistRevisionTest.php`.
