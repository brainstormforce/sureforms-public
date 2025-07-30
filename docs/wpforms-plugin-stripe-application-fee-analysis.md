# WPForms Plugin: Stripe Application Fee Management Analysis

## Overview

WPForms implements a **self-contained, hardcoded application fee system** with sophisticated license management. Unlike Give's external API dependency, WPForms uses internal logic with hardcoded 3% fees while incorporating geographic restrictions and multi-tier license exemptions.

## Database Storage

### WPForms Custom Tables
```sql
-- wp_wpforms_payments table (primary payment storage)
CREATE TABLE wp_wpforms_payments (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    form_id bigint(20) NOT NULL,
    status varchar(10) NOT NULL DEFAULT '',
    subtotal_amount decimal(26,8) NOT NULL DEFAULT 0,
    total_amount decimal(26,8) NOT NULL DEFAULT 0,
    currency varchar(3) NOT NULL DEFAULT '',
    gateway varchar(20) NOT NULL DEFAULT '',
    -- No specific application_fee_amount column
    PRIMARY KEY (id)
);
```

### WordPress Options Table
```php
// Key storage locations
wpforms_stripe_{mode}_account_country    // Stripe account country for geo restrictions
wpforms_license                          // License information and status
wpforms_stripe_{mode}_connect_account_id // Stripe Connect account details
```

## Core Implementation

### 1. Hardcoded Fee Calculation

```php
// File: /src/Integrations/Stripe/Process.php

// One-time payments (line 660-662)
if ( ! Helpers::is_license_ok() && Helpers::is_application_fee_supported() ) {
    $args['application_fee_amount'] = (int) ( round( $this->amount * 0.03, 2 ) * $amount_decimals );
}

// Subscriptions (line 1059-1061)
if ( ! Helpers::is_license_ok() && Helpers::is_application_fee_supported() ) {
    $args['application_fee_percent'] = 3; // 3% for recurring payments
}
```

### 2. License Validation Logic

```php
// File: /src/Integrations/Stripe/Helpers.php
public static function is_license_ok() {
    return self::is_license_active() && self::is_allowed_license_type();
}

public static function is_allowed_license_type() {
    return in_array( 
        wpforms_get_license_type(), 
        [ 'pro', 'elite', 'agency', 'ultimate' ], 
        true 
    );
}

public static function is_license_active() {
    $license = (array) get_option( 'wpforms_license', [] );
    
    return ! empty( wpforms_get_license_key() ) &&
           empty( $license['is_expired'] ) &&
           empty( $license['is_disabled'] ) &&
           empty( $license['is_invalid'] );
}
```

### 3. Geographic Restrictions

```php
// File: /src/Integrations/Stripe/Helpers.php
public static function is_application_fee_supported() {
    $blocked_countries = [ 'br', 'in', 'mx' ]; // Brazil, India, Mexico
    return ! in_array( self::get_account_country(), $blocked_countries, true );
}

private static function get_account_country(): string {
    $mode = self::get_stripe_mode(); // 'live' or 'test'
    return get_option( "wpforms_stripe_{$mode}_account_country", '' );
}
```

## Payment Processing Integration

### Stripe Payment Intent Creation

```php
// File: /src/Integrations/Stripe/Process.php
private function process_single() {
    $args = [
        'amount'                => $this->amount,
        'currency'              => strtolower( $this->currency ),
        'automatic_payment_methods' => [ 'enabled' => true ],
        'metadata'              => $this->get_intent_metadata(),
    ];

    // Add application fee for unlicensed/basic users
    if ( ! Helpers::is_license_ok() && Helpers::is_application_fee_supported() ) {
        $amount_decimals = Helpers::get_currency_decimals( $this->currency );
        $args['application_fee_amount'] = (int) ( round( $this->amount * 0.03, 2 ) * $amount_decimals );
    }

    return $this->stripe->paymentIntents->create( $args );
}
```

### Subscription Processing

```php
// File: /src/Integrations/Stripe/Process.php
private function process_subscription() {
    $args = [
        'customer'    => $this->customer_id,
        'items'       => [ [ 'price' => $this->price_id ] ],
        'expand'      => [ 'latest_invoice.payment_intent' ],
        'metadata'    => $this->get_subscription_metadata(),
    ];

    // Add percentage-based fee for subscriptions
    if ( ! Helpers::is_license_ok() && Helpers::is_application_fee_supported() ) {
        $args['application_fee_percent'] = 3;
    }

    return $this->stripe->subscriptions->create( $args );
}
```

## License Management System

### License Tiers and Fee Structure

| License Type | Fee Status | Description |
|-------------|------------|-------------|
| **Free/Lite** | 3% fee | No license key, always charged |
| **Basic** | 3% fee | Entry-level paid license, still charged |
| **Pro** | No fee | Mid-tier license, fee exemption starts here |
| **Elite** | No fee | Advanced license, no fees |
| **Agency** | No fee | Multi-site license, no fees |
| **Ultimate** | No fee | Top-tier license, no fees |

### License Status Validation

```php
// File: /src/Integrations/Stripe/Helpers.php
public static function get_license_notice_text() {
    if ( ! self::is_license_active() ) {
        return sprintf(
            __( '<strong>Pay-as-you-go Pricing</strong><br>3%% fee per-transaction + Stripe fees. <a href="%s">Activate your license</a> to remove additional fees...', 'wpforms' ),
            admin_url( 'admin.php?page=wpforms-settings&view=license' )
        );
    }

    if ( ! self::is_allowed_license_type() ) {
        return sprintf(
            __( '<strong>Pay-as-you-go Pricing</strong><br> 3%% fee per-transaction + Stripe fees. <a href="%s" target="_blank">Upgrade to Pro</a> to remove additional fees...', 'wpforms' ),
            wpforms_utm_link( 'https://wpforms.com/pricing/', 'Settings - Payments', 'Upgrade to Pro' )
        );
    }

    return '';
}
```

## External API Dependencies

### Limited External Communication

**Stripe Connect OAuth:**
- **Single endpoint**: `https://wpforms.com/oauth/stripe-connect`
- **Purpose**: Credential exchange during Stripe Connect setup only
- **No fee calculation**: Does NOT determine or modify fee amounts

```php
// File: /src/Integrations/Stripe/Admin/Connect.php
class Connect {
    const WPFORMS_URL = 'https://wpforms.com/oauth/stripe-connect';

    protected function fetch_stripe_credentials( $state ) {
        $response = wp_remote_post(
            self::WPFORMS_URL,
            [
                'timeout' => 30,
                'body'    => [
                    'action' => 'credentials',
                    'state'  => $state,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        return json_decode( $body, true );
    }
}
```

## Business Logic Rules

### Fee Application Conditions

**Fees are charged when ALL conditions are met:**
1. License is NOT active OR license type is below 'pro' level
2. Stripe account country supports application fees (not BR, IN, MX)
3. Form has Stripe payment processing enabled
4. Payment is processed through Stripe Connect

**Fee exemption hierarchy:**
```php
// Priority order for fee exemption:
1. Invalid/expired/disabled license → Charge 3% fee
2. Active license but 'basic' type → Charge 3% fee  
3. Active 'pro'+ license → No fees
4. Geographic restriction → No fees (regardless of license)
```

## Admin Interface Integration

### Settings Page Notices

```php
// File: /src/Integrations/Stripe/Admin/Builder.php
private function get_stripe_fee_notice() {
    if ( Helpers::is_license_ok() || ! Helpers::is_application_fee_supported() ) {
        return '';
    }

    return sprintf(
        '<div class="wpforms-alert wpforms-alert-info">%s</div>',
        Helpers::get_license_notice_text()
    );
}
```

### Payment Form Integration

```php
// Display fee information in form builder
if ( ! Helpers::is_license_ok() && Helpers::is_application_fee_supported() ) {
    echo '<p class="wpforms-stripe-credit-card-cardinfo-description">';
    echo __( 'Additional 3% processing fee applies.', 'wpforms' );
    echo '</p>';
}
```

## Key Files

```
src/Integrations/Stripe/Process.php                    # Core payment processing
src/Integrations/Stripe/Helpers.php                   # License and geo validation
src/Integrations/Stripe/Admin/Connect.php             # Stripe Connect setup
src/Integrations/Stripe/Admin/Builder.php             # Form builder integration
includes/functions.php                                 # License utility functions
pro/includes/admin/settings/license.php               # License management
```

## Security Considerations

- **Hardcoded fee rates** prevent tampering
- **License validation** uses WordPress options securely stored
- **Geographic restrictions** comply with regional regulations
- **No external fee APIs** reduce attack surface
- **Clear user notifications** about fee structure provide transparency

## Comparison with Other Plugins

| Aspect | WPForms | Give Plugin | Easy Digital Downloads |
|--------|---------|-------------|----------------------|
| **Fee System** | Self-contained hardcoded | External API-driven | Self-contained hardcoded |
| **Fee Rate** | Fixed 3% | Variable (2% default, API-controlled) | Fixed 3% |
| **License Tiers** | 6 tiers (4 fee-exempt) | Simple active/inactive | Simple active/inactive |
| **External APIs** | OAuth setup only | Fee calculation + license | License validation only |
| **Geographic Logic** | 3-country blocklist | 6-country dynamic | 3-country blocklist |
| **Fee Storage** | Real-time calculation | Database caching | Real-time calculation |
| **User Interface** | Integrated notices | Minimal UI | No admin settings |
| **Transparency** | High (clear notices) | Low (hidden API logic) | Medium (code-visible) |

## Technical Summary

WPForms implements a **sophisticated yet self-contained application fee system** where:
- **Fixed 3% fee** for non-Pro licenses (hardcoded, no external APIs)
- **Multi-tier license exemptions** with clear upgrade paths
- **Geographic restrictions** for regulatory compliance (BR, IN, MX)
- **Transparent user notifications** about pay-as-you-go pricing
- **Stripe-specific integration** using standard application_fee parameters

**Fee Determination Hierarchy:**
1. **Hardcoded Logic**: 3% fee calculation is entirely internal
2. **License Validation**: WordPress options determine exemption status
3. **Geographic Rules**: Internal country blocklist overrides fees
4. **No External Dependencies**: All logic is self-contained

This represents a balanced approach between Give's complex external API system and EDD's basic hardcoded implementation, offering transparency and predictability while maintaining sophisticated business logic.

---

## Non-Technical Summary

**What is it?**
WPForms includes a "pay-as-you-go" pricing model that charges a 3% fee on form payments processed through Stripe when users don't have Pro-level licenses.

**How it works:**
- **Free/Lite/Basic users**: WPForms takes 3% from every payment
- **Pro+ license holders**: No additional fees taken
- **Geographic exceptions**: Fees disabled in Brazil, India, and Mexico
- **Clear notifications**: Users see fee information in admin settings

**Business purpose:**
- **Revenue generation**: 3% fees fund WPForms development and support
- **Upgrade incentive**: Encourages users to purchase Pro+ licenses
- **Fair pricing**: Free users pay per transaction instead of upfront costs
- **Sustainable model**: Provides ongoing revenue for feature development

**Real-world example:**
- Customer submits a donation form with $100 payment
- Stripe processes the payment through WPForms
- WPForms automatically deducts $3 as application fee (for non-Pro users)
- Website owner receives $97 (minus standard Stripe fees)
- WPForms keeps the $3 for platform maintenance

**License structure:**
- **Free/Lite**: Always pay 3% fee
- **Basic**: Still pay 3% fee (entry-level paid license)
- **Pro/Elite/Agency/Ultimate**: No WPForms fees

**Key advantages:**
- **Transparent pricing**: Fee structure clearly displayed to users
- **Predictable costs**: Fixed 3% rate, no surprise changes
- **No external dependencies**: All logic built into the plugin
- **Fair usage model**: Pay only when actually processing payments

**User impact:**
- **Free users**: Can use WPForms for payments but pay per transaction
- **Pro+ users**: Get full payment amounts with no additional fees
- **Form visitors**: Don't see the fee - deducted behind the scenes
- **Website owners**: Need to factor fees into pricing strategies

**Geographic considerations:**
The fee system respects local payment regulations by automatically disabling fees in countries where such charges aren't permitted, ensuring global compliance.

**Upgrade path:**
WPForms makes the upgrade path clear with built-in notices that show exactly how much users could save by upgrading to Pro licenses, helping them make informed decisions about their payment processing costs.