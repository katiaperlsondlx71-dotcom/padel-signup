# Email Deliverability Setup

## Why this matters

`playpadelwith.us` sends transactional email (registration confirmations,
waitlist promotions, cancellations, welcome emails, password resets) via
PHP's `mail()` through cPanel's local MTA. Modern mail receivers (Gmail,
Hotmail, Yahoo, Outlook.com) treat unauthenticated mail from shared
hosting as high-risk and silently drop or graylist it.

To get reliable delivery you need three DNS records, in roughly this
order of impact:

1. **SPF** — declares which servers are allowed to send mail for your
   domain. The single highest-leverage record. Add this first.
2. **DKIM** — cryptographically signs outbound mail so receivers can
   verify it hasn't been tampered with and really came from your
   server. cPanel generates the key for you.
3. **DMARC** — tells receivers how to handle mail that fails SPF or
   DKIM (monitor / quarantine / reject). Start in monitor mode.

After all three are in place and verifying, run a test through
<https://www.mail-tester.com/> — you should score 9/10 or 10/10.

---

## 1. SPF record

Add this **TXT** record at the DNS host that controls `playpadelwith.us`
(GoDaddy / Cloudflare / wherever you bought the domain — *not* cPanel
unless cPanel is also acting as nameserver).

| Field | Value |
|---|---|
| Type | `TXT` |
| Host / Name | `@` (sometimes shown as the bare domain `playpadelwith.us`) |
| Value | `v=spf1 +a +mx +ip4:148.251.90.41 ~all` |
| TTL | 3600 (1 hour) is fine |

What this means:

- `+a` — allow the IP that the `playpadelwith.us` A record points to
- `+mx` — allow whatever IPs your MX (mail-receiving) servers use
- `+ip4:148.251.90.41` — allow the cPanel server IP explicitly (the
  one currently serving `playpadelwith.us`)
- `~all` — soft-fail anything else (recommended starting point;
  upgrade to `-all` once you're confident)

Only one SPF record per domain is allowed. If a record already exists,
**edit it** rather than adding a second one.

### Verify

After ~15 minutes (or up to a few hours):

```
dig +short TXT playpadelwith.us
```

You should see the SPF string come back.

---

## 2. DKIM record

cPanel generates this for you.

1. Log into cPanel → search for **"Email Deliverability"** in the
   tools list (under the **Email** section).
2. Find `playpadelwith.us` in the table and click **Manage**.
3. cPanel will show one of two things:
   - **SPF**: status of the SPF record we set in Step 1. If it
     says "Not valid", click "Repair" to have cPanel insert a
     suggested record.
   - **DKIM**: status of the DKIM key. If it says "Not valid",
     click "Repair" — cPanel auto-generates a key and shows you
     the TXT record to add at your DNS host. Copy the value
     (it's long, starts with `v=DKIM1; k=rsa; p=...`) and add it
     at your DNS host:

| Field | Value |
|---|---|
| Type | `TXT` |
| Host / Name | `default._domainkey` (cPanel will tell you the exact selector) |
| Value | The long `v=DKIM1; ...` string cPanel showed you |
| TTL | 3600 |

4. After the DNS record propagates, go back to cPanel and re-check
   the DKIM status — it should turn green.

### Verify

```
dig +short TXT default._domainkey.playpadelwith.us
```

Should show the public key.

---

## 3. DMARC record

Add **after** SPF and DKIM both show valid, otherwise legitimate
mail might be quarantined unnecessarily.

| Field | Value |
|---|---|
| Type | `TXT` |
| Host / Name | `_dmarc` |
| Value | `v=DMARC1; p=none; rua=mailto:postmaster@playpadelwith.us` |
| TTL | 3600 |

Start with `p=none` (monitor only). Aggregate reports get sent to the
`rua=` address. After a week or two with no false positives, tighten:

- Move to `p=quarantine` (suspicious mail goes to spam)
- Eventually `p=reject` (suspicious mail bounces)

Note: `postmaster@playpadelwith.us` needs to exist as a real
mailbox or alias. Create it in cPanel → Email Accounts if it
doesn't already.

---

## Testing end-to-end

1. Go to <https://www.mail-tester.com/>. It gives you a one-time
   email address.
2. Register a new user account on `https://playpadelwith.us/register.php`
   using that address. The welcome email will arrive at mail-tester.
3. Click "Then check your score" on mail-tester.
4. Aim for **9/10 or higher**. The report tells you exactly what's
   missing if you score lower.

Then send a real test:

5. Log in as a regular user. Join a tournament.
6. Check the registration confirmation email arrives at your inbox
   (not spam) within 30 seconds.
7. Compare the email's raw headers (Gmail: ⋮ → "Show original") and
   confirm `SPF=PASS` and `DKIM=PASS` appear.

---

## If mail still doesn't arrive

In order of likelihood:

1. **Cached DNS** — wait an hour, then re-check with `dig`.
2. **`noreply@playpadelwith.us` not a real mailbox** — cPanel sender
   verification might reject mail with a non-existent From address.
   Either create the mailbox in cPanel → Email Accounts (no need to
   read it; just need it to exist) or change `EMAIL_NOREPLY` in
   `includes/config.php` to a real mailbox.
3. **cPanel "Sender Verification" or anti-spam enabled** —
   cPanel → Email → Default Address — make sure outbound isn't
   being intercepted.
4. **Last resort: switch to SMTP** — install
   [PHPMailer](https://github.com/PHPMailer/PHPMailer) and route
   through a transactional provider (SendGrid, Mailgun, Postmark,
   Resend). Free tiers handle a few thousand emails/month and give
   100% deliverability + bounce tracking. `mail()` over shared
   hosting is fundamentally unreliable even with perfect DNS.
