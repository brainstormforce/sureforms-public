# AI Form Builder

SureForms includes an AI-powered form generator that creates complete forms from natural language descriptions.

## Architecture

```
inc/ai-form-builder/
|-- ai-form-builder.php    # Main AI builder class
|-- ai-auth.php            # Authentication and billing
|-- ai-helper.php          # AI utility functions
|-- field-mapping.php      # Maps AI response to Gutenberg blocks
```

## Authentication Flow

The `AI_Auth` class handles authentication with the SureForms middleware API:

1. User initiates authentication from admin settings
2. `GET /initiate-auth` REST endpoint generates an auth URL
3. User is redirected to the billing portal (`SRFM_BILLING_PORTAL`)
4. After authentication, an encrypted access key is returned
5. `POST /handle-access-key` decrypts and stores the key in WordPress options
6. Subsequent AI requests use this key for authorization

### Constants

| Constant | URL | Purpose |
|----------|-----|---------|
| `SRFM_AI_MIDDLEWARE` | `https://credits.startertemplates.com/sureforms/` | AI credits API |
| `SRFM_MIDDLEWARE_BASE_URL` | `https://api.sureforms.com/` | API middleware |
| `SRFM_BILLING_PORTAL` | `https://billing.sureforms.com/` | Billing portal |

## Form Generation Process

### Step 1: User Input

The user provides a natural language description of the form they want (e.g., "Create a contact form with name, email, phone, and message fields").

### Step 2: AI Generation

`POST /generate-form` sends the description to the AI middleware:

```
User prompt -> REST API -> AI_Form_Builder::generate_ai_form()
  -> External AI middleware API call
  -> AI generates form structure as JSON
  -> Returns field definitions
```

### Step 3: Field Mapping

`POST /map-fields` converts the AI response to SureForms Gutenberg blocks:

```
AI JSON response -> Field_Mapping::generate_gutenberg_fields_from_questions()
  -> Maps each AI field to a SureForms block type
  -> Generates block attributes
  -> Creates Gutenberg block markup
  -> Returns blocks ready for the editor
```

### Field Type Mapping

The `Field_Mapping` class maps AI-suggested field types to SureForms blocks:

| AI Field Type | SureForms Block |
|---------------|----------------|
| text / name | `srfm/input` |
| email | `srfm/email` |
| phone / tel | `srfm/phone` |
| textarea / message | `srfm/textarea` |
| number | `srfm/number` |
| url / website | `srfm/url` |
| select / dropdown | `srfm/dropdown` |
| checkbox | `srfm/checkbox` |
| radio / multiple choice | `srfm/multichoice` |
| address | `srfm/address` |

## REST API Endpoints

| Method | Endpoint | Handler | Description |
|--------|----------|---------|-------------|
| POST | `/generate-form` | `AI_Form_Builder::generate_ai_form` | Send prompt to AI, get form structure |
| POST | `/map-fields` | `Field_Mapping::generate_gutenberg_fields_from_questions` | Convert AI fields to blocks |
| GET | `/initiate-auth` | `AI_Auth::get_auth_url` | Get billing portal auth URL |
| POST | `/handle-access-key` | `AI_Auth::handle_access_key` | Save decrypted access key |

All endpoints require `manage_options` capability.

## AI Helper

The `AI_Helper` class provides utility functions for:
- API key management
- Credit balance checking
- Request formatting
- Error handling for AI middleware responses

## Related Pages

- [REST API Reference](REST-API-Reference) -- All API endpoints
- [Gutenberg Blocks](Gutenberg-Blocks) -- Block types the AI maps to
- [Architecture Overview](Architecture-Overview) -- AI classes in bootstrap flow
