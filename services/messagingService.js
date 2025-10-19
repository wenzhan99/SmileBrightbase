const twilio = require('twilio');
const logger = require('../utils/logger');

class MessagingService {
  constructor() {
    this.provider = process.env.MSG_PROVIDER || 'twilio';
    this.clients = {};
    this.templates = new Map();
    this.initializeProviders();
    this.loadTemplates();
  }

  /**
   * Initialize messaging providers
   */
  async initializeProviders() {
    try {
      // Initialize Twilio
      if (process.env.TWILIO_ACCOUNT_SID && process.env.TWILIO_AUTH_TOKEN) {
        this.clients.twilio = twilio(process.env.TWILIO_ACCOUNT_SID, process.env.TWILIO_AUTH_TOKEN);
        logger.info('Twilio client initialized');
      }

      // Initialize other providers as needed
      // Vonage, MessageBird, AWS SNS, Meta WhatsApp can be added here

      logger.info(`Messaging service initialized with provider: ${this.provider}`);

    } catch (error) {
      logger.error('Failed to initialize messaging providers', { error: error.message });
      throw new Error(`Messaging provider initialization error: ${error.message}`);
    }
  }

  /**
   * Load messaging templates
   */
  loadTemplates() {
    // SMS Templates
    this.templates.set('booking_created_sms', {
      text: 'SmileBright: Ref {{reference_id}} on {{date}} {{time}} at {{clinic}}. View: {{short_link}}',
      maxLength: 160
    });

    this.templates.set('clinic_adjusted_sms', {
      text: 'SmileBright: Your appt changed {{old_date}} {{old_time}} → {{date}} {{time}}. View: {{short_link}}',
      maxLength: 160
    });

    this.templates.set('rescheduled_by_client_sms', {
      text: 'SmileBright: Rescheduled to {{date}} {{time}}. Ref {{reference_id}}. View: {{short_link}}',
      maxLength: 160
    });

    // WhatsApp Templates (for Meta Cloud API)
    this.templates.set('booking_created_wa', {
      name: 'booking_created',
      language: 'en',
      components: [
        {
          type: 'header',
          parameters: [{ type: 'text', text: 'SmileBright Booking' }]
        },
        {
          type: 'body',
          parameters: [
            { type: 'text', text: '{{full_name}}' },
            { type: 'text', text: '{{date}}' },
            { type: 'text', text: '{{time}}' },
            { type: 'text', text: '{{clinic}}' },
            { type: 'text', text: '{{reference_id}}' }
          ]
        },
        {
          type: 'button',
          sub_type: 'url',
          index: 0,
          parameters: [{ type: 'text', text: '{{short_link}}' }]
        }
      ]
    });

    logger.info('Messaging templates loaded successfully');
  }

  /**
   * Send message via SMS or WhatsApp
   */
  async sendMessage({ to, template_id, variables = {} }) {
    try {
      // Validate phone number format (E.164)
      const phoneNumber = this.formatPhoneNumber(to);
      if (!phoneNumber) {
        throw new Error('Invalid phone number format');
      }

      // Determine channel priority: WhatsApp first, then SMS
      const channels = this.getChannelPriority();
      
      for (const channel of channels) {
        try {
          const result = await this.sendViaChannel(channel, phoneNumber, template_id, variables);
          if (result.success) {
            logger.info(`Message sent successfully via ${channel}`, {
              messageId: result.messageId,
              channel: channel,
              to: phoneNumber.replace(/(.{3}).*(.{3})/, '$1***$2')
            });
            return result;
          }
        } catch (channelError) {
          logger.warn(`Failed to send via ${channel}, trying next channel`, {
            error: channelError.message,
            channel: channel
          });
          continue;
        }
      }

      throw new Error('All messaging channels failed');

    } catch (error) {
      logger.error('Message sending failed', { 
        error: error.message, 
        to: to.replace(/(.{3}).*(.{3})/, '$1***$2'),
        template: template_id
      });
      throw error;
    }
  }

  /**
   * Get channel priority based on configuration
   */
  getChannelPriority() {
    const channels = [];
    
    if (process.env.ENABLE_WHATSAPP === 'true') {
      channels.push('whatsapp');
    }
    
    if (process.env.ENABLE_SMS === 'true') {
      channels.push('sms');
    }
    
    return channels;
  }

  /**
   * Send message via specific channel
   */
  async sendViaChannel(channel, to, template_id, variables) {
    switch (channel) {
      case 'whatsapp':
        return await this.sendWhatsApp(to, template_id, variables);
      case 'sms':
        return await this.sendSMS(to, template_id, variables);
      default:
        throw new Error(`Unsupported channel: ${channel}`);
    }
  }

  /**
   * Send SMS via Twilio
   */
  async sendSMS(to, template_id, variables) {
    if (!this.clients.twilio) {
      throw new Error('Twilio client not initialized');
    }

    const template = this.templates.get(template_id);
    if (!template) {
      throw new Error(`Template '${template_id}' not found`);
    }

    // Prepare template variables
    const templateVars = this.prepareTemplateVariables(variables);
    
    // Generate message text
    const messageText = this.renderTemplate(template.text, templateVars);

    // Send SMS
    const message = await this.clients.twilio.messages.create({
      body: messageText,
      from: process.env.TWILIO_PHONE_NUMBER,
      to: to
    });

    return {
      success: true,
      messageId: message.sid,
      channel: 'sms',
      status: message.status,
      provider: 'twilio'
    };
  }

  /**
   * Send WhatsApp message via Twilio
   */
  async sendWhatsApp(to, template_id, variables) {
    if (!this.clients.twilio) {
      throw new Error('Twilio client not initialized');
    }

    const template = this.templates.get(template_id);
    if (!template) {
      throw new Error(`Template '${template_id}' not found`);
    }

    // For WhatsApp, we'll use a simple text message
    // In production, you'd use WhatsApp Business API with approved templates
    const templateVars = this.prepareTemplateVariables(variables);
    const messageText = this.renderTemplate(template.text, templateVars);

    // Send WhatsApp message
    const message = await this.clients.twilio.messages.create({
      body: messageText,
      from: `whatsapp:${process.env.TWILIO_PHONE_NUMBER}`,
      to: `whatsapp:${to}`
    });

    return {
      success: true,
      messageId: message.sid,
      channel: 'whatsapp',
      status: message.status,
      provider: 'twilio'
    };
  }

  /**
   * Send WhatsApp via Meta Cloud API (alternative implementation)
   */
  async sendWhatsAppMeta(to, template_id, variables) {
    // This would be implemented for Meta Cloud API
    // Requires approved WhatsApp Business templates
    throw new Error('Meta WhatsApp API not implemented yet');
  }

  /**
   * Prepare template variables with formatting
   */
  prepareTemplateVariables(variables) {
    const moment = require('moment');
    moment.locale('en-sg');

    return {
      ...variables,
      // Format dates for SMS (shorter format)
      date: variables.date ? moment(variables.date).format('MMM D') : '',
      time: variables.time ? moment(variables.time, 'HH:mm:ss').format('h:mm A') : '',
      old_date: variables.old_date ? moment(variables.old_date).format('MMM D') : '',
      old_time: variables.old_time ? moment(variables.old_time, 'HH:mm:ss').format('h:mm A') : '',
      
      // Short link for SMS
      short_link: variables.view_url || `${process.env.WEBSITE_URL}/ref/${variables.reference_id}`,
      
      // Clinic name (shortened for SMS)
      clinic: variables.clinic || 'SmileBright'
    };
  }

  /**
   * Render template with variables
   */
  renderTemplate(template, variables) {
    let rendered = template;
    
    // Replace variables in template
    for (const [key, value] of Object.entries(variables)) {
      const regex = new RegExp(`{{${key}}}`, 'g');
      rendered = rendered.replace(regex, value || '');
    }
    
    return rendered;
  }

  /**
   * Format phone number to E.164 format
   */
  formatPhoneNumber(phone) {
    if (!phone) return null;
    
    // Remove all non-digit characters
    const digits = phone.replace(/\D/g, '');
    
    // Handle Singapore numbers
    if (digits.startsWith('65')) {
      return `+${digits}`;
    } else if (digits.startsWith('8') || digits.startsWith('9')) {
      return `+65${digits}`;
    } else if (digits.length >= 10) {
      return `+${digits}`;
    }
    
    return null;
  }

  /**
   * Test messaging configuration
   */
  async testConfiguration() {
    try {
      const results = {};
      
      if (this.clients.twilio) {
        try {
          // Test Twilio connection
          const account = await this.clients.twilio.api.accounts(this.clients.twilio.accountSid).fetch();
          results.twilio = {
            success: true,
            message: 'Twilio connection successful',
            accountSid: account.sid
          };
        } catch (error) {
          results.twilio = {
            success: false,
            message: 'Twilio connection failed',
            error: error.message
          };
        }
      }

      return {
        success: Object.values(results).some(r => r.success),
        results
      };
    } catch (error) {
      return {
        success: false,
        message: 'Messaging configuration test failed',
        error: error.message
      };
    }
  }

  /**
   * Get message status from provider
   */
  async getMessageStatus(messageId, provider = 'twilio') {
    try {
      if (provider === 'twilio' && this.clients.twilio) {
        const message = await this.clients.twilio.messages(messageId).fetch();
        return {
          messageId: message.sid,
          status: message.status,
          direction: message.direction,
          dateCreated: message.dateCreated,
          dateUpdated: message.dateUpdated,
          errorCode: message.errorCode,
          errorMessage: message.errorMessage
        };
      }
      
      throw new Error(`Provider ${provider} not supported for status lookup`);
    } catch (error) {
      logger.error('Failed to get message status', { error: error.message, messageId });
      throw error;
    }
  }
}

module.exports = new MessagingService();
