const express = require('express');
const cors = require('cors');
const nodemailer = require('nodemailer');
const { createEvent } = require('ics');
const moment = require('moment-timezone');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 4001;

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));

// Configure Nodemailer transporter
const transporter = nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: parseInt(process.env.SMTP_PORT),
    secure: process.env.SMTP_SECURE === 'true',
    auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS
    }
});

// Verify transporter configuration
transporter.verify((error, success) => {
    if (error) {
        console.error('SMTP configuration error:', error);
    } else {
        console.log('✅ SMTP server is ready to send emails');
    }
});

// Helper function to validate email token
function validateEmailToken(req) {
    const token = req.headers['x-email-token'];
    return token === process.env.EMAIL_TOKEN;
}

// Helper function to create ICS calendar event
function createCalendarEvent(appointmentData) {
    const { appointment, patient } = appointmentData;
    
    // Parse appointment date and time
    const appointmentDate = moment.tz(appointment.dateIso, process.env.TIMEZONE);
    const [hours, minutes] = appointment.time24.split(':');
    const startTime = appointmentDate.clone().hour(parseInt(hours)).minute(parseInt(minutes));
    const endTime = startTime.clone().add(1, 'hour'); // 1 hour appointment
    
    const event = {
        start: [
            startTime.year(),
            startTime.month() + 1, // ICS uses 1-based months
            startTime.date(),
            startTime.hour(),
            startTime.minute()
        ],
        end: [
            endTime.year(),
            endTime.month() + 1,
            endTime.date(),
            endTime.hour(),
            endTime.minute()
        ],
        title: `Dental Appointment - ${appointment.serviceLabel}`,
        description: `Dental appointment with ${appointment.dentistName} at ${appointment.clinicName}`,
        location: appointment.clinicName,
        status: 'CONFIRMED',
        busyStatus: 'BUSY',
        organizer: { name: 'Smile Bright Dental', email: process.env.FROM_EMAIL },
        attendees: [
            { name: `${patient.firstName} ${patient.lastName}`, email: patient.email, rsvp: true }
        ]
    };
    
    return createEvent(event);
}

// Helper function to generate patient email HTML
function generatePatientEmailHTML(data) {
    const { referenceId, patient, appointment, manageLink } = data;
    
    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmation</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f7fb; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: #1e4b86; color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; }
        .appointment-details { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #495057; }
        .detail-value { color: #212529; }
        .cta-button { display: inline-block; background: #1e4b86; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
        .reference { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0; }
        .reference-code { font-family: monospace; font-size: 18px; font-weight: bold; color: #1976d2; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Appointment Confirmed</h1>
            <p>Your dental appointment has been successfully booked</p>
        </div>
        
        <div class="content">
            <p>Dear ${patient.firstName},</p>
            
            <p>Thank you for choosing Smile Bright Dental! Your appointment has been confirmed.</p>
            
            <div class="reference">
                <strong>Reference ID:</strong><br>
                <span class="reference-code">${referenceId}</span>
            </div>
            
            <div class="appointment-details">
                <h3 style="margin-top: 0; color: #1e4b86;">Appointment Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Dentist:</span>
                    <span class="detail-value">${appointment.dentistName}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Clinic:</span>
                    <span class="detail-value">${appointment.clinicName}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service:</span>
                    <span class="detail-value">${appointment.serviceLabel}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">${appointment.dateDisplay}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">${appointment.timeDisplay}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Experience:</span>
                    <span class="detail-value">${appointment.experienceLabel}</span>
                </div>
            </div>
            
            <p><strong>Important:</strong></p>
            <ul>
                <li>Please arrive 10 minutes before your appointment time</li>
                <li>Bring a valid ID and any relevant medical records</li>
                <li>If you need to reschedule, please contact us at least 24 hours in advance</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="${manageLink}" class="cta-button">Manage Your Appointment</a>
            </div>
            
            <p>If you have any questions, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br>
            <strong>Smile Bright Dental Team</strong></p>
        </div>
        
        <div class="footer">
            <p>This email was sent to ${patient.email}</p>
            <p>© 2025 Smile Bright Dental. All rights reserved.</p>
        </div>
    </div>
</body>
</html>`;
}

// Helper function to generate clinic email HTML
function generateClinicEmailHTML(data) {
    const { referenceId, patient, appointment, notes } = data;
    
    return `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Booking Notification</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f7fb; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: #dc3545; color: white; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { padding: 30px; }
        .patient-details { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .appointment-details { background: #e8f5e8; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .detail-row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #495057; }
        .detail-value { color: #212529; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
        .reference { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .reference-code { font-family: monospace; font-size: 18px; font-weight: bold; color: #856404; }
        .notes { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🦷 New Booking Received</h1>
            <p>A new appointment has been booked</p>
        </div>
        
        <div class="content">
            <div class="reference">
                <strong>Reference ID:</strong><br>
                <span class="reference-code">${referenceId}</span>
            </div>
            
            <div class="patient-details">
                <h3 style="margin-top: 0; color: #dc3545;">Patient Information</h3>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${patient.firstName} ${patient.lastName}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${patient.email}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">${patient.phone}</span>
                </div>
            </div>
            
            <div class="appointment-details">
                <h3 style="margin-top: 0; color: #28a745;">Appointment Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Dentist:</span>
                    <span class="detail-value">${appointment.dentistName}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Clinic:</span>
                    <span class="detail-value">${appointment.clinicName}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Service:</span>
                    <span class="detail-value">${appointment.serviceLabel}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span>
                    <span class="detail-value">${appointment.dateDisplay}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span>
                    <span class="detail-value">${appointment.timeDisplay}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Experience:</span>
                    <span class="detail-value">${appointment.experienceLabel}</span>
                </div>
            </div>
            
            ${notes ? `
            <div class="notes">
                <h4 style="margin-top: 0; color: #17a2b8;">Patient Notes:</h4>
                <p>${notes}</p>
            </div>
            ` : ''}
            
            <p><strong>Action Required:</strong></p>
            <ul>
                <li>Confirm the appointment in your scheduling system</li>
                <li>Prepare patient file and any necessary documentation</li>
                <li>Send any pre-appointment instructions if needed</li>
            </ul>
        </div>
        
        <div class="footer">
            <p>This notification was automatically generated by the Smile Bright booking system</p>
            <p>© 2025 Smile Bright Dental. All rights reserved.</p>
        </div>
    </div>
</body>
</html>`;
}

// Main email sending endpoint
app.post('/send-booking-emails', async (req, res) => {
    try {
        // Validate authentication token
        if (!validateEmailToken(req)) {
            return res.status(401).json({ ok: false, message: 'Invalid email token' });
        }
        
        const { referenceId, patient, appointment, notes, consent } = req.body;
        
        // Validate required fields
        if (!referenceId || !patient || !appointment) {
            return res.status(400).json({ 
                ok: false, 
                message: 'Missing required fields: referenceId, patient, appointment' 
            });
        }
        
        // Generate manage link
        const manageLink = `${process.env.BASE_URL}/public/booking/manage_booking.html?ref=${referenceId}`;
        
        // Create calendar event
        const { error: icsError, value: icsContent } = createCalendarEvent({ patient, appointment });
        
        if (icsError) {
            console.error('ICS creation error:', icsError);
        }
        
        // Prepare email data
        const emailData = {
            referenceId,
            patient,
            appointment,
            manageLink,
            notes
        };
        
        // Send patient confirmation email
        const patientMailOptions = {
            from: `"${process.env.FROM_NAME}" <${process.env.FROM_EMAIL}>`,
            to: patient.email,
            subject: `Your Smile Bright appointment is confirmed — Ref ${referenceId}`,
            html: generatePatientEmailHTML(emailData),
            attachments: icsContent ? [{
                filename: `appointment-${referenceId}.ics`,
                content: icsContent,
                contentType: 'text/calendar'
            }] : []
        };
        
        // Send clinic notification email
        const clinicMailOptions = {
            from: `"${process.env.FROM_NAME}" <${process.env.FROM_EMAIL}>`,
            to: process.env.CLINIC_EMAIL,
            subject: `New booking — ${patient.firstName} ${patient.lastName} — ${appointment.dateDisplay} ${appointment.timeDisplay} — Ref ${referenceId}`,
            html: generateClinicEmailHTML(emailData),
            attachments: icsContent ? [{
                filename: `appointment-${referenceId}.ics`,
                content: icsContent,
                contentType: 'text/calendar'
            }] : []
        };
        
        // Send both emails
        const [patientResult, clinicResult] = await Promise.allSettled([
            transporter.sendMail(patientMailOptions),
            transporter.sendMail(clinicMailOptions)
        ]);
        
        // Log results
        console.log('Patient email result:', patientResult.status);
        console.log('Clinic email result:', clinicResult.status);
        
        if (patientResult.status === 'rejected') {
            console.error('Patient email failed:', patientResult.reason);
        }
        
        if (clinicResult.status === 'rejected') {
            console.error('Clinic email failed:', clinicResult.reason);
        }
        
        // Return success response
        res.json({ ok: true });
        
    } catch (error) {
        console.error('Email sending error:', error);
        res.status(500).json({ 
            ok: false, 
            message: 'Failed to send emails',
            error: error.message 
        });
    }
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ 
        ok: true, 
        service: 'Smile Bright Email Service',
        version: '1.0.0',
        timestamp: new Date().toISOString()
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`🚀 Smile Bright Email Service running on port ${PORT}`);
    console.log(`📧 SMTP configured for: ${process.env.SMTP_HOST}:${process.env.SMTP_PORT}`);
    console.log(`🔐 Email token: ${process.env.EMAIL_TOKEN}`);
});

module.exports = app;
