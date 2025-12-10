# Booking sandbox test plan

Use this checklist to validate the full reservation purchase flow without charging real cards. The steps rely on the Payment Settings screen in the admin dashboard.

## 1) Enable test mode and keys
1. Go to **Lofts 1325 ▸ Payment Settings** in the WP admin menu.
2. Check **Enable Stripe test mode**.
3. Enter your **test publishable** and **test secret** keys from the Stripe dashboard.
4. Save changes and confirm that the Active mode banner shows **TEST**.

## 2) Run a sandbox booking
1. Open the public booking form and select a loft, check-in, and check-out dates.
2. Proceed through checkout using a Stripe test card (e.g., `4242 4242 4242 4242`, any future expiry, any CVC).
3. Submit the payment and wait for the confirmation page.

## 3) Verify downstream records
- Confirm the booking appears in the **Bookings** admin list with the correct dates, guest name, and contact info.
- Check for the confirmation email and ensure the payment status/amount match the test order.
- In Stripe's **Test data** view, verify the Payment Intent was created with the same amount and metadata (guest email, booking ID, dates).

## 4) Switch back to live
1. Return to **Payment Settings**.
2. Uncheck **Enable Stripe test mode** to reactivate the live keys.
3. Save changes.

Document the results of each step for future QA runs to keep the sandbox purchase flow reproducible.
