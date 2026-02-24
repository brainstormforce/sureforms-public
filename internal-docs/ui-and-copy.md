# UI & Copy Guidelines

**Version:** 2.5.0

---

## User Experience Philosophy

**Principle:** Every word and interaction should make the user feel capable, not confused.

**User Mindset:**
- "I just want to create a form quickly"
- "I don't have time to read documentation"
- "I'm not a developer"

**Our Responsibility:**
- Make obvious what to do next
- Explain why, not just how
- Never blame the user

---

## User Journeys

### Journey 1: First-Time User Creates Contact Form

**Goal:** Create and publish first form in < 5 minutes

**Steps:**

**1. Plugin Activation (30 seconds)**

```
User activates plugin
  ↓
Redirect to welcome screen
  ↓
Show 2 options:
  [Create with AI] [Start from Blank]
```

**Copy:**
```
Welcome to SureForms!

Let's create your first form.

[Create with AI] ← Recommended for beginners
  Let AI build your form from a simple description

[Start from Blank]
  Build your form block-by-block
```

**Design notes:**
- Large, friendly buttons
- "Recommended" badge on AI option
- No walls of text

---

**2. AI Form Creation (2 minutes)**

```
User clicks "Create with AI"
  ↓
Modal appears with text input
  ↓
User types: "simple contact form"
  ↓
AI generates form (3-5 seconds)
  ↓
Form opens in editor, ready to publish
```

**Copy:**

```
Create Form with AI

Describe the form you need:
┌─────────────────────────────────────┐
│ Example: "job application with     │
│ file upload" or "event RSVP"       │
└─────────────────────────────────────┘

[Cancel]  [Generate Form →]
```

**Loading state:**
```
✨ Creating your form...

AI is adding fields based on your description.
This usually takes 3-5 seconds.
```

**Success state:**
```
✅ Your form is ready!

We added:
• Name field
• Email field
• Message field
• Submit button

You can add, remove, or rearrange fields below.

[Publish Form]
```

**Design notes:**
- Loading spinner + encouraging message
- Success message lists what was created
- Clear next action (Publish)

---

**3. Form Publishing (1 minute)**

```
User clicks "Publish"
  ↓
Gutenberg publish panel opens
  ↓
SureForms shows additional options
```

**Copy in publish panel:**

```
📋 Form Settings

Where should submissions be sent?

Email: [admin@yoursite.com ▼]

After submission, show:
○ Confirmation message
○ Redirect to page
● Confirmation message (selected by default)

Message:
┌─────────────────────────────────────┐
│ Thank you! We'll respond within     │
│ 24 hours.                           │
└─────────────────────────────────────┘

[Publish Form]
```

**Design notes:**
- Sensible defaults (confirmation message, admin email)
- Inline editing (no separate settings page)
- Jargon-free labels

---

**4. Embedding Form (30 seconds)**

```
User wants to add form to page
  ↓
Edit page in Gutenberg
  ↓
Add SureForms block
  ↓
Select form from dropdown
```

**Copy in block inserter:**

```
SureForms

Display a form on your page.

📝 No form yet? Create one in SureForms > Add New
```

**Copy in block settings:**

```
Form

Select a form:
[Contact Form ▼]  [Create New]

Display settings:
☑ Show form title
☑ Show form description
☐ Hide labels (show placeholders only)
```

**Design notes:**
- Help text guides to form creation if needed
- Checkbox labels explain what they do
- Preview updates in real-time

---

### Journey 2: Pro User Sets Up Payment Form

**Goal:** Create payment form and collect first payment

**Complexity:** Higher (involves Stripe setup)

**Steps:**

**1. Payment Gateway Setup (5 minutes)**

```
User navigates to:
SureForms → Settings → Payments → Stripe
```

**Copy:**

```
Stripe Payments

Accept credit card payments with Stripe.

🔒 Secure: We never store card details. Payments are processed directly by Stripe.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Test Mode (for testing)

☑ Enable test mode

Use these while testing:
  Test card: 4242 4242 4242 4242
  Any future expiry, any CVC

Publishable Key (starts with pk_test_)
┌─────────────────────────────────────┐
│                                     │
└─────────────────────────────────────┘

Secret Key (starts with sk_test_)
┌─────────────────────────────────────┐
│ ••••••••••••••••••••••••••••••••   │
└─────────────────────────────────────┘

Where to get keys:
→ Stripe Dashboard > Developers > API Keys
  https://dashboard.stripe.com/apikeys

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Live Mode (for real payments)

⚠️ Only use after testing

Publishable Key (starts with pk_live_)
┌─────────────────────────────────────┐
│                                     │
└─────────────────────────────────────┘

Secret Key (starts with sk_live_)
┌─────────────────────────────────────┐
│                                     │
└─────────────────────────────────────┘

[Save Settings]
```

**Design notes:**
- Test mode emphasized first
- Clear instructions where to get keys
- Security reassurance ("We never store")
- Direct link to Stripe dashboard

---

**2. Creating Payment Form (3 minutes)**

```
User creates new form
  ↓
Adds "Payment" block
  ↓
Configures amount and currency
```

**Copy in Payment block settings:**

```
Payment Details

Amount
○ Fixed amount
  ┌──────┐
  │ 50   │ USD ▼
  └──────┘

● Let user choose amount
  Min: [10] Max: [1000] USD ▼
  Default: [50]

Payment Type
● One-time payment
○ Subscription
  Interval: [Monthly ▼]

Button Text
┌─────────────────────────────────────┐
│ Pay Now                             │
└─────────────────────────────────────┘
```

**Design notes:**
- Radio buttons for mutually exclusive options
- Currency dropdown next to amount
- Clear labels (no "recurring" jargon, use "subscription")

---

**3. Testing Payment (2 minutes)**

**Copy on frontend form:**

```
Payment Information

Amount: $50.00

Card Number
┌─────────────────────────────────────┐
│ 1234 5678 9012 3456                 │ [Card icon]
└─────────────────────────────────────┘

Expiry          CVC
┌──────────┐    ┌─────┐
│ MM / YY  │    │ 123 │
└──────────┘    └─────┘

🔒 Secure payment powered by Stripe
   Your card details are encrypted and never stored.

[Pay $50.00]
```

**Success message after payment:**

```
✅ Payment Successful!

Receipt sent to: john@example.com

Transaction ID: ch_1A2B3C4D5E6F

[View Receipt] [Back to Home]
```

**Error message if payment fails:**

```
❌ Payment Failed

Your card was declined.

Common reasons:
• Insufficient funds
• Incorrect card number or expiry date
• Card requires 3D Secure verification

Please try again or use a different card.

[Try Again]
```

**Design notes:**
- Security reassurance prominent
- Success message includes receipt email and transaction ID
- Error message explains why and how to fix

---

### Journey 3: Power User Builds Conditional Logic Form (Pro)

**Goal:** Show/hide fields based on user selection

**Example:** Event RSVP with dietary restrictions (only show if attending)

**Steps:**

**1. Creating Base Form (2 minutes)**

```
User adds fields:
  - "Will you attend?" (Multiple Choice: Yes/No)
  - "Dietary restrictions" (Dropdown)
```

**2. Adding Conditional Logic (3 minutes)**

```
User clicks "Dietary restrictions" block
  ↓
Opens block settings panel
  ↓
Enables conditional logic
```

**Copy in block settings:**

```
Conditional Logic

Show this field only when certain conditions are met.

☑ Enable conditional logic

Show this field when:

[Will you attend? ▼] [is ▼] [Yes ▼]

[+ Add Condition]

Logic:
● Show if ALL conditions match (AND)
○ Show if ANY condition matches (OR)
```

**Design notes:**
- Toggle to enable
- Dropdown-based condition builder (no code)
- Visual feedback: field grays out in editor preview when hidden

---

**3. Testing Logic (1 minute)**

**Copy in editor preview mode:**

```
💡 Preview Mode

This is how your form will appear to users.

Try selecting different options to see conditional logic in action.

[Exit Preview]
```

**Behavior:**
- User selects "Yes" → Dietary field appears
- User selects "No" → Dietary field disappears

**Design notes:**
- Clear indication of preview mode
- Real-time updates (no reload)

---

## Microcopy Guidelines

### Field Labels

**Be concise and conversational**

❌ Bad: "Please enter your electronic mail address"
✅ Good: "Email"

❌ Bad: "Input telephone number (optional)"
✅ Good: "Phone (optional)"

**Use sentence case, not title case**

❌ Bad: "First Name"
✅ Good: "First name"

❌ Bad: "Company Name (If Applicable)"
✅ Good: "Company name (if applicable)"

---

### Help Text

**Explain why or provide examples**

**Email field:**
❌ Bad: "Enter email"
✅ Good: "We'll send a confirmation to this email"

**Phone field:**
❌ Bad: "Phone number"
✅ Good: "We'll only call if there's an issue with your order"

**File upload:**
❌ Bad: "Upload file"
✅ Good: "Upload your resume (PDF or DOCX, max 10MB)"

---

### Error Messages

**Be specific and actionable**

**Email validation:**
❌ Bad: "Invalid email"
✅ Good: "Please enter a valid email (e.g., name@example.com)"

**Required field:**
❌ Bad: "This field is required"
✅ Good: "Please enter your name"

**File size:**
❌ Bad: "File too large"
✅ Good: "File must be under 10MB. Yours is 15MB. Try compressing it."

**Payment failed:**
❌ Bad: "Transaction error"
✅ Good: "Your card was declined. Please check your card details or try a different card."

---

### Success Messages

**Be enthusiastic but not over-the-top**

**Form submission:**
❌ Bad: "Form submitted"
✅ Good: "Thanks! We'll get back to you within 24 hours."

**Payment successful:**
❌ Bad: "Payment processed successfully. Transaction ID: 1234."
✅ Good: "✅ Payment received! Receipt sent to your email."

**Registration:**
❌ Bad: "Account created. Please log in."
✅ Good: "Welcome! Check your email to verify your account."

---

### Button Labels

**Use verbs that describe the action**

❌ Bad: "Submit"
✅ Good: "Send Message"

❌ Bad: "Click Here"
✅ Good: "Download Receipt"

❌ Bad: "Proceed"
✅ Good: "Continue to Payment"

**For payment buttons, include amount**

❌ Bad: "Pay"
✅ Good: "Pay $50.00"

❌ Bad: "Subscribe"
✅ Good: "Subscribe for $9.99/month"

---

### Settings & Options

**Explain consequences, not just features**

**Email notification:**
❌ Bad: "Send email notification"
✅ Good: "Email me when someone submits this form"

**Required field:**
❌ Bad: "Required"
✅ Good: "Make this field required" (checkbox label)

**Conditional logic:**
❌ Bad: "Enable conditional logic"
✅ Good: "Show or hide this field based on other answers"

---

## Visual Design Patterns

### Empty States

**When user has no forms yet:**

```
┌─────────────────────────────────────────┐
│                                         │
│         📝                              │
│                                         │
│    No forms yet                         │
│                                         │
│    Forms help you collect information  │
│    from your website visitors.          │
│                                         │
│    [Create Your First Form]             │
│                                         │
└─────────────────────────────────────────┘
```

**Copy principles:**
- Icon relevant to context
- 1-sentence explanation
- Clear call-to-action button

---

**When form has no submissions:**

```
┌─────────────────────────────────────────┐
│                                         │
│         📭                              │
│                                         │
│    No submissions yet                   │
│                                         │
│    Share this form to start collecting │
│    responses.                           │
│                                         │
│    [Copy Form Link] [Embed on Page]    │
│                                         │
└─────────────────────────────────────────┘
```

**Design notes:**
- Actionable next steps
- Multiple options (link vs embed)

---

### Loading States

**Form submission in progress:**

```
┌─────────────────────────────────────┐
│   ⏳ Sending...                     │
│                                     │
│   Please don't close this page.    │
└─────────────────────────────────────┘
```

**AI form generation:**

```
┌─────────────────────────────────────┐
│   ✨ Creating your form...          │
│                                     │
│   [████████░░] 80%                  │
│                                     │
│   Adding email validation...        │
└─────────────────────────────────────┘
```

**Design notes:**
- Spinner or progress indicator
- Explain what's happening
- Show progress if possible

---

### Confirmation Dialogs

**Deleting a form:**

```
┌─────────────────────────────────────┐
│  Delete "Contact Form"?             │
│                                     │
│  This will permanently delete:      │
│  • The form                         │
│  • 47 submissions                   │
│  • All settings                     │
│                                     │
│  This cannot be undone.             │
│                                     │
│  [Cancel]  [Delete Form]            │
└─────────────────────────────────────┘
```

**Design notes:**
- Specific about what will be deleted
- Use red/destructive style for delete button
- Cancel button should be default (easier to accidentally click)

---

### Tooltips & Hints

**When to use:**
- Explaining technical terms
- Providing examples
- Showing keyboard shortcuts

**Format:**

```
Field Name [?]
  ↓ (on hover)
┌─────────────────────────────────────┐
│ This is the internal name used in   │
│ the database.                       │
│                                     │
│ Example: "first_name"               │
└─────────────────────────────────────┘
```

**Guidelines:**
- Keep under 2 sentences
- Provide example if helpful
- Don't repeat the label

---

## Accessibility

### Screen Reader Text

**Form structure:**

```html
<form aria-label="Contact Form">
  <div role="group" aria-labelledby="personal-info">
    <h2 id="personal-info">Personal Information</h2>
    <!-- Fields here -->
  </div>
</form>
```

**Error announcements:**

```html
<div role="alert" aria-live="polite">
  Please fix 2 errors before submitting.
</div>

<input
  aria-invalid="true"
  aria-describedby="email-error"
/>
<span id="email-error" role="alert">
  Please enter a valid email address
</span>
```

---

### Keyboard Navigation

**Required interactions:**
- Tab through all fields
- Space to toggle checkboxes/radio buttons
- Enter to submit form
- Escape to close modals

**Visual focus indicators:**

```css
.srfm-field input:focus {
  outline: 2px solid #0073aa;
  outline-offset: 2px;
}
```

**Never:**
```css
:focus {
  outline: none; /* ❌ Never remove focus outline */
}
```

---

### Color Contrast

**WCAG 2.1 Level AA Requirements:**
- Normal text: 4.5:1 minimum
- Large text (18pt+): 3:1 minimum
- UI components: 3:1 minimum

**Test tools:**
- WebAIM Contrast Checker
- Chrome DevTools (Lighthouse)

**Safe color combinations:**
- White text on dark backgrounds (#333 or darker)
- Dark text on light backgrounds (#F0F0F0 or lighter)
- Avoid gray text on gray backgrounds

---

## Tone & Voice

### Voice Characteristics

**Friendly but professional**
- Use "we" and "you"
- Conversational, not formal
- Helpful, not condescending

**Example:**
❌ Formal: "The system has detected an error in your input."
✅ Friendly: "Oops! We couldn't save that. Check for any errors above."

---

**Clear over clever**
- Avoid puns and wordplay
- Be direct
- Technical accuracy over marketing fluff

**Example:**
❌ Clever: "Houston, we have a problem!"
✅ Clear: "Something went wrong. Please try again."

---

**Empowering, not blaming**
- Focus on solutions, not problems
- Use "we" for errors, "you" for successes

**Example:**
❌ Blaming: "You entered an invalid email."
✅ Empowering: "Hmm, that email doesn't look quite right. Mind double-checking it?"

---

### Writing for Different Contexts

**First-time users:**
- More explanation
- Examples provided
- Encouraging tone

**Example:**
```
Welcome to SureForms!

Creating your first form is easy. We'll walk you through each step.

Let's start by choosing what kind of form you need.
```

---

**Power users:**
- Less explanation
- Assume knowledge
- Efficiency-focused

**Example:**
```
Advanced Settings

Show field if: [condition builder]
Custom CSS class: [input]
```

---

**Error states:**
- Apologetic but not dramatic
- Specific about what went wrong
- Clear next steps

**Example:**
```
We couldn't save your changes.

The connection to the server timed out.

Please try again. If this keeps happening, check your internet connection.

[Try Again]
```

---

**Success states:**
- Positive reinforcement
- What happens next
- Optional next action

**Example:**
```
✅ Form published!

Your form is now live at:
https://yoursite.com/contact

[View Form] [Create Another]
```

---

## Form Field Best Practices

### Required vs Optional

**Default:** Make fields optional

**Require only when absolutely necessary:**
- Name (if sending personalized response)
- Email (if you need to contact them)
- Payment details (if collecting payment)

**Label optional fields:**
```
Phone (optional)
Company name (optional)
```

**Don't label required fields:**
❌ Bad: "Email (required)"
✅ Good: "Email" (with * indicator)

---

### Field Order

**Logical flow:**
1. Personal info (Name, Email)
2. Specific questions
3. Payment details (if applicable)
4. Submit button

**Example contact form:**
```
1. Name
2. Email
3. Subject
4. Message
5. [Submit]
```

**Example order form:**
```
1. Name
2. Email
3. Product selection
4. Quantity
5. Payment details
6. [Complete Purchase]
```

---

### Placeholder Text

**Use sparingly**

Only for examples, not as labels:

❌ Bad:
```
Label: [empty]
Placeholder: "Enter your email"
```

✅ Good:
```
Label: Email
Placeholder: "name@example.com"
```

**Never use placeholders as only label** (accessibility issue)

---

### Multi-Step Forms (Pro)

**Step indicators:**

```
Step 1 of 3: Your Information
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
●━━━━━━━━━━━━○━━━━━━━━━━━━━○

[Fields here]

[Continue to Step 2 →]
```

**Navigation:**
- Show progress (Step 1 of 3)
- Visual progress bar
- Allow back navigation (not just forward)

**Button labels:**
- Step 1: "Continue" (not "Next")
- Step 2: "Continue"
- Step 3: "Submit Form" (or "Complete Order")

---

## Internationalization (i18n)

### Text Translation

**All user-facing strings must be translatable:**

```php
// ✅ Good
__('Email', 'sureforms');
_e('Submit Form', 'sureforms');

// ❌ Bad
echo 'Email'; // Hardcoded English
```

**Placeholders for dynamic content:**

```php
// ✅ Good
sprintf(__('You have %d new submissions', 'sureforms'), $count);

// ❌ Bad
echo "You have $count new submissions";
```

---

### Date & Number Formatting

**Use WordPress functions:**

```php
// Dates
echo date_i18n(get_option('date_format'), $timestamp);

// Numbers
echo number_format_i18n($number);

// Currency
echo '$' . number_format_i18n($amount, 2);
```

---

### RTL Support

**CSS for right-to-left languages:**

```css
/* Use logical properties */
.srfm-field {
  margin-inline-start: 10px; /* Not margin-left */
  padding-inline-end: 20px;  /* Not padding-right */
}
```

**Test with RTL languages:**
- Arabic
- Hebrew
- Persian

---

## Quality Checklist

Before shipping UI copy:

- [ ] Spell check passed
- [ ] Grammar correct
- [ ] Tone matches guidelines
- [ ] Actionable error messages
- [ ] Examples provided where helpful
- [ ] Accessibility: screen reader friendly
- [ ] Accessibility: color contrast ≥ 4.5:1
- [ ] Keyboard navigation works
- [ ] i18n: All strings translatable
- [ ] RTL languages supported
- [ ] Tested with real users (if major UI)

---

**Next:** [FAQ](faq.md)
