# Doctor Login Credentials - Smile Bright Dental

## 🔐 Authentication System

### Access URL
- **Login Page**: `http://localhost/SmileBright/public/booking/doctor_login.html`
- **Dashboard**: `http://localhost/SmileBright/public/booking/doctor_dashboard.html` (to be created)

### Doctor Accounts

| Doctor Name | Login ID | Password | Status |
|-------------|----------|----------|---------|
| Dr. Chua Wen Zhan | `dr-chua` | `chua123` | ✅ Active |
| Dr. Lau Gwen | `dr-lau` | `lau123` | ✅ Active |
| Dr. Sarah Tan | `dr-sarah` | `sarah123` | ✅ Active |
| Dr. James Lim | `dr-james` | `james123` | ✅ Active |
| Dr. Aisha Rahman | `dr-aisha` | `aisha123` | ✅ Active |
| Dr. Alex Lee | `dr-alex` | `alex123` | ✅ Active |

## 🎯 Dashboard Features

### View Appointments
- ✅ List all appointments
- ✅ Filter by status (Scheduled, Completed, Cancelled)
- ✅ Filter by date range
- ✅ Search by patient name

### Statistics
- ✅ Total appointments count
- ✅ Scheduled appointments
- ✅ Today's appointments
- ✅ Weekly/monthly summaries

### Edit Appointments
- ✅ Change appointment date
- ✅ Change appointment time
- ✅ Add/edit notes
- ✅ Update appointment status

### Email Notifications
- ✅ Send notifications to patients on changes
- ✅ Send notifications to clinic staff
- ✅ Automatic email triggers

## 🔒 Security Notes

### Current Implementation (Demo Only)
- **Simple password system** for demonstration purposes
- **Session stored in sessionStorage** (expires on tab close)
- **No password hashing** (not production-ready)
- **No HTTPS/SSL** (local development only)

### Production Recommendations
- [ ] Database-stored hashed passwords
- [ ] Session tokens with expiry
- [ ] Role-based access control
- [ ] Password recovery system
- [ ] Two-factor authentication
- [ ] HTTPS/SSL encryption
- [ ] Rate limiting for login attempts
- [ ] Audit logging

## 📱 Session Management

### Storage Method
- **Store**: `sessionStorage`
- **Expiry**: On tab close
- **Manual Logout**: Available in dashboard

### Session Data Structure
```javascript
{
  doctorId: "dr-chua",
  doctorName: "Dr. Chua Wen Zhan",
  loginTime: "2025-01-25T10:30:00.000Z"
}
```

## 🚀 Usage Instructions

### For Doctors
1. Navigate to the login page
2. Select your name from the dropdown
3. Enter your password
4. Click "Login to Dashboard"
5. Access your appointment management interface

### For Administrators
1. Use the credentials above to test the system
2. Monitor appointment changes and email notifications
3. Update doctor credentials as needed

## 🔧 Technical Details

### File Structure
```
public/booking/
├── doctor_login.html          # Login page
├── doctor_dashboard.html      # Dashboard (to be created)
└── doctor_logout.html         # Logout page (to be created)
```

### Dependencies
- **Frontend**: Pure HTML/CSS/JavaScript (no frameworks)
- **Backend**: PHP API endpoints (to be created)
- **Database**: MySQL (existing SmileBright database)
- **Email**: Existing email service integration

## 📞 Support

For technical issues or password resets, contact:
- **Email**: admin@smilebrightdental.sg
- **Phone**: +65 6234 5678

---

**⚠️ Important**: This is a demonstration system. For production use, implement proper security measures as outlined in the production recommendations above.

**Last Updated**: January 25, 2025
**Version**: 1.0.0
