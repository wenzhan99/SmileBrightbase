# Snake Case Refactoring Plan

## 🎯 Goal
Standardize all file and directory names to **snake_case** convention throughout the entire project.

---

## 📋 Current State Analysis

### ✅ Already Following Snake Case:
**HTML Files:**
- about_us.html
- book_appointment.html
- booking_confirmation.html
- booking_form.html
- booking_success.html
- api_test.html
- manage_booking.html
- doctor_dashboard.html
- doctor_login.html
- test_booking_api.html
- setup_new_db.php

**PHP Files:**
- All `.php` files already use snake_case ✓

**JavaScript Files:**
- doctor_dashboard.js
- test_dashboard_fix.js
- footer.js
- test.js
- server.js

**CSS Files:**
- footer.css

**Markdown Files:**
- Using UPPER_SNAKE_CASE for documentation (standard practice) ✓

**Database Files:**
- All migration and seed files use snake_case ✓

---

## 🔄 Files That Need Renaming

### HTML Files (1 file)
| Current Name | New Name | Reason |
|--------------|----------|---------|
| `FAQ.html` | `faq.html` | All caps → lowercase snake_case |

### JavaScript Files (1 file)
| Current Name | New Name | Reason |
|--------------|----------|---------|
| `integration-test.js` | `integration_test.js` | kebab-case → snake_case |

### Total Files to Rename: **2 files**

---

## 📝 References That Need Updating

After renaming files, we need to update references in:

### 1. FAQ.html → faq.html
**Files that may reference it:**
- `public/index.html` - navigation links
- `public/about_us.html` - navigation links
- `public/services.html` - navigation links
- `public/clinics.html` - navigation links
- `public/book_appointment.html` - navigation links
- Any other pages with navigation menus

### 2. integration-test.js → integration_test.js
**Files that may reference it:**
- `email-service/package.json` - scripts section
- Any documentation in `email-service/README.md`

---

## 🚀 Execution Plan

### Phase 1: Rename HTML Files
```
1. Rename: public/FAQ.html → public/faq.html
2. Update all navigation references
```

### Phase 2: Rename JavaScript Files
```
1. Rename: email-service/integration-test.js → email-service/integration_test.js
2. Update package.json scripts
```

### Phase 3: Update Documentation
```
1. Update any docs that reference the old filenames
2. Update README files
```

### Phase 4: Verify
```
1. Check all internal links work
2. Verify no broken references
3. Test navigation
4. Run the application
```

---

## ⚠️ Important Notes

### Files to Keep As-Is:
1. **Documentation (.md files)** - Using UPPER_SNAKE_CASE is standard for documentation
2. **Package files** - package.json, composer.json (standard naming)
3. **Config files** - .htaccess, .env (standard naming)
4. **Batch/Shell scripts** - start_notifications.bat/sh (acceptable naming)
5. **Node modules** - Don't touch anything in node_modules/vendor

### Naming Convention Standards:
- **HTML/PHP/JS files:** snake_case (lowercase with underscores)
- **Documentation:** UPPER_SNAKE_CASE or Title Case
- **Database:** snake_case for tables and columns
- **CSS classes:** kebab-case (industry standard)
- **URLs:** kebab-case or snake_case (we use snake_case in filenames)

---

## 🔍 Search Patterns for References

### For FAQ.html:
```bash
grep -r "FAQ.html" --include="*.html" --include="*.js" --include="*.php"
```

### For integration-test.js:
```bash
grep -r "integration-test" --include="*.json" --include="*.js" --include="*.md"
```

---

## ✅ Post-Refactor Checklist

- [ ] All files renamed
- [ ] All references updated
- [ ] Navigation links work
- [ ] No 404 errors
- [ ] Git history preserved (use git mv)
- [ ] Documentation updated
- [ ] Changes committed

---

**Created:** October 26, 2025  
**Status:** Ready for execution

