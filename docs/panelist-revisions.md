# Panelist revisions

This note records what was implemented on the `panelist-revisions` branch. It does not replace the Final Paper or SRS. Those documents are not in this repository.

RAMsey still answers known clinic questions with local rules. A server-side Gemini call is available only for a message those rules do not already answer, and only after a privacy filter. No Gemini API key is configured here, so a live Gemini reply was not tested. QR patient identification was not added. The Final Paper, SRS, system architecture, DFD, and separate privacy packet are not in this repository, so they were not edited.

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

Low stock no longer uses one threshold of 10 for every item. A saved custom threshold wins. Otherwise Medicine uses 20 percent of its baseline, Medical Supply uses 25 percent, Equipment uses 1 unit, and a First Aid Kit uses 2 kits. These are provisional settings, not clinically approved standards. `clinic:initialize-baselines` stores a baseline once: an existing target is kept, otherwise the first opening receipt is used, otherwise the stock recorded before the earliest inventory movement is used, otherwise the current positive quantity is stored as provisional. The baseline is not recalculated when stock is dispensed. An item at zero with no baseline stays out of stock and is flagged for review. No quantity is invented. An incomplete first aid kit is not counted as a complete kit. Stock above the threshold is NORMAL, stock at or below it is LOW STOCK, and zero is OUT OF STOCK.

## Low-stock mail and notifications

When stock first reaches its calculated threshold, the app creates an in-app alert and queues `LowStockAlertMail`. A second dispense while the same shortage is still open does not send another alert. `clinic:stock-reminders` sends one reminder only after 24 hours have passed since the last notice. Reaching zero opens an out-of-stock alert. Stock above the threshold resolves the open alert. Marking a notification read does not restock the item or close the shortage.

The recipient is `CLINIC_ALERT_EMAIL` only. The local setting is anamaet@apc.edu.ph. The message is addressed to Ms. Torre and contains the item, category, quantity, automatic threshold, status, and timestamp. It does not contain patient information. If the recipient is empty, only the in-app alert is created. The mail job is queued after the stock transaction commits. A mail exception is logged and does not roll back the stock change. The queued mail tries up to 3 times. Reminders are eligible 24 hours after the last notice, and the scheduler runs every 15 minutes with an overlap lock. A daily midnight job is not the only timer.

Live Gmail delivery was not tested. `.env.example` documents `smtp.gmail.com` port 587 with `MAIL_SCHEME=smtp` for STARTTLS. The running app still uses `MAIL_MAILER=log` because `MAIL_USERNAME` and `MAIL_PASSWORD` are empty. Do not switch the mailer to `sendmail`. Do not send a message to the clinic recipient until the sender account and the recipient have both been authorized.

## RAMsey

Typed messages and the suggested-question buttons go through the same `/ramsey/ask` path. The browser posts to that Laravel route only. It does not receive a Gemini API key.

RAMsey is a hybrid assistant. `RamseyDialogue` still answers greetings, Filipino and English appointment directions, the 30-minute "Saan ko makikita yun?" follow-up, thanks, symptoms such as "Masakit ulo ko.", record-page directions, and the existing operational shortcuts. "Hi" stays in English. "Kumusta", "kamusta", and "kumusta ka" are answered in Filipino. Those replies are not sent to Google Gemini. A student's own visit summary and prescription list still come from the Laravel database and are shown only to that signed-in student. Staff queue, inventory, duty, and report shortcuts stay on that same local path.

If the local rules do not recognize a message, `RamseyPrivacy` checks it before any external call. A suspected identifier, diagnosis, dosage, or other health detail is answered locally and is not forwarded. The same is true for a medication recommendation, a prompt-injection attempt, a role spoof, or a request to change records. When `GEMINI_API_KEY` is empty, an unrecognized message still gets "Sorry, hindi ko naintindihan ang message mo. Maaari mo bang linawin?" When the key is set, only the screened message and a fixed navigation prompt are sent. The request has no database tools. The key is an HTTP header, not part of the page. A failed, blocked, or unsafe model reply falls back to a local sentence. Requests are limited per signed-in user.

Gemini is not given medical records, diagnoses, prescriptions, or clinical decisions. It is not authorized to read or disclose them. RAMsey does not provide medical consultations, diagnoses, prescriptions, or clinical decision-making. Provider retention, cross-border transfer, and Philippine Data Privacy Act approval were not reviewed in this pass. Do not turn the key on for live clinic chats until that approval exists.

## User accounts

The Edit User Account form shows Medical Staff Role and License No. only when the selected type is Medical Staff, or when an Administrator account already has a separate clinical classification. Student and Employee selections hide those fields immediately and do not submit them. The server rejects a forged role or license on a Student or Employee account. Changing Medical Staff to Student or Employee removes the clinical credential, writes an audit row, and leaves the account id and medical records in place. Only Physician, Nurse, and Dentist can be assigned, and the license must be 4 to 30 letters or numbers. A guest cannot create a Medical Staff account through the public user endpoint.

## Deployment

The additive migrations `2026_10_11_120000_add_panelist_security_tables`, `2026_10_12_090000_add_inventory_threshold_settings`, and `2026_10_12_150000_add_inventory_baseline_status` were applied to the local `ram_cims` database. They do not delete accounts, patients, records, appointments, or inventory. `clinic:initialize-baselines` can be run again; it does not change stock quantities or existing baselines.

The reminder command is scheduled every 15 minutes:

`*/15 * * * * php artisan clinic:stock-reminders`

Production needs a cron entry that runs `php artisan schedule:run` every minute, and a queue worker that stays running:

`php artisan queue:work --tries=3`

The local queue connection is `database`. A failed job remains in `failed_jobs` and can be retried with `php artisan queue:retry`. This pass did not deploy the app or change production hosting. HTTPS and the Microsoft redirect URI still have to match the deployed host.

## Accounts, appointments, inventory, and records

The Accounts edit form uses a wider dialog so Email, Student/Employee Number, and Contact sit on one row on a large screen and stack on a narrow screen. Accounts, Inventory, and Medical Records have a server-side search. Inventory search can be combined with the category filter and does not change quantities.

Appointment booking dates come from the physician's open duty slots for the current week and the following weeks. The free-standing duty date field was removed from that form. The appointment list defaults to the current Manila week and later, ordered by appointment time. Earlier visits stay in the database and open from Previous weeks. Treatment-first queue ordering was not changed. Student booking uses the clinic clock in Asia/Manila, the same clock as the week filter.

An authorized nurse in the staff view, or a listed clinic developer, can mark an open future duty hour unavailable and mark it available again. A booked or past hour cannot be reopened, and the appointment itself is not changed. The change is written to `audit_logs`.

Inventory batches are stored in `inventory_batches`. Existing quantity and expiration values were copied. Batch numbers were not invented, and those rows are flagged for review. The edit form has a Batch number field beside Expiration Date so staff can enter the number from the receipt. Expired medicine batches are not dispensed. A dispense uses the earliest valid expiration first. Changing the quantity of an item that has one batch updates that batch. Low stock still uses the dispensable quantity.

The Final Paper and SRS are not in this repository, so they were not edited.

## Tests

`php artisan test` was run after the baseline, notification, and mail-template changes: 38 passed, 566 assertions. The mailer is still `log`, so Gmail delivery was not verified. A completed Microsoft sign-in was not run.

After the RAMsey Gemini filter was added, `php artisan test` passed 47 tests and 643 assertions. Gemini was mocked. No live call was made because `GEMINI_API_KEY` is empty. The automated tests cover English, Filipino, and Taglish navigation questions, medication and record refusals, identifier filtering, prompt injection, role spoofing, unauthorized actions, a failed API response, a blocked model response, an unsafe dosage in the model text, rate limiting, and the absence of the API key from the page.

After these appointment, slot, and account checks, `php artisan test` passed 54 tests and 727 assertions. Coverage includes appointment time order, clinic-time slot filtering, nurse and listed-developer slot changes, and a medical-staff update that reports a missing role before it checks the license format. A signed-in browser pass of those screens was not completed.
