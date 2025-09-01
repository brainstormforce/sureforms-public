# SureForms Payment Middleware Documentation

## Overview

The SureForms Payment Middleware is a standalone PHP API that processes payments through Stripe with license-based application fee management. It provides a secure, scalable solution for handling payment intents, subscriptions, and webhooks while integrating with SureCart for license verification.

### Key Features

- **Payment Intent Management**: Create, retrieve, update, and cancel Stripe PaymentIntents
- **Refund Processing**: Create full or partial refunds for existing payments
- **Subscription Management**: Handle recurring payments and subscription lifecycle
- **License-Based Fees**: Dynamic application fee calculation based on SureForms license tiers
- **Webhook Support**: Event-driven payment notifications and status updates
- **Security**: Input validation, error handling, and secure API communication
- **Cross-Origin Support**: CORS headers for frontend integration

### Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend App  │───▶│  Middleware API │───▶│  Stripe API     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                               │
                               ▼
                        ┌─────────────────┐
                        │  SureCart API   │
                        └─────────────────┘
```

## Installation & Setup

### Requirements

- PHP 7.4 or higher
- Composer
- cURL extension
- JSON extension
- Stripe account with API keys
- SureCart account with secret key

### Dependencies

```json
{
    "require": {
        "stripe/stripe-php": "^17.5"
    }
}
```

### Installation Steps

1. **Clone or download the middleware files**
   ```bash
   # Copy index.php to your server
   # Copy composer.json to your server
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure constants** (in index.php lines 26-36)
   ```php
   // SureForms product IDs from SureCart
   define('SRFM_STARTER_PRODUCT_ID',  'your-starter-product-id');
   define('SRFM_PRO_PRODUCT_ID',      'your-pro-product-id');
   define('SRFM_BUSINESS_PRODUCT_ID', 'your-business-product-id');
   
   // Default application fee for unlicensed users
   define('SRFM_DEFAULT_APPLICATION_FEE', 2.9);
   
   // SureCart API configuration
   define('SURECART_SECRET_KEY', 'your-surecart-secret-key');
   ```

4. **Web server configuration**
   - Ensure PHP can write to error logs
   - Configure HTTPS for production
   - Set appropriate file permissions

## Configuration

### License Tier Configuration

The middleware supports three license tiers with different application fees:

| License Tier | Product ID | Application Fee |
|-------------|------------|----------------|
| Starter     | `SRFM_STARTER_PRODUCT_ID` | 1.0% |
| Pro         | `SRFM_PRO_PRODUCT_ID` | 0.0% |
| Business    | `SRFM_BUSINESS_PRODUCT_ID` | 0.0% |
| Free/Unlicensed | N/A | 2.9% |

### Environment Variables

You can optionally use environment variables instead of hardcoded constants:

```php
// Example environment variable usage
define('SURECART_SECRET_KEY', $_ENV['SURECART_SECRET_KEY'] ?? 'fallback-key');
define('SRFM_DEFAULT_APPLICATION_FEE', $_ENV['DEFAULT_FEE'] ?? 2.9);
```

## API Reference

### Base URL Structure

```
https://yourdomain.com/path-to-middleware/
```

### Authentication

All requests require a Stripe secret key in the request body:

```json
{
    "secret_key": "sk_test_...",
    // other parameters
}
```

### Request Format

All request bodies should be **base64-encoded JSON**:

```javascript
const requestData = {
    secret_key: "sk_test_...",
    // other parameters
};

fetch('/payment-intent/create', {
    method: 'POST',
    body: btoa(JSON.stringify(requestData)),
    headers: {
        'Content-Type': 'application/json'
    }
});
```

---

## Payment Intent Endpoints

### Create Payment Intent

**Endpoint**: `POST /payment-intent/create`

Creates a new Stripe PaymentIntent with calculated application fees.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "amount": 2000,
    "currency": "usd",
    "description": "SureForms Payment",
    "license_key": "your-license-key"
}
```

**Parameters**:
- `secret_key` (required): Stripe secret key
- `amount` (required): Amount in cents (e.g., 2000 = $20.00)
- `currency` (optional): 3-character ISO currency code (default: "usd")
- `description` (optional): Payment description
- `license_key` (optional): SureForms license key for fee calculation

**Response** (Success - 200):
```json
{
    "id": "pi_1234567890",
    "client_secret": "pi_1234567890_secret_...",
    "amount": 2000,
    "currency": "usd",
    "status": "requires_payment_method",
    "application_fee_amount": 58,
    // ... other Stripe PaymentIntent properties
}
```

**Response** (Error - 400):
```json
{
    "code": "invalid_amount",
    "message": "Amount is required and must be greater than 0",
    "status": "error"
}
```

### Retrieve Payment Intent

**Endpoint**: `POST /payment-intent/retrieve`

Retrieves an existing PaymentIntent by ID.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "payment_intent_id": "pi_1234567890"
}
```

**Parameters**:
- `secret_key` (required): Stripe secret key
- `payment_intent_id` (required): PaymentIntent ID (format: `pi_...`)

### Update Payment Intent

**Endpoint**: `POST /payment-intent/update`

Updates an existing PaymentIntent with a new amount and recalculates fees.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "payment_intent_id": "pi_1234567890",
    "amount": 3000,
    "license_key": "your-license-key"
}
```

**Parameters**:
- `secret_key` (required): Stripe secret key
- `payment_intent_id` (required): PaymentIntent ID
- `amount` (required): New amount in cents
- `license_key` (optional): License key for fee recalculation

### Delete Payment Intent

**Endpoint**: `DELETE /payment-intent/delete`

Cancels an existing PaymentIntent.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "payment_intent_id": "pi_1234567890"
}
```

### Capture Payment Intent

**Endpoint**: `POST /payment-intent/capture`

Captures a PaymentIntent that was created with manual capture. This is used for delayed capture scenarios where payment authorization and capture happen at different times.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "payment_intent_id": "pi_1234567890"
}
```

**Parameters**:
- `secret_key` (required): Stripe secret key
- `payment_intent_id` (required): PaymentIntent ID (format: `pi_...`)

**Response** (Success - 200):
```json
{
    "id": "pi_1234567890",
    "amount": 2000,
    "amount_capturable": 0,
    "amount_received": 2000,
    "currency": "usd",
    "status": "succeeded",
    "charges": {
        "data": [
            {
                "id": "ch_1234567890",
                "amount": 2000,
                "captured": true,
                "status": "succeeded"
            }
        ]
    }
}
```

**Response** (Error - 400):
```json
{
    "code": "payment_intent_not_capturable",
    "message": "PaymentIntent cannot be captured in its current status: succeeded",
    "status": "error"
}
```

**Common Error Scenarios**:
- PaymentIntent not in `requires_capture` status
- PaymentIntent already captured
- Invalid PaymentIntent ID

---

## Subscription Endpoints

### Create Subscription

**Endpoint**: `POST /subscription/create`

Creates a new Stripe subscription with dynamic pricing and application fees.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "customer": "cus_1234567890",
    "amount": 2000,
    "currency": "usd",
    "interval": "month",
    "interval_count": 1,
    "description": "SureForms Subscription",
    "license_key": "your-license-key",
    "trial_period_days": 7,
    "block_id": "block_123"
}
```

**Parameters**:
- `secret_key` (required): Stripe secret key
- `customer` (required): Stripe customer ID
- `amount` (required): Amount in cents
- `currency` (optional): Currency code (default: "usd")
- `interval` (optional): Billing interval ("month", "year", "week", "day")
- `interval_count` (optional): Number of intervals (default: 1)
- `description` (optional): Subscription description
- `license_key` (optional): License key for fee calculation
- `trial_period_days` (optional): Trial period in days
- `block_id` (optional): Block identifier for metadata

### Update Subscription

**Endpoint**: `POST /subscription/update`

Updates an existing subscription.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "subscription_id": "sub_1234567890",
    "price": "price_1234567890",
    "metadata": {
        "key": "value"
    }
}
```

### Retrieve Subscription

**Endpoint**: `POST /subscription/retrieve`

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "subscription_id": "sub_1234567890"
}
```

### Cancel Subscription

**Endpoint**: `DELETE /subscription/cancel`

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "subscription_id": "sub_1234567890"
}
```

---

## Refund Endpoints

### Create Refund

**Endpoint**: `POST /refund/create`

Creates a refund for an existing payment intent or charge. Supports both full and partial refunds with optional metadata and reason codes.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "payment_intent": "pi_1234567890",
    "amount": 1000,
    "reason": "requested_by_customer",
    "metadata": {
        "source": "SureForms",
        "payment_id": "123",
        "refunded_by": "admin_user"
    }
}
```

**Parameters**:
- `secret_key` (required): Stripe secret key
- `payment_intent` (optional): PaymentIntent ID (format: `pi_...`) - use this OR charge
- `charge` (optional): Charge ID (format: `ch_...`) - use this OR payment_intent  
- `amount` (optional): Amount to refund in cents. If omitted, full refund is processed
- `reason` (optional): Refund reason (`duplicate`, `fraudulent`, `requested_by_customer`, `expired_uncaptured_charge`)
- `metadata` (optional): Key-value pairs for tracking refund context

**Response** (Success - 200):
```json
{
    "id": "re_1234567890",
    "amount": 1000,
    "charge": "ch_1234567890", 
    "created": 1640995200,
    "currency": "usd",
    "metadata": {
        "source": "SureForms",
        "payment_id": "123",
        "refunded_by": "admin_user"
    },
    "payment_intent": "pi_1234567890",
    "reason": "requested_by_customer",
    "status": "succeeded"
}
```

**Response** (Error - 400):
```json
{
    "code": "refund_failed",
    "message": "Refund failed: Charge pi_1234567890 has already been refunded.",
    "status": "error"
}
```

**Common Error Scenarios**:
- Payment already fully refunded
- Refund amount exceeds available balance
- Payment not eligible for refund (e.g., disputed, failed)
- Invalid payment intent or charge ID

---

## Webhook Endpoints

### Create Webhook

**Endpoint**: `POST /webhook/create`

Creates a webhook endpoint for payment event notifications.

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "webhook_url": "https://yoursite.com/webhook-handler"
}
```

**Supported Events**:
- `charge.failed`
- `charge.succeeded`
- `payment_intent.succeeded`
- `charge.refunded`
- `charge.dispute.created`
- `charge.dispute.closed`
- `review.opened`
- `review.closed`

### Delete Webhook

**Endpoint**: `DELETE /webhook/delete`

**Request Body**:
```json
{
    "secret_key": "sk_test_...",
    "id": "we_1234567890"
}
```

### Validate Webhook Signature

**Endpoint**: `POST /webhook/validate-signature`

Validates webhook signatures to ensure the request is genuine and comes from Stripe. This endpoint is useful for verifying webhook payloads before processing them.

**Request Body**:
```json
{
    "payload": "webhook_payload_string",
    "signature": "webhook_signature_header", 
    "webhook_secret": "whsec_your_webhook_endpoint_secret"
}
```

**Parameters**:
- `payload` (required): The raw webhook payload body as received from Stripe
- `signature` (required): The `Stripe-Signature` header value from the webhook request
- `webhook_secret` (required): Your webhook endpoint secret (starts with `whsec_`)

**Response** (Valid Signature - 200):
```json
{
    "valid": true,
    "event_type": "payment_intent.succeeded",
    "event_id": "evt_1234567890"
}
```

**Response** (Invalid Signature - 200):
```json
{
    "valid": false,
    "error": "Invalid webhook signature: No signatures found matching the expected signature for payload"
}
```

**Response** (Invalid Payload - 200):
```json
{
    "valid": false,
    "error": "Invalid webhook payload: Invalid JSON payload"
}
```

**Response** (Error - 400/500):
```json
{
    "code": "missing_required_field",
    "message": "Required field 'payload' is missing or empty",
    "status": "error"
}
```

**Usage Example**:
```javascript
// Frontend JavaScript example
async function validateWebhookSignature(payload, signature, webhookSecret) {
    const requestData = {
        payload: payload,
        signature: signature,
        webhook_secret: webhookSecret
    };

    const response = await fetch('/webhook/validate-signature', {
        method: 'POST',
        body: btoa(JSON.stringify(requestData)),
        headers: {
            'Content-Type': 'application/json'
        }
    });

    const result = await response.json();
    
    if (result.valid) {
        console.log('Webhook signature is valid:', result.event_type);
        return true;
    } else {
        console.error('Webhook signature validation failed:', result.error);
        return false;
    }
}
```

**PHP Example**:
```php
<?php
// PHP integration example
function validateWebhookWithMiddleware($payload, $signature, $webhookSecret, $middlewareUrl) {
    $data = [
        'payload' => $payload,
        'signature' => $signature,
        'webhook_secret' => $webhookSecret
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $middlewareUrl . '/webhook/validate-signature',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => base64_encode(json_encode($data)),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['valid'] ?? false;
}

// Usage in webhook handler
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$webhookSecret = 'whsec_your_secret';

if (validateWebhookWithMiddleware($payload, $signature, $webhookSecret, 'https://your-middleware.com')) {
    // Process the webhook
    echo "Webhook is valid, processing...";
} else {
    http_response_code(400);
    echo "Invalid webhook signature";
}
```

**Common Use Cases**:
- Validating webhooks in applications that can't directly use Stripe SDK
- Centralized webhook signature validation for multiple services
- Testing webhook signatures during development
- Validating webhooks in environments where Stripe SDK installation isn't possible

---

## Error Handling

### Common Error Codes

| Code | Message | HTTP Status | Description |
|------|---------|-------------|-------------|
| `invalid_request` | Invalid API route / method not allowed | 404 | Unsupported endpoint |
| `invalid_payment_intent_data` | Invalid JSON payment_intent_data | 400 | Malformed request body |
| `invalid_amount` | Amount is required and must be greater than 0 | 400 | Invalid amount parameter |
| `stripe_key_missing` | Stripe secret key not found | 400 | Missing secret key |
| `payment_intent_failed` | Failed to create payment intent | 500 | Stripe API error |
| `payment_intent_not_found` | PaymentIntent not found or invalid | 404 | Invalid PaymentIntent ID |
| `stripe_authentication_failed` | Invalid Stripe secret key | 400 | Authentication error |
| `payment_identifier_missing` | Either payment_intent or charge ID is required | 400 | Missing payment reference |
| `invalid_charge_id` | Invalid Charge ID format | 400 | Malformed charge ID |
| `refund_failed` | Refund failed: [Stripe error message] | 400 | Stripe refund error |
| `refund_creation_failed` | Failed to create refund. Please try again. | 500 | General refund error |
| `payment_intent_not_capturable` | PaymentIntent cannot be captured in its current status | 400 | Invalid capture status |
| `payment_intent_capture_failed` | PaymentIntent capture failed: [Stripe error message] | 400 | Stripe capture error |

### Error Response Format

```json
{
    "code": "error_code",
    "message": "Human-readable error message",
    "status": "error"
}
```

### Debugging

Enable error logging by ensuring these settings in index.php:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1); // Disable in production
```

Check server error logs for detailed error information.

## Security Considerations

### Production Setup

1. **Disable error display**:
   ```php
   ini_set('display_errors', 0); // Production
   ```

2. **Use HTTPS**: Always serve the middleware over HTTPS in production

3. **Validate origins**: Consider implementing origin validation for CORS

4. **Rate limiting**: Implement rate limiting to prevent abuse

5. **Input sanitization**: The middleware includes input validation, but additional server-level protections are recommended

### License Key Security

- License keys are transmitted in request bodies (base64 encoded)
- Keys are verified server-side with SureCart API
- Failed verifications fall back to default fee structure

## Integration Examples

### Frontend JavaScript Integration

```javascript
class SureFormsPayment {
    constructor(apiUrl, stripePublishableKey) {
        this.apiUrl = apiUrl;
        this.stripe = Stripe(stripePublishableKey);
    }

    async createPaymentIntent(amount, currency = 'usd', licenseKey = '') {
        const requestData = {
            secret_key: 'sk_test_...', // Should come from secure source
            amount: amount,
            currency: currency,
            license_key: licenseKey,
            description: 'SureForms Payment'
        };

        const response = await fetch(`${this.apiUrl}/payment-intent/create`, {
            method: 'POST',
            body: btoa(JSON.stringify(requestData)),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        return await response.json();
    }

    async processPayment(paymentIntentClientSecret, paymentMethodId) {
        return await this.stripe.confirmCardPayment(
            paymentIntentClientSecret,
            {
                payment_method: paymentMethodId
            }
        );
    }

    async createRefund(paymentIntentId, amount = null, reason = 'requested_by_customer', metadata = {}) {
        const requestData = {
            secret_key: 'sk_test_...', // Should come from secure source
            payment_intent: paymentIntentId,
            reason: reason,
            metadata: {
                source: 'SureForms',
                refunded_at: new Date().toISOString(),
                ...metadata
            }
        };

        // Add amount for partial refund
        if (amount !== null) {
            requestData.amount = amount;
        }

        const response = await fetch(`${this.apiUrl}/refund/create`, {
            method: 'POST',
            body: btoa(JSON.stringify(requestData)),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        return await response.json();
    }

    async capturePaymentIntent(paymentIntentId) {
        const requestData = {
            secret_key: 'sk_test_...', // Should come from secure source
            payment_intent_id: paymentIntentId
        };

        const response = await fetch(`${this.apiUrl}/payment-intent/capture`, {
            method: 'POST',
            body: btoa(JSON.stringify(requestData)),
            headers: {
                'Content-Type': 'application/json'
            }
        });

        return await response.json();
    }
}

// Usage
const payment = new SureFormsPayment(
    'https://your-middleware-url.com',
    'pk_test_your_publishable_key'
);

// Create payment intent
const paymentIntent = await payment.createPaymentIntent(2000, 'usd', 'license-key');

// Process payment
const result = await payment.processPayment(
    paymentIntent.client_secret,
    'pm_card_visa'
);
```

### PHP Integration Example

```php
<?php

class SureFormsPaymentClient {
    private $apiUrl;
    private $secretKey;

    public function __construct($apiUrl, $secretKey) {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->secretKey = $secretKey;
    }

    public function createPaymentIntent($amount, $currency = 'usd', $licenseKey = '') {
        $data = [
            'secret_key' => $this->secretKey,
            'amount' => $amount,
            'currency' => $currency,
            'license_key' => $licenseKey
        ];

        return $this->makeRequest('/payment-intent/create', $data);
    }

    public function createSubscription($customerId, $amount, $interval = 'month', $licenseKey = '') {
        $data = [
            'secret_key' => $this->secretKey,
            'customer' => $customerId,
            'amount' => $amount,
            'interval' => $interval,
            'license_key' => $licenseKey
        ];

        return $this->makeRequest('/subscription/create', $data);
    }

    public function createRefund($paymentIntentId, $amount = null, $reason = 'requested_by_customer', $metadata = []) {
        $data = [
            'secret_key' => $this->secretKey,
            'payment_intent' => $paymentIntentId,
            'reason' => $reason,
            'metadata' => array_merge([
                'source' => 'SureForms',
                'refunded_at' => date('c')
            ], $metadata)
        ];

        if ($amount !== null) {
            $data['amount'] = $amount;
        }

        return $this->makeRequest('/refund/create', $data);
    }

    public function capturePaymentIntent($paymentIntentId) {
        $data = [
            'secret_key' => $this->secretKey,
            'payment_intent_id' => $paymentIntentId
        ];

        return $this->makeRequest('/payment-intent/capture', $data);
    }

    private function makeRequest($endpoint, $data) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => base64_encode(json_encode($data)),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($response, true);
    }
}
```

### WordPress Plugin Integration

```php
<?php

class SureFormsWPPayment {
    private $middleware;

    public function __construct() {
        $this->middleware = new SureFormsPaymentClient(
            get_option('sureforms_middleware_url'),
            get_option('sureforms_stripe_secret_key')
        );
    }

    public function process_form_payment($form_data) {
        $amount = $form_data['amount'] * 100; // Convert to cents
        $license_key = get_option('sureforms_license_key');

        $payment_intent = $this->middleware->createPaymentIntent(
            $amount,
            $form_data['currency'] ?? 'usd',
            $license_key
        );

        if (isset($payment_intent['client_secret'])) {
            return [
                'success' => true,
                'client_secret' => $payment_intent['client_secret']
            ];
        }

        return [
            'success' => false,
            'error' => $payment_intent['message'] ?? 'Payment failed'
        ];
    }
}
```

## Webhook Implementation

### Webhook Handler Example

```php
<?php

class SureFormsWebhookHandler {
    private $endpointSecret;

    public function __construct($endpointSecret) {
        $this->endpointSecret = $endpointSecret;
    }

    public function handleWebhook() {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $this->endpointSecret
            );

            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSuccess($event['data']['object']);
                    break;
                
                case 'charge.failed':
                    $this->handlePaymentFailure($event['data']['object']);
                    break;
                
                case 'charge.dispute.created':
                    $this->handleDispute($event['data']['object']);
                    break;
                    
                default:
                    error_log('Unhandled webhook event: ' . $event['type']);
            }

            http_response_code(200);
            echo json_encode(['status' => 'success']);

        } catch(\UnexpectedValueException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
        }
    }

    private function handlePaymentSuccess($paymentIntent) {
        // Update order status, send confirmation email, etc.
        error_log('Payment succeeded: ' . $paymentIntent['id']);
    }

    private function handlePaymentFailure($charge) {
        // Handle failed payment, notify user, etc.
        error_log('Payment failed: ' . $charge['id']);
    }

    private function handleDispute($dispute) {
        // Handle dispute, notify admin, etc.
        error_log('Dispute created: ' . $dispute['id']);
    }
}

// Usage
$handler = new SureFormsWebhookHandler('whsec_your_webhook_secret');
$handler->handleWebhook();
```

## Testing

### Test Environment Setup

1. **Use Stripe test keys**:
   ```php
   $secretKey = 'sk_test_...'; // Test secret key
   $publishableKey = 'pk_test_...'; // Test publishable key
   ```

2. **Test license keys**: Use test license keys or empty strings for testing

3. **Test payment methods**: Use Stripe's test card numbers

### Test Card Numbers

| Card Number | Description |
|-------------|-------------|
| 4242424242424242 | Visa - Success |
| 4000000000000002 | Visa - Declined |
| 4000000000000341 | Visa - Attach required |
| 4000002500003155 | Visa - Insufficient funds |

### Testing Checklist

- [ ] Payment intent creation with valid data
- [ ] Payment intent creation with invalid data
- [ ] Payment intent retrieval
- [ ] Payment intent updates
- [ ] Payment intent cancellation
- [ ] Payment intent capture (manual capture scenarios)
- [ ] Refund creation (full and partial refunds)
- [ ] Subscription creation and management
- [ ] License key validation and fee calculation
- [ ] Webhook creation and deletion
- [ ] Error handling for various scenarios
- [ ] CORS functionality with frontend
- [ ] Security validation (invalid keys, malformed data)

### Example Test Script

```php
<?php
require_once 'vendor/autoload.php';

// Test configuration
$test_secret_key = 'sk_test_your_test_key';
$middleware_url = 'https://your-middleware-domain.com';

function testPaymentIntentCreation($middleware_url, $secret_key) {
    $data = [
        'secret_key' => $secret_key,
        'amount' => 2000,
        'currency' => 'usd',
        'description' => 'Test Payment',
        'license_key' => '' // Test without license
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $middleware_url . '/payment-intent/create',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => base64_encode(json_encode($data)),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $http_code\n";
    echo "Response: $response\n";
    
    return json_decode($response, true);
}

// Run tests
echo "Testing Payment Intent Creation...\n";
$result = testPaymentIntentCreation($middleware_url, $test_secret_key);

if (isset($result['client_secret'])) {
    echo "✓ Payment Intent created successfully\n";
    echo "Client Secret: " . $result['client_secret'] . "\n";
} else {
    echo "✗ Payment Intent creation failed\n";
    if (isset($result['message'])) {
        echo "Error: " . $result['message'] . "\n";
    }
}
```

## Troubleshooting

### Common Issues

1. **"Stripe library not found" error**
   - Run `composer install` to install dependencies
   - Ensure `vendor/autoload.php` is accessible

2. **"Invalid JSON payment_intent_data" error**
   - Ensure request body is base64-encoded JSON
   - Check JSON syntax and structure

3. **CORS errors in browser**
   - Verify CORS headers are being sent
   - Check if middleware URL is accessible from frontend domain

4. **License verification failures**
   - Check SureCart secret key configuration
   - Verify license key format and validity
   - Check network connectivity to SureCart API

5. **Application fees not calculating correctly**
   - Verify product ID mappings in configuration
   - Check license verification response in error logs
   - Ensure fee percentages are configured correctly

### Debug Mode

Enable detailed logging by adding debug statements:

```php
// Add after license verification
error_log('License verification response: ' . print_r($response, true));
error_log('Calculated fee percentage: ' . $application_fee_percentage);
error_log('Fee amount in cents: ' . $application_fee_amount);
```

### Log Analysis

Monitor server error logs for:
- Stripe API errors
- License verification failures
- Request validation errors
- cURL connection issues

## Conclusion

The SureForms Payment Middleware provides a robust foundation for handling payments with license-based fee structures. Follow this documentation to integrate the middleware into your applications and customize it for your specific requirements.

For additional support or questions, refer to the Stripe API documentation and SureCart API documentation for their respective services.