# LatePoint Plugin: Stripe Application Fee Management Analysis

## Overview

LatePoint implements an **external API-driven application fee system** where all payment processing and fee calculations are handled by their servers at `app.latepoint.com`. This approach ensures complete control over monetization while preventing local tampering with fee logic.

## Architecture Design

### Server-Controlled Model
- **All Stripe operations** proxy through LatePoint's infrastructure
- **Plugin acts as thin client** with no local fee calculation
- **License validation** happens entirely server-side
- **Fee enforcement** is tamper-proof and centralized

## Database Storage

### No Local Fee Storage
Unlike other plugins, LatePoint stores minimal payment-related data locally:

```php
// Custom tables exist but no fee-specific columns
CREATE TABLE {$wpdb->prefix}latepoint_transactions (
    id int(11) NOT NULL AUTO_INCREMENT,
    token text,
    booking_id int(11) NOT NULL,
    customer_id int(11) NOT NULL,
    processor varchar(255) NOT NULL,
    amount decimal(10,2) NOT NULL,
    // No application_fee_amount column
);
```

### WordPress Options
```php
// Only stores Stripe account connection status
latepoint_stripe_connect_account_id_{environment}
latepoint_payments_environment  // 'live' or 'test'
latepoint_license_key          // Used for API authentication
```

## Core Implementation

### 1. External API Communication

```php
// File: /lib/helpers/stripe_connect_helper.php
define('LATEPOINT_STRIPE_CONNECT_URL', 'https://app.latepoint.com');

public static function do_account_request($route, $latepoint_environment, $payment_method, $method = 'POST', $vars = []) {
    $url = LATEPOINT_STRIPE_CONNECT_URL . "/" . $route;
    
    $default_headers = [
        'latepoint-version' => LATEPOINT_VERSION,
        'latepoint-domain' => OsUtilHelper::get_site_url(),
        'latepoint-license-key' => OsLicenseHelper::get_license_key(),
        'latepoint-environment' => $latepoint_environment,
        'latepoint-payment-method' => $payment_method
    ];
    
    $args = [
        'method' => $method,
        'headers' => $default_headers,
        'timeout' => 15,
        'body' => $vars
    ];
    
    $response = wp_remote_request($url, $args);
    // ... response handling
}
```

### 2. Payment Intent Creation

```php
// File: /lib/helpers/stripe_connect_helper.php
public static function create_payment_intent($options = [], $customer_data = []) {
    // All payment intents created via external API
    $result = self::do_account_request(
        'payment-intents', 
        OsSettingsHelper::get_payments_environment(), 
        '', 
        'POST', 
        [
            'payment_intent_options' => $options, 
            'customer_data' => $customer_data
        ]
    );
    
    // LatePoint server adds 2.9% fee for free users
    // Returns payment intent with fee already included
    return $result;
}
```

### 3. Fee Disclosure Display

```php
// File: /lib/helpers/stripe_connect_helper.php (lines 223-227)
public static function show_connect_fee_info() {
    $is_fee_applicable = apply_filters('latepoint_stripe_connect_transaction_fee', true);
    
    if ($is_fee_applicable) {
        echo '<div class="latepoint-stripe-fee-info">';
        echo '<div class="lp-fee-notice">' . __('2.9% Transaction Fee on All Payments', 'latepoint') . '</div>';
        echo '<div class="lp-fee-description">';
        echo __('Since you are using a free version of LatePoint, a 2.9% transaction fee will be charged on all payments.', 'latepoint');
        echo ' <a href="' . OsLicenseHelper::get_license_url() . '" target="_blank">';
        echo __('Upgrade your license', 'latepoint') . '</a> ';
        echo __('to remove this fee.', 'latepoint');
        echo '</div>';
        echo '</div>';
    }
}
```

## Payment Processing Flow

### 1. Customer Books Appointment
```php
// Booking process initiates payment
$booking->save();
$payment_intent = OsStripeConnectHelper::create_payment_intent([
    'amount' => $booking->get_total_amount(),
    'currency' => OsSettingsHelper::get_currency(),
    'metadata' => [
        'booking_id' => $booking->id,
        'customer_id' => $booking->customer_id
    ]
]);
```

### 2. LatePoint Server Processing
```
1. Receives payment request with license key
2. Validates license status:
   - Valid premium license → No fee
   - Free/invalid license → Add 2.9% application fee
3. Creates Stripe payment intent with appropriate fee
4. Returns payment intent to plugin
```

### 3. Plugin Completes Payment
```php
// Plugin receives and processes the server response
if ($payment_intent['status'] === 'succeeded') {
    $transaction->mark_as_paid();
    $booking->update_status('approved');
}
```

## License Management

### License Validation
```php
// File: /lib/helpers/license_helper.php
class OsLicenseHelper {
    public static function get_license_key() {
        return get_option('latepoint_license_key', '');
    }
    
    public static function is_license_active() {
        // License validation happens server-side
        // Local plugin cannot determine license status
        return self::check_license_status();
    }
    
    private static function check_license_status() {
        // Calls LatePoint API to verify license
        $response = wp_remote_get(LATEPOINT_STRIPE_CONNECT_URL . '/verify-license', [
            'headers' => [
                'latepoint-license-key' => self::get_license_key(),
                'latepoint-domain' => OsUtilHelper::get_site_url()
            ]
        ]);
        // ... process response
    }
}
```

## Business Logic Rules

### Fee Application Logic
- **Free Version**: Always charged 2.9% on all transactions
- **Premium License**: No transaction fees
- **Invalid License**: Treated as free version (2.9% fee)
- **No Geographic Restrictions**: Fee applies globally

### Server-Side Enforcement
```
if (license.isValid() && license.isPremium()) {
    // No application fee
    paymentIntent.application_fee_amount = 0;
} else {
    // Apply 2.9% fee
    paymentIntent.application_fee_amount = Math.round(amount * 0.029);
}
```

## External API Endpoints

### Primary API Routes
```
https://app.latepoint.com/payment-intents      // Create payment intent
https://app.latepoint.com/verify-license        // License validation
https://app.latepoint.com/stripe-connect        // Stripe account setup
https://app.latepoint.com/payment-methods       // Retrieve payment methods
```

### API Authentication
Every request includes:
- `latepoint-license-key`: License key for validation
- `latepoint-domain`: Website domain for verification
- `latepoint-version`: Plugin version compatibility check
- `latepoint-environment`: Live or test mode

## Security Considerations

### Advantages
- **Tamper-proof fees**: Cannot be bypassed by editing plugin files
- **Centralized control**: All fee logic managed server-side
- **License protection**: Cannot fake premium status locally
- **Secure payment flow**: All sensitive operations on secure servers

### Potential Concerns
- **API dependency**: Plugin requires internet connection for payments
- **Privacy**: All payment data flows through LatePoint servers
- **Vendor lock-in**: Cannot process payments without LatePoint API
- **Service availability**: Dependent on LatePoint server uptime

## Comparison with Other Plugins

| Aspect | LatePoint | Give | EDD | WPForms |
|--------|-----------|------|-----|---------|
| **Fee Model** | External API | External API | Self-contained | Self-contained |
| **Fee Rate** | 2.9% | 2% (variable) | 3% | 3% |
| **Local Control** | None | Minimal | Full | Full |
| **Tamper Resistance** | High | High | Low | Low |
| **API Dependency** | Required | Optional | None | OAuth only |
| **Fee Storage** | Server-side | Cached locally | None | None |
| **Transparency** | Low | Low | High | High |

## Key Files

```
/lib/helpers/stripe_connect_helper.php     # Core Stripe integration
/lib/helpers/license_helper.php            # License management
/lib/helpers/payment_helper.php            # Payment processing
/lib/models/transaction_model.php          # Transaction records
/lib/controllers/stripe_connect_controller.php  # API endpoints
```

## Technical Summary

LatePoint implements a **fully external, server-controlled application fee system** where:
- **2.9% transaction fee** for all free version users
- **Zero local fee calculation** - everything handled by app.latepoint.com
- **License-based exemption** validated entirely server-side
- **Proxy architecture** - plugin forwards all Stripe operations to LatePoint servers
- **Tamper-proof design** - fees cannot be bypassed locally

**Architecture Characteristics:**
1. **External Control**: All fee logic on LatePoint servers
2. **API Dependency**: Requires internet for all payments
3. **License Integration**: Server validates license for fee exemption
4. **Security**: High tamper resistance but privacy considerations

This represents the most controlled approach among analyzed plugins, trading transparency and local control for guaranteed monetization and security.

---

## Non-Technical Summary

**What is it?**
LatePoint booking plugin includes a transaction fee system that charges 2.9% on all appointment payments when using the free version.

**How it works:**
- **Free version users**: LatePoint takes 2.9% from every payment automatically
- **Premium license holders**: No transaction fees
- **Server-controlled**: Fees are managed by LatePoint's servers, not locally
- **Transparent notices**: Users see fee information in payment settings

**Business purpose:**
- **Revenue generation**: 2.9% fees fund LatePoint development
- **Upgrade incentive**: Businesses can eliminate fees with premium licenses
- **Sustainable model**: Ensures ongoing support and feature development
- **Fair pricing**: Pay-per-transaction instead of large upfront costs

**Real-world example:**
- Customer books a $100 appointment at a salon
- Payment processes through LatePoint's system
- LatePoint automatically deducts $2.90 as transaction fee
- Salon receives $97.10 (minus standard Stripe fees)
- LatePoint keeps the $2.90 for platform maintenance

**Key differences from other plugins:**
- **Server-controlled**: All fee calculations happen on LatePoint's servers
- **Cannot be modified**: No way to bypass fees by editing plugin files
- **Internet required**: Must connect to LatePoint servers for payments
- **Complete control**: LatePoint manages entire payment process

**Privacy considerations:**
- All payment data flows through LatePoint's servers
- Business and customer information shared with third-party
- Dependent on LatePoint's security and privacy practices
- Consider data sovereignty requirements for your region

**User impact:**
- **Free users**: Must factor 2.9% into service pricing
- **Premium users**: Get full payment amounts
- **Customers**: Don't see the fee directly
- **Business owners**: Need reliable internet for payment processing

**Upgrade benefits:**
- Eliminate 2.9% transaction fees immediately
- Reduce dependency on external servers
- Improve profit margins on all bookings
- Professional features and priority support

**Service reliability:**
The plugin requires LatePoint's servers to be operational for payment processing. Consider this dependency when choosing your booking solution, especially for mission-critical business operations.