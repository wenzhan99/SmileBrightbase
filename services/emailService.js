const nodemailer = require('nodemailer');
const handlebars = require('handlebars');
const fs = require('fs').promises;
const path = require('path');
const logger = require('../utils/logger');

class EmailService {
  constructor() {
    this.transporter = null;
    this.templates = new Map();
    this.initializeTransporter();
    this.loadTemplates();
  }

  /**
   * Initialize SMTP transporter
   */
  async initializeTransporter() {
    try {
      const config = {
        host: process.env.SMTP_HOST,
        port: parseInt(process.env.SMTP_PORT) || 587,
        secure: process.env.SMTP_SECURE === 'true',
        auth: {
          user: process.env.SMTP_USER,
          pass: process.env.SMTP_PASS
        },
        tls: {
          rejectUnauthorized: false // For development/testing
        }
      };

      this.transporter = nodemailer.createTransporter(config);

      // Verify connection
      await this.transporter.verify();
      logger.info('SMTP connection verified successfully');

    } catch (error) {
      logger.error('Failed to initialize SMTP transporter', { error: error.message });
      throw new Error(`SMTP configuration error: ${error.message}`);
    }
  }

  /**
   * Load email templates
   */
  async loadTemplates() {
    try {
      const templatesDir = path.join(__dirname, '../templates/email');
      
      // Load booking_created template
      const bookingCreatedHtml = await fs.readFile(
        path.join(templatesDir, 'booking_created.html'), 'utf8'
      );
      const bookingCreatedText = await fs.readFile(
        path.join(templatesDir, 'booking_created.txt'), 'utf8'
      );
      
      this.templates.set('booking_created', {
        html: handlebars.compile(bookingCreatedHtml),
        text: handlebars.compile(bookingCreatedText),
        subject: 'Your SmileBright booking — Ref {{reference_id}}'
      });

      // Load clinic_adjusted template
      const clinicAdjustedHtml = await fs.readFile(
        path.join(templatesDir, 'clinic_adjusted.html'), 'utf8'
      );
      const clinicAdjustedText = await fs.readFile(
        path.join(templatesDir, 'clinic_adjusted.txt'), 'utf8'
      );
      
      this.templates.set('clinic_adjusted', {
        html: handlebars.compile(clinicAdjustedHtml),
        text: handlebars.compile(clinicAdjustedText),
        subject: 'Appointment adjusted — Ref {{reference_id}}'
      });

      // Load rescheduled_by_client template
      const rescheduledHtml = await fs.readFile(
        path.join(templatesDir, 'rescheduled_by_client.html'), 'utf8'
      );
      const rescheduledText = await fs.readFile(
        path.join(templatesDir, 'rescheduled_by_client.txt'), 'utf8'
      );
      
      this.templates.set('rescheduled_by_client', {
        html: handlebars.compile(rescheduledHtml),
        text: handlebars.compile(rescheduledText),
        subject: 'Rescheduled confirmed — Ref {{reference_id}}'
      });

      logger.info('Email templates loaded successfully');

    } catch (error) {
      logger.error('Failed to load email templates', { error: error.message });
      // Don't throw error, use fallback templates
    }
  }

  /**
   * Send email using template
   */
  async sendEmail({ to, template_id, variables = {} }) {
    try {
      if (!this.transporter) {
        throw new Error('SMTP transporter not initialized');
      }

      const template = this.templates.get(template_id);
      if (!template) {
        throw new Error(`Template '${template_id}' not found`);
      }

      // Prepare template variables
      const templateVars = this.prepareTemplateVariables(variables);

      // Generate subject
      const subjectTemplate = handlebars.compile(template.subject);
      const subject = subjectTemplate(templateVars);

      // Generate HTML and text content
      const htmlContent = template.html(templateVars);
      const textContent = template.text(templateVars);

      // Prepare email options
      const mailOptions = {
        from: process.env.EMAIL_FROM || 'SmileBright Clinic <no-reply@smilebrightdental.sg>',
        to: to,
        replyTo: process.env.EMAIL_REPLY_TO || 'frontdesk@smilebrightdental.sg',
        subject: subject,
        html: htmlContent,
        text: textContent,
        headers: {
          'X-Mailer': 'SmileBright Notifications v1.0',
          'X-Priority': '3',
          'X-MSMail-Priority': 'Normal'
        }
      };

      // Add BCC if configured
      if (process.env.EMAIL_BCC_ADMIN) {
        mailOptions.bcc = process.env.EMAIL_BCC_ADMIN;
      }

      // Send email
      const result = await this.transporter.sendMail(mailOptions);
      
      logger.info('Email sent successfully', {
        messageId: result.messageId,
        to: to.replace(/(.{2}).*(@.*)/, '$1***$2'), // Redact email
        template: template_id,
        subject: subject
      });

      return {
        success: true,
        messageId: result.messageId,
        accepted: result.accepted,
        rejected: result.rejected
      };

    } catch (error) {
      logger.error('Email sending failed', { 
        error: error.message, 
        to: to.replace(/(.{2}).*(@.*)/, '$1***$2'),
        template: template_id
      });
      throw error;
    }
  }

  /**
   * Prepare template variables with formatting
   */
  prepareTemplateVariables(variables) {
    const moment = require('moment');
    moment.locale('en-sg');

    return {
      ...variables,
      // Format dates
      date: variables.date ? moment(variables.date).format('dddd, MMMM D, YYYY') : '',
      time: variables.time ? moment(variables.time, 'HH:mm:ss').format('h:mm A') : '',
      old_date: variables.old_date ? moment(variables.old_date).format('dddd, MMMM D, YYYY') : '',
      old_time: variables.old_time ? moment(variables.old_time, 'HH:mm:ss').format('h:mm A') : '',
      
      // Format URLs
      view_url: variables.view_url || `${process.env.WEBSITE_URL}/confirm.php?ref=${variables.reference_id}&token=${variables.reschedule_token}`,
      
      // Clinic information
      clinic_info: this.getClinicInfo(variables.clinic),
      
      // Branding
      website_url: process.env.WEBSITE_URL || 'https://smilebrightdental.sg',
      support_phone: process.env.SUPPORT_PHONE || '+65 6XXX XXXX',
      support_email: process.env.SUPPORT_EMAIL || 'reception@smilebrightdental.sg',
      
      // Current timestamp
      sent_at: moment().format('MMMM D, YYYY [at] h:mm A'),
      timezone: 'Asia/Singapore'
    };
  }

  /**
   * Get clinic information
   */
  getClinicInfo(clinicName) {
    const clinics = {
      'Novena': {
        name: 'Novena',
        address: 'Novena Medical Center, 10 Sinaran Drive #03-15, Singapore 307506',
        phone: '+65 6XXX XXXX',
        email: 'novena@smilebrightdental.sg'
      },
      'Tampines': {
        name: 'Tampines',
        address: 'Tampines Plaza, 5 Tampines Central 6 #02-08, Singapore 529482',
        phone: '+65 6XXX XXXX',
        email: 'tampines@smilebrightdental.sg'
      },
      'Jurong East': {
        name: 'Jurong East',
        address: 'JEM, 50 Jurong Gateway Road #03-14, Singapore 608549',
        phone: '+65 6XXX XXXX',
        email: 'jurongeast@smilebrightdental.sg'
      },
      'Woodlands': {
        name: 'Woodlands',
        address: 'Causeway Point, 1 Woodlands Square #03-26, Singapore 738099',
        phone: '+65 6XXX XXXX',
        email: 'woodlands@smilebrightdental.sg'
      },
      'Punggol': {
        name: 'Punggol',
        address: 'Waterway Point, 83 Punggol Central #03-22, Singapore 828761',
        phone: '+65 6XXX XXXX',
        email: 'punggol@smilebrightdental.sg'
      }
    };

    return clinics[clinicName] || {
      name: clinicName || 'SmileBright Clinic',
      address: 'Address not available',
      phone: process.env.SUPPORT_PHONE || '+65 6XXX XXXX',
      email: process.env.SUPPORT_EMAIL || 'reception@smilebrightdental.sg'
    };
  }

  /**
   * Test email configuration
   */
  async testConfiguration() {
    try {
      if (!this.transporter) {
        throw new Error('SMTP transporter not initialized');
      }

      const testResult = await this.transporter.verify();
      return {
        success: true,
        message: 'SMTP configuration is valid',
        result: testResult
      };
    } catch (error) {
      return {
        success: false,
        message: 'SMTP configuration test failed',
        error: error.message
      };
    }
  }
}

module.exports = new EmailService();
