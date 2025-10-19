const express = require('express');
const crypto = require('crypto');
const logger = require('../utils/logger');

const router = express.Router();

/**
 * Verify webhook signature for security
 */
function verifyWebhookSignature(req, res, next) {
  const signature = req.headers['x-twilio-signature'] || req.headers['x-signature'];
  const webhookSecret = process.env.WEBHOOK_SECRET;
  
  if (!webhookSecret) {
    logger.warn('Webhook secret not configured, skipping signature verification');
    return next();
  }

  if (!signature) {
    return res.status(401).json({ error: 'Missing signature header' });
  }

  // For Twilio webhooks
  if (req.headers['x-twilio-signature']) {
    const url = `${req.protocol}://${req.get('host')}${req.originalUrl}`;
    const params = req.body;
    
    try {
      const isValid = twilio.validateRequest(
        process.env.TWILIO_AUTH_TOKEN,
        signature,
        url,
        params
      );
      
      if (!isValid) {
        logger.warn('Invalid Twilio webhook signature', { ip: req.ip });
        return res.status(401).json({ error: 'Invalid signature' });
      }
    } catch (error) {
      logger.error('Webhook signature verification failed', { error: error.message });
      return res.status(401).json({ error: 'Signature verification failed' });
    }
  }

  next();
}

/**
 * Log webhook delivery status
 */
async function logDeliveryStatus(provider, channel, messageId, status, metadata = {}) {
  try {
    const logEntry = {
      timestamp: new Date().toISOString(),
      provider,
      channel,
      messageId,
      status,
      metadata,
      source: 'webhook'
    };

    logger.info('Message delivery status update', logEntry);

    // Here you would typically save to database
    // For now, we'll just log it
    // await saveDeliveryStatusToDatabase(logEntry);

  } catch (error) {
    logger.error('Failed to log delivery status', { error: error.message, messageId });
  }
}

/**
 * Twilio webhook endpoint for SMS/WhatsApp status updates
 */
router.post('/twilio', verifyWebhookSignature, async (req, res) => {
  try {
    const {
      MessageSid,
      MessageStatus,
      To,
      From,
      SmsStatus,
      SmsSid,
      EventType,
      ChannelSid
    } = req.body;

    const messageId = MessageSid || SmsSid;
    const status = MessageStatus || SmsStatus;
    const channel = From && From.includes('whatsapp') ? 'whatsapp' : 'sms';

    if (!messageId || !status) {
      logger.warn('Incomplete Twilio webhook data', { body: req.body });
      return res.status(400).json({ error: 'Missing required fields' });
    }

    // Map Twilio statuses to our internal statuses
    const statusMap = {
      'queued': 'queued',
      'sending': 'sending',
      'sent': 'sent',
      'delivered': 'delivered',
      'undelivered': 'failed',
      'failed': 'failed'
    };

    const mappedStatus = statusMap[status] || status;

    await logDeliveryStatus('twilio', channel, messageId, mappedStatus, {
      to: To,
      from: From,
      eventType: EventType,
      channelSid: ChannelSid,
      originalStatus: status
    });

    // Update database if needed
    // await updateBookingNotificationStatus(messageId, mappedStatus);

    res.status(200).json({ success: true });

  } catch (error) {
    logger.error('Twilio webhook processing failed', { error: error.message, body: req.body });
    res.status(500).json({ error: 'Webhook processing failed' });
  }
});

/**
 * Generic webhook endpoint for other providers
 */
router.post('/generic', async (req, res) => {
  try {
    const {
      provider,
      channel,
      messageId,
      status,
      metadata = {}
    } = req.body;

    if (!provider || !messageId || !status) {
      return res.status(400).json({ 
        error: 'Missing required fields: provider, messageId, status' 
      });
    }

    await logDeliveryStatus(provider, channel, messageId, status, metadata);

    res.status(200).json({ success: true });

  } catch (error) {
    logger.error('Generic webhook processing failed', { error: error.message, body: req.body });
    res.status(500).json({ error: 'Webhook processing failed' });
  }
});

/**
 * Email delivery status webhook (for providers like SendGrid, Mailgun)
 */
router.post('/email', async (req, res) => {
  try {
    const {
      provider,
      messageId,
      event,
      email,
      timestamp,
      metadata = {}
    } = req.body;

    if (!provider || !messageId || !event) {
      return res.status(400).json({ 
        error: 'Missing required fields: provider, messageId, event' 
      });
    }

    // Map email events to our internal statuses
    const eventMap = {
      'processed': 'queued',
      'delivered': 'delivered',
      'bounce': 'failed',
      'dropped': 'failed',
      'spam': 'delivered', // Consider spam as delivered
      'unsubscribe': 'delivered',
      'group_unsubscribe': 'delivered',
      'group_resubscribe': 'delivered'
    };

    const mappedStatus = eventMap[event] || event;

    await logDeliveryStatus(provider, 'email', messageId, mappedStatus, {
      email: email ? email.replace(/(.{2}).*(@.*)/, '$1***$2') : undefined,
      event,
      timestamp,
      ...metadata
    });

    res.status(200).json({ success: true });

  } catch (error) {
    logger.error('Email webhook processing failed', { error: error.message, body: req.body });
    res.status(500).json({ error: 'Webhook processing failed' });
  }
});

/**
 * Health check for webhooks
 */
router.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    timestamp: new Date().toISOString(),
    endpoints: [
      'POST /webhooks/twilio',
      'POST /webhooks/generic',
      'POST /webhooks/email'
    ]
  });
});

/**
 * Get delivery status for a message
 */
router.get('/status/:messageId', async (req, res) => {
  try {
    const { messageId } = req.params;
    const { provider = 'twilio' } = req.query;

    // This would typically query your database
    // For now, we'll return a mock response
    const status = {
      messageId,
      provider,
      status: 'delivered',
      timestamp: new Date().toISOString(),
      channel: 'sms'
    };

    res.json(status);

  } catch (error) {
    logger.error('Failed to get message status', { error: error.message, messageId: req.params.messageId });
    res.status(500).json({ error: 'Failed to get message status' });
  }
});

/**
 * Error handling middleware for webhooks
 */
router.use((err, req, res, next) => {
  logger.error('Webhook error', { 
    error: err.message, 
    stack: err.stack,
    path: req.path,
    method: req.method,
    body: req.body
  });
  
  res.status(500).json({
    error: 'Webhook processing error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  });
});

module.exports = router;
