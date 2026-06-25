# Payment Integration

SureForms supports Stripe payment processing with one-time payments and recurring subscriptions.

## Architecture Overview

```
inc/payments/
|-- payments.php              # Main payments orchestrator
|-- front-end.php             # Frontend payment handling
|-- payment-helper.php        # Shared payment utilities
|-- admin/
|   |-- admin-handler.php     # Admin payment management UI
|-- stripe/
    |-- payments-settings.php # Stripe settings + REST routes
    |-- stripe-helper.php     # Stripe API utilities
    |-- stripe-webhook.php    # Webhook event handler
    |-- admin-stripe-handler.php # Admin Stripe config
```

## Stripe Setup

### Configuration

Stripe settings are managed via the admin settings page and stored as WordPress options:

1. Navigate to SureForms > Settings > Payments
2. Connect your Stripe account via OAuth flow
3. Configure test/live mode
4. Set default currency

The `Payments_Settings` class registers REST routes for settings management and handles the Stripe OAuth callback via `admin_init`.

### OAuth Flow

1. Admin clicks "Connect Stripe" in settings
2. `intercept_stripe_callback()` on `admin_init` handles the OAuth redirect
3. Stripe credentials are securely stored in WordPress options

## Payment Flow

```
1. FORM DISPLAY
   - Payment block renders Stripe Elements
   - Frontend_Assets enqueues Stripe JS SDK
   - Payment form elements injected into the form

2. FORM SUBMISSION
   - User fills form + enters payment details
   - Frontend JavaScript creates Stripe PaymentIntent
   - Form data + payment token submitted to /submit-form

3. SERVER PROCESSING
   - Form_Submit validates form data
   - Payment processing triggered
   - Stripe API called to confirm payment
   - Payment record created in wp_srfm_payments table
   - Entry created in wp_srfm_entries table
   - Payment ID linked to entry

4. WEBHOOK HANDLING
   - Stripe sends webhook events to /wp-json endpoint
   - Stripe_Webhook validates signature
   - Payment status updated in database
   - Subscription events handled (renewal, cancellation, etc.)
```

## Payment Database Schema

Payments are stored in the `wp_srfm_payments` custom table. See [Database Schema](Database-Schema) for full column definitions.

### Payment Types

| Type | Description |
|------|-------------|
| `payment` | One-time payment |
| `subscription` | Recurring subscription record |
| `renewal` | Subscription renewal payment |

### Payment Statuses

`pending`, `succeeded`, `failed`, `canceled`, `requires_action`, `requires_payment_method`, `processing`, `refunded`, `partially_refunded`

### Subscription Statuses

`active`, `canceled`, `past_due`, `unpaid`, `trialing`, `incomplete`, `incomplete_expired`, `paused`

## Webhook Handling

The `Stripe_Webhook` class (`inc/payments/stripe/stripe-webhook.php`) processes Stripe webhook events:

- Validates webhook signature using Stripe signing secret
- Handles payment status updates (succeeded, failed, etc.)
- Processes subscription lifecycle events (renewal, cancellation)
- Creates renewal payment records for subscription billing cycles
- Updates payment and subscription statuses in the database

## Refund Management

The `Payments` table class provides refund tracking:

- `add_refund_to_payment_data()` -- Record refund details in payment_data JSON
- `add_refund_amount()` -- Increment the refunded_amount column
- `get_refundable_amount()` -- Calculate remaining refundable balance
- `is_fully_refunded()` / `is_partially_refunded()` -- Status checks

## Supported Currencies

USD, EUR, GBP, JPY, CAD, AUD, CHF, CNY, SEK, NZD, MXN, SGD, HKD, NOK, TRY, RUB, INR, BRL, ZAR, KRW

## Payment Modes

| Mode | Description |
|------|-------------|
| `test` | Stripe test mode (test API keys) |
| `live` | Stripe live mode (production) |

## Admin Payment Management

The `Admin_Handler` class provides the admin interface for viewing and managing payments. Payment IDs in entry views are rendered as clickable links via the `srfm_entry_value` filter.

## Related Pages

- [Database Schema](Database-Schema) -- Payment table columns and relationships
- [Form Submission Flow](Form-Submission-Flow) -- Payment processing in submission pipeline
- [REST API Reference](REST-API-Reference) -- Payment-related endpoints
- [Gutenberg Blocks](Gutenberg-Blocks) -- Payment block type
