# Give Plugin: Stripe Application Fee Management Analysis

## Overview

The Give WordPress donation plugin implements a freemium model using Stripe's `application_fee_amount` feature. This analysis covers how application fees are managed, stored, and applied during payment processing.

## Database Storage

### WordPress Options Table
```php
// Key options storing fee-related data
give_licenses                           // License information array
give_licenses_platform_fee_percentage   // Platform fee percentage (float)
give_licenses_refreshed_last_checked    // Last license refresh timestamp
```

**No dedicated database tables** - Application fee amounts are calculated dynamically during payment processing, not pre-stored.

## Core Implementation

### 1. Fee Permission Logic

```php
// File: src/PaymentGateways/Stripe/ApplicationFee.php
public static function canAddFee(): bool
{
    $gate = give(static::class);
    
    if (!$gate->doesCountrySupportApplicationFee()) {
        return false;
    }
    
    return $gate->licenseRepository->hasPlatformFeePercentage();
}
```

### 2. Country Restrictions

```php
// Hardcoded country restrictions
private function doesCountrySupportApplicationFee(): bool
{
    $unsupportedCountries = ['BR', 'IN', 'MY', 'MX', 'SG', 'TH'];
    $accountCountry = give_stripe_get_account_country();
    
    return !in_array($accountCountry, $unsupportedCountries, true);
}
```

### 3. Fee Percentage Calculation

```php
// File: src/License/Repositories/LicenseRepository.php
public function getPlatformFeePercentage(): float
{
    // Free users: 2% fee
    if (!$this->hasActiveLicenses()) {
        return 2.0;
    }
    
    // Premium users without stored fee: 0% fee
    if (!$this->hasStoredPlatformFeePercentage()) {
        return 0.0;
    }
    
    // Premium users with custom fee from license server
    return $this->getStoredPlatformFeePercentage();
}
```

### 4. Fee Amount Calculation

```php
// Helper function to calculate actual fee amount
function give_stripe_get_application_fee_amount( $amount ) {
    $percentage = give_stripe_get_application_fee_percentage();
    return round( $amount * $percentage / 100, 0 );
}

function give_stripe_get_application_fee_percentage() {
    $licenseRepository = give(LicenseRepository::class);
    return $licenseRepository->getPlatformFeePercentage();
}
```

## Payment Processing Integration

### Payment Intent (Stripe Elements)

```php
// File: includes/gateways/stripe/includes/payment-methods/class-give-stripe-payment-intent.php
if ( ApplicationFee::canAddfee() ) {
    $args['application_fee_amount'] = give_stripe_get_application_fee_amount( $args['amount'] );
}

$intent = \Stripe\PaymentIntent::create( $args, give_stripe_get_connected_account_options() );
```

### Checkout Session (Stripe Checkout)

```php
// File: includes/gateways/stripe/includes/payment-methods/class-give-stripe-checkout-session.php
if ( ApplicationFee::canAddfee() && isset( $args['payment_intent_data'] ) ) {
    $args['payment_intent_data']['application_fee_amount'] = 
        give_stripe_get_application_fee_amount( $args['line_items'][0]['amount'] );
}

$session = \Stripe\Checkout\Session::create( $args, give_stripe_get_connected_account_options() );
```

### Direct Charges (Legacy)

```php
// File: includes/gateways/stripe/includes/class-give-stripe-gateway.php
if ( ApplicationFee::canAddfee() ) {
    $charge_args['application_fee_amount'] = 
        give_stripe_get_application_fee_amount( $charge_args['amount'] );
}

$charge = \Stripe\Charge::create( $charge_args, give_stripe_get_connected_account_options() );
```

## Business Logic Rules

### Fee Structure
- **Free Plugin Users**: 2% application fee (hardcoded)
- **Premium License Holders**: 0% fee by default
- **Custom Agreements**: Variable percentage from license server

### Geographic Restrictions
Application fees are **disabled** in:
- Brazil (BR)
- India (IN) 
- Malaysia (MY)
- Mexico (MX)
- Singapore (SG)
- Thailand (TH)

### License Integration

```php
// License checking logic
private function hasActiveLicenses(): bool
{
    return !empty($this->getActiveLicenses());
}

private function getActiveLicenses(): array
{
    $licenses = get_option('give_licenses', []);
    return array_filter($licenses, function($license) {
        return $license['status'] === 'valid';
    });
}
```

## Configuration Flow

1. **Plugin Installation**: No active licenses → 2% fee applies
2. **License Activation**: Premium license → 0% fee (default)
3. **License Server Sync**: Custom fee percentage can be set remotely
4. **Payment Processing**: Fee calculated and applied to Stripe API calls

## Database Queries

```sql
-- Get current fee percentage
SELECT option_value FROM wp_options 
WHERE option_name = 'give_licenses_platform_fee_percentage';

-- Get license information
SELECT option_value FROM wp_options 
WHERE option_name = 'give_licenses';
```

## External API Dependencies

### License Server Communication

**Primary Endpoint:**
- **URL**: `https://givewp.com/edd-sl-api/`
- **Configurable**: Via `GIVE_LICENSE_API_URL` constant
- **Site URL**: `https://givewp.com/` (via `GIVE_SITE_URL`)

### API Actions

```php
// License checking and fee retrieval
$api_params = array(
    'action'      => 'check_licenses',
    'url'         => home_url(),
    'licenses'    => $licenses_data
);

$response = wp_remote_post( $license_url, array(
    'timeout'   => 15,
    'sslverify' => false,
    'body'      => $api_params
));
```

### Fee Determination Flow

1. **License Refresh**: `give_refresh_licenses()` calls external API
2. **Response Processing**: Extracts `gateway_fee` from license data  
3. **Local Storage**: Saves to `give_licenses_platform_fee_percentage`
4. **Fee Calculation**: Uses stored value or hardcoded fallbacks

### External Data Structure

```php
// License server response includes:
{
    "license": "valid",
    "gateway_fee": 1.5,  // Custom fee percentage from server
    "expires": "2024-12-31",
    "bundle_licenses": [...]
}
```

### Middleware Components

```php
// Core API communication
Give_License::request_license_api()         # Main API handler
ConnectClient                               # HTTP client
RefreshLicensesForPlatformFee              # Migration/refresh logic
```

## Key Files

```
src/PaymentGateways/Stripe/ApplicationFee.php          # Core fee logic
src/License/Repositories/LicenseRepository.php        # License management
includes/gateways/stripe/includes/give-stripe-helpers.php  # Helper functions
includes/gateways/stripe/includes/class-give-stripe-gateway.php  # Legacy charges
includes/gateways/stripe/includes/payment-methods/    # Modern payment methods
includes/admin/licenses/includes/misc-functions.php    # License API calls
includes/admin/licenses/includes/give-license.php      # License processing
```

## Security Considerations

- Fee percentage validated as float/numeric
- Country code validation against hardcoded list  
- License status verified before fee waiver
- Stripe Connect account verification required

## Technical Summary

Give plugin uses a **freemium monetization model** with **external API dependency** where:
- Free users pay 2% application fee on donations (hardcoded fallback)
- Premium license holders get fees determined by license server at `https://givewp.com/edd-sl-api/`
- Fees are calculated dynamically during payment processing using cached server data
- Geographic restrictions apply to comply with local regulations
- **External middleware**: License server controls actual fee percentages via `gateway_fee` response field

**Fee Determination Hierarchy:**
1. **External API**: License server provides custom fee percentage
2. **Local Cache**: Stored in `give_licenses_platform_fee_percentage` option
3. **Hardcoded Fallbacks**: 2% (unlicensed) or 0% (licensed without stored fee)

This creates a revenue stream for the plugin developers while incentivizing users to purchase premium licenses.

---

## Non-Technical Summary

**What is it?**
The Give donation plugin has a built-in "tax" system that takes a small percentage from donations processed through Stripe payments.

**How it works:**
- **Free version users**: Give automatically takes 2% from every donation
- **Premium version users**: Fee amount determined by Give's servers (usually 0%, but can be customized)
- **External control**: Give can remotely adjust fee percentages for premium users
- **Some countries**: Fees are completely disabled due to local laws

**Business purpose:**
- **Revenue generation**: The 2% fee helps fund plugin development
- **Upgrade incentive**: Users can eliminate fees by purchasing premium licenses
- **Sustainability**: Provides ongoing income to maintain and improve the plugin

**Real-world example:**
- Someone donates $100 through a free Give installation
- Stripe processes the $100 payment
- Give automatically takes $2 as an application fee
- The nonprofit receives $98 (minus regular Stripe fees)
- Give keeps the $2 to fund development

**Geographic limitations:**
The fee system doesn't work in Brazil, India, Malaysia, Mexico, Singapore, and Thailand due to local payment regulations.

**User impact:**
- **Free users**: Must factor in 2% fee when calculating expected donations
- **Premium users**: Get full donation amounts (no Give fee)
- **Donors**: Don't see the fee - it's deducted behind the scenes
- **Nonprofits**: Need to understand this affects their actual received amounts