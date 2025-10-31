# Troubleshooting Guide: Doctor Dashboard Issues

## 🔍 Quick Diagnostic Steps

### Step 1: Clear Browser Cache
**CRITICAL:** The browser may be loading an old version of the JavaScript file.

**Clear cache methods:**
- **Chrome/Edge:** Press `Ctrl + Shift + Delete` → Clear cache → Hard refresh: `Ctrl + F5`
- **Firefox:** Press `Ctrl + Shift + Delete` → Clear cache → Hard refresh: `Ctrl + F5`
- **Or:** Open DevTools (F12) → Network tab → Check "Disable cache" checkbox

### Step 2: Run API Diagnostic Test
1. Navigate to: `http://localhost/SmileBright/public/booking/api_test.html`
2. Click each test button
3. Verify the results

**Expected Results:**
- Test 1: Shows current URL path
- Test 2: Clicking "Test dr-lau-gwen" should return 1 booking (SB-20251026-B7A6A6)
- Test 3: Shows session data or "No session found"
- Test 4: Shows which doctor IDs have bookings

### Step 3: Check Console Logs
1. Open browser DevTools (F12)
2. Go to Console tab
3. Navigate to: `http://localhost/SmileBright/public/booking/doctor_dashboard.html`
4. Look for these logs:

```
Loading bookings for doctor: dr-lau-gwen
API URL: ../../api/booking/by-doctor.php?doctorId=dr-lau-gwen
API Response: {ok: true, bookings: [...]}
Number of bookings returned: X
```

### Step 4: Check Network Tab
1. Open browser DevTools (F12)
2. Go to Network tab
3. Reload the dashboard
4. Look for:
   - Request to `by-doctor.php` with status 200
   - Response containing `{ok: true, bookings: [...]}`

---

## 🐛 Common Issues & Fixes

### Issue 1: API Path 404 Not Found
**Symptom:** Network tab shows 404 error for API requests

**Cause:** API paths were using absolute paths `/api/` instead of relative paths

**Fix Applied:**
- Changed from: `/api/booking/by-doctor.php`
- Changed to: `../../api/booking/by-doctor.php`

**Verification:**
```javascript
// In browser console
fetch('../../api/booking/by-doctor.php?doctorId=dr-lau-gwen')
  .then(r => r.json())
  .then(d => console.log(d));
```

### Issue 2: Browser Cache Loading Old JS
**Symptom:** Code changes not reflected, no API calls visible

**Fix Applied:**
- Added cache-busting version parameter: `doctor_dashboard.js?v=20251026-bugfix`
- Forces browser to reload JS file

**Verification:**
1. Check Network tab → JS tab
2. Look for: `doctor_dashboard.js?v=20251026-bugfix`
3. Should show status 200 and recent timestamp

### Issue 3: Doctor Identifier Mismatch
**Symptom:** Dr. Lau Gwen sees 0 bookings but booking exists in database

**Fix Applied:**
- Changed login from `dr-lau` to `dr-lau-gwen`
- Now matches database `dentist_id` column

**Verification:**
```javascript
// Check session storage
const session = JSON.parse(sessionStorage.getItem('doctorSession'));
console.log(session.doctorId); 
// Should output: "dr-lau-gwen"
```

### Issue 4: Edit Button Not Working
**Symptom:** Clicking Edit does nothing, no console errors

**Fix Applied:**
- Removed broken inline `onclick` with JSON.stringify
- Implemented event delegation with `data-ref-id` attribute

**Verification:**
```javascript
// Check if event listener is attached
document.getElementById('bookingsBody').onclick = function() {
  console.log('Click detected on tbody');
};
```

### Issue 5: Time Slots Not Enforced
**Symptom:** Can schedule appointments at any time

**Fix Applied:**
- Added `FIXED_TIME_SLOTS` constant: `['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00']`
- Added client-side validation

**Verification:**
- Edit modal should only show 7 time options
- Attempting to save invalid time shows error

---

## 📋 Step-by-Step Testing Procedure

### Test 1: Login with Dr. Lau Gwen
1. Clear browser cache (Ctrl + Shift + Delete)
2. Go to: `http://localhost/SmileBright/public/booking/doctor_login.html`
3. Select: "Dr. Lau Gwen"
4. Enter password: `lau123`
5. Click "Login to Dashboard"

**Expected:**
- Redirects to dashboard
- Console shows: `Loading bookings for doctor: dr-lau-gwen`
- Dashboard loads with appointment visible

### Test 2: Verify Booking Appears
**Expected to see:**
- Reference ID: SB-20251026-B7A6A6
- Patient: WEN ZHAN CHUA (name order from DB)
- Date: Oct 27, 2025
- Time: 3:00 PM (formatted from 15:00)
- Service: General Checkup

**If missing:**
- Check console for API response
- Check Network tab for by-doctor.php request
- Run api_test.html diagnostic

### Test 3: Edit Button Functionality
1. Click "Edit" button on any appointment
2. Modal should open immediately
3. All fields populated
4. Only 7 time slots visible

**Expected:**
- Modal opens (no delay)
- Fields show current values
- Time slots: 9am, 10am, 11am, 2pm, 3pm, 4pm, 5pm
- Note: "Only 7 fixed time slots available"

### Test 4: Save Changes
1. In edit modal, change time to different slot
2. Click "Save Changes"
3. Check console for update request

**Expected Console:**
```
Updating appointment: {referenceId: "SB-...", changes: {...}, doctor: "dr-lau-gwen"}
Update response: {ok: true, ...}
```

**Expected Result:**
- Alert: "Appointment updated successfully!"
- Modal closes
- Dashboard refreshes with new data

---

## 🔧 Manual Verification Steps

### Check 1: Verify Database Has Booking
```sql
-- Run in phpMyAdmin
SELECT * FROM bookings 
WHERE reference_id = 'SB-20251026-B7A6A6';

-- Check dentist_id value
-- Should be: dr-lau-gwen
```

### Check 2: Test API Directly
```bash
# In browser or curl
http://localhost/SmileBright/api/booking/by-doctor.php?doctorId=dr-lau-gwen
```

**Expected Response:**
```json
{
  "ok": true,
  "bookings": [
    {
      "referenceId": "SB-20251026-B7A6A6",
      "dentistId": "dr-lau-gwen",
      "firstName": "WEN ZHAN",
      "lastName": "CHUA",
      "dateIso": "2025-10-27",
      "time24": "15:00:00",
      "serviceLabel": "General Checkup",
      "status": "scheduled",
      ...
    }
  ],
  "total": 1
}
```

### Check 3: Verify JavaScript File Loaded
```javascript
// In browser console
console.log(typeof loadBookings);
// Should output: "function"

console.log(typeof FIXED_TIME_SLOTS);
// Should output: "object"

console.log(FIXED_TIME_SLOTS);
// Should output: ['09:00', '10:00', '11:00', '14:00', '15:00', '16:00', '17:00']
```

### Check 4: Verify Event Delegation
```javascript
// In browser console after dashboard loads
const tbody = document.getElementById('bookingsBody');
console.log(tbody.onclick ? 'Has handler' : 'No handler');
// Should show: "Has handler" or check getEventListeners(tbody) in Chrome

// Try clicking programmatically
const btn = document.querySelector('.btn-edit');
if (btn) {
  console.log('Edit button data-ref-id:', btn.getAttribute('data-ref-id'));
  // Should show reference ID like: SB-20251026-B7A6A6
}
```

---

## 🎯 Quick Fix Checklist

If dashboard still not working:

- [ ] **Clear browser cache completely** (Ctrl + Shift + Delete)
- [ ] **Hard refresh page** (Ctrl + F5 or Ctrl + Shift + R)
- [ ] **Check JavaScript console for errors** (F12)
- [ ] **Run api_test.html diagnostic** 
- [ ] **Verify XAMPP/Apache is running**
- [ ] **Check database connection** (phpMyAdmin accessible)
- [ ] **Verify booking exists in database**
- [ ] **Check dentist_id matches login identifier**
- [ ] **Ensure no syntax errors in JS** (check console)
- [ ] **Test API endpoint directly in browser**
- [ ] **Check file permissions** (especially on Linux/Mac)

---

## 🚨 Red Flags

**If you see these, there's a problem:**

1. **No network requests visible**
   - JS file not loaded or old version cached
   - Fix: Hard refresh + clear cache

2. **404 on API requests**
   - Wrong path or file doesn't exist
   - Fix: Verify ../../api/booking/by-doctor.php exists

3. **Empty bookings array but booking in DB**
   - Doctor identifier mismatch
   - Fix: Verify dentist_id in DB matches doctorId in request

4. **Edit button exists but clicking does nothing**
   - Event delegation not attached
   - Fix: Verify JS file is latest version (check v= parameter)

5. **Can't login with dr-lau-gwen**
   - Credentials not updated
   - Fix: Verify doctor_login.html has dr-lau-gwen option

---

## 📞 Still Not Working?

If all tests pass but still having issues:

1. **Export current booking data:**
   ```sql
   SELECT * FROM bookings 
   WHERE dentist_id LIKE '%lau%' OR dentist_id LIKE '%gwen%';
   ```

2. **Check PHP error log:**
   - XAMPP: `xampp/apache/logs/error.log`

3. **Enable PHP error display temporarily:**
   ```php
   // In by-doctor.php (line 13)
   ini_set('display_errors', '1'); // Change to '1'
   ```

4. **Test with different doctor:**
   - Try logging in as Dr. Chua (dr-chua)
   - If working, confirms issue is specific to Dr. Lau Gwen

5. **Check for JavaScript errors:**
   - Console should show no red errors
   - If errors present, they prevent everything else from running

---

## ✅ Success Criteria

Dashboard is working correctly when:

- [x] Login with dr-lau-gwen succeeds
- [x] Dashboard loads without errors
- [x] Network tab shows successful API call to by-doctor.php
- [x] Console shows: "Number of bookings returned: 1" (or more)
- [x] Booking SB-20251026-B7A6A6 visible in table
- [x] Edit button opens modal when clicked
- [x] Only 7 time slots shown in edit modal
- [x] Can save changes successfully
- [x] Dashboard auto-refreshes after save

---

## 🔄 Cache Busting Techniques

If browser keeps loading old JS:

**Method 1: Version Parameter (Already Applied)**
```html
<script src="doctor_dashboard.js?v=20251026-bugfix"></script>
```

**Method 2: Disable Cache in DevTools**
- F12 → Network tab → Check "Disable cache"
- Keep DevTools open while testing

**Method 3: Incognito/Private Mode**
- Opens without cache
- `Ctrl + Shift + N` (Chrome/Edge)
- `Ctrl + Shift + P` (Firefox)

**Method 4: Change Version Number**
```html
<!-- Increment version number -->
<script src="doctor_dashboard.js?v=20251026-bugfix2"></script>
```

---

## 📁 File Locations Recap

```
SmileBright/
├── api/
│   └── booking/
│       ├── by-doctor.php ✅ API endpoint
│       ├── availability.php
│       └── update.php
│
└── public/
    └── booking/
        ├── doctor_login.html ✅ Login page
        ├── doctor_dashboard.html ✅ Dashboard
        ├── doctor_dashboard.js ✅ Dashboard logic
        └── api_test.html ✅ NEW: Diagnostic tool
```

**Relative paths from dashboard:**
- Dashboard location: `/public/booking/doctor_dashboard.html`
- API location: `/api/booking/by-doctor.php`
- Relative path: `../../api/booking/by-doctor.php` ✅

---

## 📊 Expected Console Output (Success)

When dashboard loads correctly, you should see:

```
Loading bookings for doctor: dr-lau-gwen
API URL: ../../api/booking/by-doctor.php?doctorId=dr-lau-gwen
API Response: {ok: true, bookings: Array(1), total: 1, filters: {…}}
Number of bookings returned: 1
Transformed bookings: Array(1)
  0: {referenceId: "SB-20251026-B7A6A6", patient: {…}, date: "2025-10-27", ...}
```

No red errors, no 404s, no CORS issues.

---

## 🎓 Understanding the Fixes

### Why Relative Paths?
- Dashboard is at: `/SmileBright/public/booking/`
- API is at: `/SmileBright/api/booking/`
- From dashboard, go up 2 levels (`../../`) then into `api/booking/`

### Why Event Delegation?
- Rows are dynamically generated (innerHTML)
- Inline onclick handlers with complex JSON break HTML parsing
- Event delegation attaches listener to parent (tbody), which is stable
- Clicks bubble up from buttons to tbody, where handler catches them

### Why Cache Busting?
- Browsers aggressively cache JS files for performance
- Changes to JS file not visible until cache expires or cleared
- Version parameter (`?v=...`) makes browser treat it as new file

### Why Time Slot Enforcement?
- Business rule: only 7 specific times allowed
- Prevents scheduling conflicts and enforces business hours
- Client-side validation provides immediate feedback
- Server should also validate (defense in depth)

---

**Last Updated:** October 26, 2025  
**Version:** 2.0 (Post-Path-Fix)

