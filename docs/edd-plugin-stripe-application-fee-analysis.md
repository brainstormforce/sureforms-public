# Easy Digital Downloads (EDD): Stripe Application Fee Management Analysis

## Overview

Easy Digital Downloads implements a **self-contained application fee system** that charges a **3% per-transaction fee** for stores using Stripe Connect without valid licensing. Unlike more complex external API-driven systems, EDD's approach is primarily internal with minimal external dependencies.

## Database Storage

### WordPress Options Table
```php
// Primary storage locations
edd_stripe_connect_account_id       // Stripe Connect account ID
edd_stripe_connect_account_country  // Account country for geographic checks
edd_{item_name}_license_key         // License keys for various EDD products
```

**No dedicated database tables** - Application fee logic is computed in real-time, not pre-stored.

## Core Implementation

### 1. Hardcoded Fee Calculation

```php
// File: /src/Gateways/Stripe/ApplicationFee.php
private function get_application_fee_percentage() {
    return 3; // Hardcoded 3% - NO external API dependency
}

public function get_application_fee_amount( $amount ) {
    return round( $amount * ( $this->get_application_fee_percentage() / 100 ), 0 );
}
```

### 2. Fee Application Logic

```php
// File: /src/Gateways/Stripe/ApplicationFee.php
public function has_application_fee() {
    // Not connected to Stripe Connect
    if ( empty( edd_stripe()->connect()->get_connect_id() ) ) {
        return false;
    }

    // Country doesn't support application fees
    if ( true !== edds_stripe_connect_account_country_supports_application_fees() ) {
        return false;
    }

    // No license found - CHARGE FEE
    if ( ! $this->get_license() ) {
        return true;
    }

    // Valid license - NO FEE
    if ( $this->license->is_license_valid() ) {
        return false;
    }

    // New install grace period (72 hours) - NO FEE
    if ( $this->license->is_in_new_install_grace_period() ) {
        return false;
    }

    // Expired license grace period (14 days) - NO FEE
    if ( $this->license->is_in_grace_period() ) {
        return false;
    }

    // Default: charge fee
    return true;
}
```

### 3. Geographic Restrictions

```php
// File: /includes/gateways/stripe/includes/functions.php
function edds_stripe_connect_account_country_supports_application_fees() {
    $account_country = edd_get_option( 'stripe_connect_account_country', '' );
    
    $blocked_countries = array(
        'br', // Brazil
        'in', // India
        'mx', // Mexico
    );

    return ! in_array( strtolower( $account_country ), $blocked_countries, true );
}
```

## Payment Processing Integration

### Payment Intent Creation

```php
// File: /src/Gateways/Stripe/Checkout/Form.php
if ( edd_stripe()->application_fee->has_application_fee() ) {
    $application_fee = edd_stripe()->application_fee->get_application_fee_amount( $amount );
    if ( ! empty( $application_fee ) ) {
        $intent_args['application_fee_amount'] = $application_fee;
    }
}

$intent = edds_api_request( 'PaymentIntent', 'create', $intent_args );
```

### Invoice Processing (Webhooks)

```php
// File: /src/Gateways/Stripe/Webhooks/Events/InvoiceCreated.php
if ( edd_stripe()->application_fee->has_application_fee() ) {
    $fee_amount = $application_fee->get_application_fee_amount( $invoice_amount );

    edds_api_request(
        'Invoice',
        'update',
        $this->object->id,
        array(
            'application_fee_amount' => $fee_amount,
        )
    );
}
```

### Setup Intent (Subscriptions)

```php
// File: /src/Gateways/Stripe/Checkout/Setup.php
if ( edd_stripe()->application_fee->has_application_fee() ) {
    $setup_intent_args['application_fee_amount'] = 
        edd_stripe()->application_fee->get_application_fee_amount( $amount );
}
```

## License Management System

### License Types and Grace Periods

```php
// File: /src/Gateways/Stripe/License.php
public function is_license_valid() {
    return ! empty( $this->license_data->success ) && 'valid' === $this->license_data->license;
}

public function is_in_new_install_grace_period() {
    $installed = get_option( '_edd_stripe_connect_first_connect_date', 0 );
    $grace_end = $installed + ( HOUR_IN_SECONDS * 72 ); // 72 hours
    
    return time() < $grace_end;
}

public function is_in_grace_period() {
    if ( empty( $this->license_data->expires ) || 'lifetime' === $this->license_data->expires ) {
        return false;
    }
    
    $expiration = strtotime( $this->license_data->expires );
    $now = time();
    
    // 14 days grace period after expiration
    return ( $now - $expiration < ( DAY_IN_SECONDS * 14 ) );
}
```

### License Categories

**Fee Exempt Licenses:**
- Personal Pass
- Extended Pass  
- Professional Pass
- All Access Pass
- Individual product licenses (when valid)

## External API Dependencies

### Limited External Communication

**License Validation API:**
- **URL**: `https://easydigitaldownloads.com/edd-sl-api`
- **Purpose**: License status validation only
- **Does NOT**: Determine fee amounts or percentages

```php
// File: /src/Licensing/API.php
private $api_url = 'https://easydigitaldownloads.com/edd-sl-api';

public function make_request( $license_key, $item_id, $action = 'check_license' ) {
    $api_params = array(
        'edd_action' => $action,
        'license'    => $license_key,
        'item_id'    => $item_id,
        'url'        => home_url()
    );

    return wp_remote_post( $this->api_url, array(
        'timeout'   => 15,
        'sslverify' => false,
        'body'      => $api_params
    ));
}
```

## Business Logic Rules

### Fee Structure
- **No License**: 3% application fee (hardcoded)
- **Valid License**: 0% fee
- **New Install**: 72-hour grace period (no fee)
- **Expired License**: 14-day grace period (no fee)

### Geographic Restrictions
Application fees are **disabled** in:
- Brazil (BR)
- India (IN)
- Mexico (MX)

### Configuration Flow

1. **Plugin Installation**: Check for existing licenses
2. **Stripe Connect**: Link account and determine country
3. **License Check**: Validate against EDD API
4. **Fee Calculation**: Apply 3% if conditions met
5. **Payment Processing**: Add fee to Stripe API calls

## Key Files

```
src/Gateways/Stripe/ApplicationFee.php                    # Core fee logic
src/Gateways/Stripe/License.php                          # License validation
src/Gateways/Stripe/Connect.php                          # Stripe Connect integration
src/Gateways/Stripe/Checkout/Form.php                    # Payment Intent processing
src/Gateways/Stripe/Webhooks/Events/InvoiceCreated.php   # Invoice fee handling
src/Licensing/API.php                                     # License API communication
includes/gateways/stripe/includes/functions.php          # Geographic restrictions
```

## Security Considerations

- Fee percentage is hardcoded (cannot be manipulated)
- License validation uses secure API communication
- Grace periods stored as WordPress transients (secure)
- Geographic restrictions prevent fee application in blocked countries
- No admin interface to modify fee amounts (prevents tampering)

## Comparison with Give Plugin

| Aspect | Easy Digital Downloads | Give Plugin |
|--------|----------------------|-------------|
| **Fee Rate** | Fixed 3% (hardcoded) | Variable (2% default, API-controlled) |
| **External APIs** | License validation only | Fee calculation + license management |
| **Complexity** | Simple, transparent | Complex, external dependencies |
| **Fee Storage** | Real-time calculation | Database caching of API responses |
| **Geographic Logic** | Simple blocklist (3 countries) | Complex country-specific rules (6 countries) |
| **Configuration** | No admin settings | Configurable via external API |
| **Predictability** | Highly predictable | Variable based on external factors |

## Technical Summary

EDD implements a **transparent, self-contained application fee system** where:
- **Fixed 3% fee** for unlicensed Stripe Connect usage (hardcoded)
- **No external fee calculation APIs** - all logic is internal
- **Simple license validation** through EDD's own API
- **Clear grace periods** for new installs and expired licenses
- **Geographic restrictions** for regulatory compliance

**Fee Determination Hierarchy:**
1. **Hardcoded Logic**: 3% fee calculation is internal
2. **License Status**: External API validates license only
3. **Grace Periods**: Internal WordPress transients manage timing
4. **Geographic Rules**: Internal country blocklist

This creates a predictable, transparent system with minimal external dependencies.

---

## Non-Technical Summary

**What is it?**
Easy Digital Downloads has a built-in "service fee" that takes 3% from payments when stores use Stripe Connect without purchasing a license.

**How it works:**
- **Free/Unlicensed stores**: EDD automatically takes 3% from every sale
- **Licensed stores**: No fees taken (0%)
- **New stores**: 3-day grace period before fees start
- **Expired licenses**: 2-week grace period before fees resume
- **Some countries**: Fees disabled due to local regulations (Brazil, India, Mexico)

**Business purpose:**
- **Revenue generation**: The 3% fee helps fund EDD development
- **License incentive**: Store owners can eliminate fees by purchasing licenses
- **Sustainability**: Provides ongoing revenue for plugin maintenance

**Real-world example:**
- Customer buys a $100 digital product from an unlicensed EDD store
- Stripe processes the $100 payment
- EDD automatically takes $3 as an application fee
- Store owner receives $97 (minus regular Stripe fees)
- EDD keeps the $3 for development funding

**Key differences from other plugins:**
- **Fixed rate**: Always 3% (never changes)
- **Transparent**: Fee calculation is clearly visible in code
- **Predictable**: No surprise rate changes from external services
- **Simple**: No complex external APIs controlling fee amounts

**User impact:**
- **Unlicensed users**: Must factor in 3% fee when pricing products
- **Licensed users**: Get full sale amounts (no EDD fee)
- **Customers**: Don't see the fee - it's deducted behind the scenes
- **Store owners**: Need to understand this affects their actual revenue

**Grace periods help new users:**
- **3 days free**: New installations get 72 hours without fees
- **2 weeks buffer**: Expired licenses get 14 days before fees resume
- Gives time to evaluate and purchase licenses