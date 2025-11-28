# Flutterwave Payment Integration - FindAJob Nigeria

## 🚀 Setup Complete!

The Flutterwave payment gateway has been integrated into the FindAJob platform.

---

## 📋 What's Been Created

### 1. **Configuration File**
- `config/flutterwave.php` - Flutterwave API configuration and helper functions

### 2. **Database**
- ✅ `transactions` table updated with Flutterwave fields:
  - `tx_ref` - FindAJob transaction reference
  - `flw_ref` - Flutterwave reference
  - `transaction_id` - Flutterwave transaction ID
  - `customer_email`, `customer_name`, `customer_phone`
  - `metadata` - Additional transaction data (JSON)
  - `flw_response` - Complete Flutterwave API response (JSON)
  - `verified_at` - Payment verification timestamp

### 3. **API Endpoints**
- `api/payment.php` - Main payment API
  - `initialize_payment` - Start a new payment
  - `verify_payment` - Verify payment status
  - `get_transaction` - Get transaction details
  - `get_user_transactions` - List user transactions

- `api/payment-callback.php` - Handles redirect after payment
- `api/flutterwave-webhook.php` - Receives Flutterwave webhooks

### 4. **User Pages**
- `pages/payment/checkout.php` - Payment selection page
- `pages/payment/verify.php` - Payment verification page

---

## 🔧 Configuration Steps

### Step 1: Get Flutterwave API Keys

1. Go to [Flutterwave Dashboard](https://dashboard.flutterwave.com)
2. Sign up or login
3. Go to Settings → API Keys
4. Copy your keys:
   - Public Key
   - Secret Key
   - Encryption Key

### Step 2: Update Configuration

Edit `config/flutterwave.php`:

```php
// Replace these with your actual keys
define('FLUTTERWAVE_PUBLIC_KEY', 'FLWPUBK_TEST-your-public-key-here');
define('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST-your-secret-key-here');
define('FLUTTERWAVE_ENCRYPTION_KEY', 'FLWSECK_TEST-your-encryption-key-here');

// Change to 'live' for production
define('FLUTTERWAVE_ENVIRONMENT', 'test'); // 'test' or 'live'
```

### Step 3: Configure Webhook (Important!)

1. In Flutterwave Dashboard, go to Settings → Webhooks
2. Add webhook URL: `https://yourdomain.com/api/flutterwave-webhook.php`
3. Save your Secret Hash (already configured in the code)

### Step 4: Update Redirect URLs

In `config/flutterwave.php`, update:

```php
define('FLUTTERWAVE_REDIRECT_URL', 'https://yourdomain.com/pages/payment/verify.php');
define('FLUTTERWAVE_CALLBACK_URL', 'https://yourdomain.com/api/payment-callback.php');
```

---

## 💰 Payment Types Supported

1. **Job Posting** (`job_posting`) - ₦5,000
2. **Featured Listing** (`featured_listing`) - ₦10,000
3. **CV Service** (`cv_service`) - ₦15,000
4. **Premium Subscription** (`subscription`) - ₦20,000
5. **NIN Verification** (`nin_verification`)
6. **Job Booster** (`job_booster`)

---

## 🎯 How to Use

### Initialize Payment (JavaScript)

```javascript
async function makePayment() {
    const formData = new FormData();
    formData.append('action', 'initialize_payment');
    formData.append('amount', 5000);
    formData.append('service_type', 'job_posting');
    formData.append('description', 'Payment for job posting');

    const response = await fetch('/api/payment.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();

    if (data.success) {
        // Redirect to Flutterwave payment page
        window.location.href = data.data.payment_link;
    }
}
```

### Verify Payment (After Redirect)

```javascript
async function verifyPayment(txRef, transactionId) {
    const response = await fetch(
        `/api/payment.php?action=verify_payment&tx_ref=${txRef}&transaction_id=${transactionId}`
    );

    const data = await response.json();

    if (data.success) {
        console.log('Payment verified!', data.data);
    }
}
```

---

## 🔄 Payment Flow

1. **User clicks "Pay Now"**
   - Frontend calls `api/payment.php?action=initialize_payment`
   - Record created in `transactions` table with status 'pending'
   - Flutterwave payment link generated

2. **User redirects to Flutterwave**
   - User enters card details on Flutterwave's secure page
   - Payment processed

3. **Redirect back to FindAJob**
   - Flutterwave redirects to `pages/payment/verify.php`
   - Frontend calls `api/payment.php?action=verify_payment`
   - Payment status verified with Flutterwave API
   - Transaction updated to 'successful' or 'failed'

4. **Webhook confirmation (backup)**
   - Flutterwave sends webhook to `api/flutterwave-webhook.php`
   - Payment status double-checked and updated

---

## 🛠️ Testing

### Test Cards (Flutterwave Test Mode)

**Successful Payment:**
- Card: `5531 8866 5214 2950`
- CVV: `564`
- Expiry: Any future date
- PIN: `3310`
- OTP: `12345`

**Failed Payment:**
- Card: `5143 0100 0000 0003`

### Test the Integration

1. Visit: `http://localhost/findajob/pages/payment/checkout.php`
2. Click "Pay Now" on any service
3. Use test card details
4. Complete payment
5. You'll be redirected to verification page

---

## 📊 Admin Transaction Management

Admins can view transactions at:
- `admin/transactions.php` (to be created)

Query transactions:
```sql
SELECT * FROM transactions 
WHERE status = 'successful' 
ORDER BY created_at DESC;
```

---

## 🔒 Security Features

✅ Webhook signature verification
✅ Amount verification before confirmation
✅ Transaction reference uniqueness
✅ User authentication required
✅ SQL injection protection (prepared statements)
✅ XSS protection (htmlspecialchars)

---

## 🎨 Customization

### Add New Payment Type

1. Add to enum in `transactions` table:
```sql
ALTER TABLE transactions 
MODIFY COLUMN service_type ENUM('job_posting', 'new_service', ...);
```

2. Add pricing in `pages/payment/checkout.php`:
```php
'new_service' => [
    'name' => 'New Service',
    'price' => 25000,
    'description' => 'Description'
]
```

3. Add processing logic in `processPaymentService()` function

---

## 📞 Support

- **Flutterwave Docs**: https://developer.flutterwave.com/docs
- **Test Dashboard**: https://dashboard.flutterwave.com/dashboard/test
- **Live Dashboard**: https://dashboard.flutterwave.com/dashboard/live

---

## ✅ Next Steps

1. Get Flutterwave API keys
2. Update `config/flutterwave.php` with keys
3. Test payment with test card
4. Configure webhook URL
5. Switch to live mode when ready
6. Create admin transactions page

**Payment system is ready to use!** 🎉
