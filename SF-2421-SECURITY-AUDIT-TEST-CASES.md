# SF-2421 Security Audit - Manual Test Cases

## Overview
This document outlines comprehensive test cases for the SF-2421 security audit changes in both SureForms Free and SureForms Pro. The changes focus on security improvements through proper output escaping and template structure modernization.

## 🎯 Testing Objectives
- Verify all form functionality remains intact after security improvements
- Ensure proper HTML escaping prevents XSS vulnerabilities  
- Confirm template output is visually identical to previous version
- Test edge cases that might expose the identified potential issues

---

## 📋 Test Environment Setup

### Prerequisites
- WordPress 6.4+ environment
- PHP 8.1+ (to test strict mode compatibility)
- Both SureForms Free and Pro plugins active
- Browser developer tools for HTML inspection
- Forms with various field types and configurations

### Test Data Required
- Forms with special characters in labels/placeholders (HTML entities, quotes, etc.)
- Long text strings for testing overflow scenarios
- International characters and Unicode content
- Empty/null values for edge case testing

---

## 🔍 Core Functionality Tests

### 1. Form Field Rendering Tests

#### 1.1 Basic Field Rendering
**Objective:** Ensure all form fields render correctly with new template structure

**Test Steps:**
1. Create a form with all available field types:
   - Text Input
   - Email
   - Textarea
   - Select/Dropdown
   - Radio Buttons
   - Checkboxes
   - Number
   - URL
   - Phone
   - Date Picker (Pro)
   - Time Picker (Pro)
   - Rating (Pro)
   - Password (Pro)

2. Add the form to a page/post
3. View the form on frontend
4. Inspect HTML source for each field

**Expected Results:**
- All fields render visually identical to previous version
- HTML structure remains the same
- No broken layouts or missing elements
- All CSS classes and IDs are preserved

**Priority:** ⭐⭐⭐⭐⭐ (Critical)

#### 1.2 Field Labels and Help Text
**Objective:** Verify proper escaping of labels and help text

**Test Steps:**
1. Create fields with labels containing:
   - HTML entities: `&lt;script&gt;alert('test')&lt;/script&gt;`
   - Special characters: `"Label with quotes" & ampersands`
   - Unicode characters: `测试标签 العربية 🎉`
   - Very long text (500+ characters)

2. Add help text with similar content variations
3. Set some fields as required
4. View form on frontend and inspect HTML

**Expected Results:**
- Labels display correctly without executing any scripts
- Help text renders properly with HTML entities escaped
- Required asterisks (*) appear correctly
- No JavaScript errors in console
- HTML source shows properly escaped attributes

**Priority:** ⭐⭐⭐⭐⭐ (Critical)

#### 1.3 Placeholder Text Security
**Objective:** Test placeholder attribute escaping (Free: `inc/fields/base.php:413-414`, Pro: `inc/fields/base.php:337,436`)

**Test Steps:**
1. Create text fields with placeholder text containing:
   - Double quotes: `Enter "quoted text" here`
   - Single quotes: `Don't forget this`
   - HTML tags: `<script>alert('xss')</script>`
   - Special characters: `& < > " ' /`

2. View form on frontend
3. Inspect HTML source for placeholder attributes

**Expected Results:**
- All placeholder text displays correctly in the input field
- HTML source shows properly escaped placeholder attributes
- No XSS vulnerabilities when viewing page source
- Special characters are properly encoded

**Priority:** ⭐⭐⭐⭐⭐ (Critical)

### 2. Admin Interface Tests

#### 2.1 Form Management Pages
**Objective:** Test admin page rendering improvements

**Test Steps:**
1. Navigate to Forms → All Forms (when no forms exist)
2. Navigate to Forms → Add New Form
3. Navigate to Forms → Settings
4. Check SureMail page (if available)

**Expected Results:**
- All admin pages load without errors
- Blank state pages display correctly
- Import/Export buttons work properly
- No visual layout issues

**Priority:** ⭐⭐⭐⭐ (High)

#### 2.2 Entry Management (Pro)
**Objective:** Test entries list and management functionality

**Test Steps:**
1. Create forms and generate test entries
2. Navigate to form entries list
3. Test bulk actions (checkboxes)
4. Edit entries and test log generation
5. Test email notification resend functionality

**Expected Results:**
- Entry checkboxes function correctly
- Entry editing logs display properly formatted
- Email notification messages show correctly
- No HTML rendering issues in entry logs

**Priority:** ⭐⭐⭐⭐ (High)

### 3. Form Submission Tests

#### 3.1 Email Header Security
**Objective:** Test email header escaping (`inc/form-submit.php:730-732`)

**Test Steps:**
1. Create form with email notifications
2. Configure email settings with special characters:
   - CC: `test@example.com, "User Name" <user@test.com>`
   - BCC: `admin@site.com`
   - Reply-To: `noreply@example.com`

3. Submit form and check email headers
4. Test with smart tags in email fields

**Expected Results:**
- Emails are sent successfully
- Email headers are properly formatted
- No injection vulnerabilities in headers
- Smart tags process correctly

**Priority:** ⭐⭐⭐⭐ (High)

#### 3.2 Form Confirmation Messages
**Objective:** Test confirmation message rendering

**Test Steps:**
1. Create forms with custom confirmation messages containing:
   - HTML content (images, links, formatting)
   - Special characters and entities
   - Smart tags

2. Submit forms and verify confirmation display

**Expected Results:**
- Confirmation messages display correctly
- HTML content renders properly
- Images and links work as expected
- No XSS vulnerabilities

**Priority:** ⭐⭐⭐ (Medium)

---

## 🔍 Pro-Specific Tests

### 4. Page Break Functionality (Pro)
**Objective:** Test page break template improvements (`inc/extensions/page-break.php`)

**Test Steps:**
1. Create multi-step form with page breaks
2. Add page break labels with special characters
3. Configure different progress indicator types
4. Test form navigation between pages

**Expected Results:**
- Page breaks render correctly
- Progress indicators display properly
- Navigation between pages works smoothly
- Page break labels show correctly escaped

**Priority:** ⭐⭐⭐⭐ (High)

### 5. PDF Generation (Pro)
**Objective:** Test PDF functionality after template changes

**Test Steps:**
1. Create form with PDF generation enabled
2. Submit form with various field types
3. Generate PDF and verify content
4. Test PDF with special characters

**Expected Results:**
- PDF generates successfully
- All form data appears correctly in PDF
- Special characters render properly
- No generation errors

**Priority:** ⭐⭐⭐ (Medium)

### 6. User Registration (Pro)
**Objective:** Test user registration field rendering

**Test Steps:**
1. Create form with password fields
2. Configure user registration settings
3. Test form submission and user creation

**Expected Results:**
- Password fields render correctly
- User registration works as expected
- No security issues with password field HTML

**Priority:** ⭐⭐⭐ (Medium)

---

## ⚠️ Edge Case and Vulnerability Tests

### 7. XSS Prevention Tests
**Objective:** Verify XSS vulnerabilities are properly mitigated

**Test Steps:**
1. Attempt to inject scripts through:
   - Form field labels
   - Help text
   - Placeholder text
   - Form names
   - Option values

2. Common XSS payloads to test:
   ```
   <script>alert('XSS')</script>
   javascript:alert('XSS')
   <img src=x onerror=alert('XSS')>
   "><script>alert('XSS')</script>
   ```

**Expected Results:**
- No script execution occurs
- All content is properly escaped
- HTML entities are correctly encoded
- No console errors or warnings

**Priority:** ⭐⭐⭐⭐⭐ (Critical)

### 8. PHP Error Testing
**Objective:** Test the identified potential issues in `inc/helper.php`

**Test Steps:**
1. Enable PHP error reporting (E_ALL)
2. Test scenarios that might trigger undefined variables:
   - Empty forms with no fields
   - Forms with invalid field configurations
   - Edge cases where `ob_get_clean()` might fail

3. Monitor PHP error logs for warnings

**Expected Results:**
- No PHP warnings or notices
- Graceful handling of edge cases
- Functions return expected string values

**Priority:** ⭐⭐⭐⭐ (High)

### 9. Browser Compatibility Tests
**Objective:** Ensure changes work across different browsers

**Test Steps:**
1. Test forms in multiple browsers:
   - Chrome (latest)
   - Firefox (latest)
   - Safari (latest)
   - Edge (latest)

2. Test on different devices:
   - Desktop
   - Mobile
   - Tablet

**Expected Results:**
- Consistent rendering across all browsers
- No browser-specific errors
- Responsive design maintained

**Priority:** ⭐⭐⭐ (Medium)

---

## 🔧 Technical Validation Tests

### 10. HTML Validation
**Objective:** Ensure generated HTML is valid

**Test Steps:**
1. Create comprehensive test form
2. View source and validate HTML using W3C validator
3. Check for any malformed attributes or tags

**Expected Results:**
- Valid HTML5 markup
- Proper attribute quoting
- No structural errors

**Priority:** ⭐⭐⭐ (Medium)

### 11. Performance Impact
**Objective:** Verify changes don't negatively impact performance

**Test Steps:**
1. Measure page load times before and after changes
2. Test with forms containing many fields (50+ fields)
3. Monitor server response times
4. Check for any memory usage increases

**Expected Results:**
- No significant performance degradation
- Page load times remain similar
- Memory usage stays within normal ranges

**Priority:** ⭐⭐ (Low)

---

## 📊 Test Reporting

### Test Execution Checklist
- [ ] Environment setup completed
- [ ] Test data prepared
- [ ] Core functionality tests passed
- [ ] Admin interface tests passed
- [ ] Form submission tests passed
- [ ] Pro-specific tests passed (if applicable)
- [ ] Edge case tests passed
- [ ] Security validation completed
- [ ] Cross-browser testing completed
- [ ] Performance validation completed

### Critical Issues to Report Immediately
1. XSS vulnerabilities found
2. Form submission failures
3. PHP errors or warnings
4. Data loss or corruption
5. Admin page crashes
6. Email functionality broken

### Bug Report Template
```
**Title:** [Brief description of issue]
**Severity:** Critical/High/Medium/Low
**Environment:** WordPress version, PHP version, Browser
**Steps to Reproduce:**
1. Step one
2. Step two
3. Step three

**Expected Result:** What should happen
**Actual Result:** What actually happened
**Screenshots:** [Attach relevant screenshots]
**Additional Notes:** Any other relevant information
```

---

## 🚀 Pre-Deployment Checklist

Before approving this PR for production:

- [ ] All critical and high priority tests pass
- [ ] No XSS vulnerabilities identified
- [ ] No PHP errors in error logs
- [ ] Form submissions work correctly
- [ ] Email notifications function properly
- [ ] Admin interface operates normally
- [ ] Pro features work as expected
- [ ] Cross-browser compatibility confirmed
- [ ] Mobile responsiveness maintained

---

## 📞 Support Information

If you encounter any issues during testing:
- Check PHP error logs first
- Test in a clean environment
- Document exact steps to reproduce
- Include browser console errors
- Provide WordPress and plugin version details

**Testing Duration Estimate:** 4-6 hours for comprehensive testing
**Recommended Testers:** 2-3 team members with different testing focuses