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

Low stock no longer uses one threshold of 10 for every item. A saved per-item threshold wins. Otherwise Medicine uses 20 percent of target stock, Medical Supply uses 25 percent, Equipment uses 1 unit, and a First Aid Kit uses 2 kits. These are initial settings, not clinically approved standards. If a percentage category has no target stock, the status is CONFIGURATION REQUIRED and no target is invented. An incomplete first aid kit is not counted as a complete kit. Stock above the threshold is NORMAL, stock at or below it is LOW STOCK, and zero is OUT OF STOCK.

## Low-stock mail and notifications

When stock first reaches its calculated threshold, the app creates an in-app alert and queues `LowStockAlertMail`. A second dispense while the same shortage is still open does not send another alert. `clinic:stock-reminders` sends one reminder only after 24 hours have passed since the last notice. Reaching zero opens an out-of-stock alert. Stock above the threshold resolves the open alert. Marking a notification read does not restock the item or close the shortage.

The recipient is `CLINIC_ALERT_EMAIL`. If it is empty, only the in-app alert is created. A mail exception is logged and does not roll back the stock change. The queued mail tries up to 3 times. Reminders are eligible 24 hours after the last notice, and the scheduler runs every 15 minutes with an overlap lock. A daily midnight job is not the only timer.

Live Gmail delivery was not tested. `.env.example` documents `smtp.gmail.com` port 587. The running app still uses `MAIL_MAILER=log` until a Gmail app password is supplied. Do not switch the mailer to `sendmail`.

## RAMsey

Typed messages and the suggested-question buttons go through the same `/ramsey/ask` path. Conversational replies come from `RamseyDialogue`. Operational shortcuts, such as the duty check and a student's own prescription list, still use the existing clinic replies.

Unknown text such as "adajda" gets "Sorry, hindi ko naintindihan ang message mo. Maaari mo bang linawin?" A help request in Filipino asks what help is needed. Appointment directions stay in Filipino when the question is in Filipino, and "Saan ko makikita yun?" uses that answer only while the appointment topic is still active. The topic expires after 30 minutes and is cleared when the message is not understood. "Masakit ulo ko." is answered with a request to consult clinic personnel. RAMsey does not call a generative AI service.

## User accounts

The Edit User Account form shows Medical Staff Role and License No. only when the selected type is Medical Staff, or when an Administrator account already has a separate clinical classification. Student and Employee selections hide those fields immediately and do not submit them. The server rejects a forged role or license on a Student or Employee account. Changing Medical Staff to Student or Employee removes the clinical credential, writes an audit row, and leaves the account id and medical records in place. Only Physician, Nurse, and Dentist can be assigned, and the license must be 4 to 30 letters or numbers. A guest cannot create a Medical Staff account through the public user endpoint.

## Deployment

The additive migrations `2026_10_11_120000_add_panelist_security_tables` and `2026_10_12_090000_add_inventory_threshold_settings` were applied to the local `ram_cims` database. They do not delete accounts, patients, records, appointments, or inventory.

The reminder command is scheduled every 15 minutes:

`*/15 * * * * php artisan clinic:stock-reminders`

Production still needs a cron entry for `php artisan schedule:run`, a queue worker because low-stock mail implements `ShouldQueue`, HTTPS, and the Microsoft redirect URI that matches the deployed host. This pass did not deploy the app or change production hosting.

## Tests

`php artisan test` was run after the chatbot, threshold, and account-form changes: 37 passed, 513 assertions. The mailer is still `log`, so Gmail delivery was not verified. A completed Microsoft sign-in was not run.
