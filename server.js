const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const dotenv = require('dotenv');
const winston = require('winston');
const path = require('path');

// Load environment variables
dotenv.config();

// Import services
const emailService = require('./services/emailService');
const messagingService = require('./services/messagingService');
const webhookService = require('./services/webhookService');
const logger = require('./utils/logger');

const app = express();
const PORT = process.env.PORT || 3001;

// ============================================================
// MIDDLEWARE SETUP
// ============================================================

// Security middleware
app.use(helmet());

// CORS configuration
app.use(cors({
  origin: process.env.NODE_ENV === 'production' 
    ? ['https://smilebrightdental.sg', 'https://www.smilebrightdental.sg']
    : ['http://localhost', 'http://127.0.0.1'],
  credentials: true
}));

// Rate limiting
if (process.env.ENABLE_RATE_LIMITING === 'true') {
  const limiter = rateLimit({
    windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000, // 15 minutes
    max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS) || 100,
    message: {
      error: 'Too many requests from this IP, please try again later.',
      retryAfter: Math.ceil((parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000) / 1000)
    },
    standardHeaders: true,
    legacyHeaders: false,
  });
  app.use('/api/', limiter);
}

// Body parsing middleware
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Request logging middleware
app.use((req, res, next) => {
  logger.info(`${req.method} ${req.path}`, {
    ip: req.ip,
    userAgent: req.get('User-Agent'),
    body: req.method === 'POST' ? { ...req.body, /* redact sensitive data */ } : undefined
  });
  next();
});

// ============================================================
// HEALTH CHECK ENDPOINT
// ============================================================

app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    timestamp: new Date().toISOString(),
    version: '1.0.0',
    services: {
      email: process.env.ENABLE_EMAIL === 'true',
      sms: process.env.ENABLE_SMS === 'true',
      whatsapp: process.env.ENABLE_WHATSAPP === 'true',
      webhooks: process.env.ENABLE_WEBHOOKS === 'true'
    }
  });
});

// ============================================================
// NOTIFICATION ENDPOINTS
// ============================================================

/**
 * Send booking confirmation email and SMS/WhatsApp
 * POST /api/notifications/booking-created
 */
app.post('/api/notifications/booking-created', async (req, res) => {
  try {
    const { 
      to, 
      template_id = 'booking_created',
      variables = {} 
    } = req.body;

    // Validate required fields
    if (!to || !variables.reference_id) {
      return res.status(400).json({
        error: 'Missing required fields: to, variables.reference_id'
      });
    }

    const results = {};

    // Send email
    if (process.env.ENABLE_EMAIL === 'true') {
      try {
        const emailResult = await emailService.sendEmail({
          to,
          template_id,
          variables
        });
        results.email = emailResult;
        logger.info('Email sent successfully', { 
          messageId: emailResult.messageId,
          to: to.replace(/(.{2}).*(@.*)/, '$1***$2') // Redact email
        });
      } catch (emailError) {
        logger.error('Email sending failed', { error: emailError.message });
        results.email = { error: emailError.message };
      }
    }

    // Send SMS/WhatsApp
    if (process.env.ENABLE_SMS === 'true' || process.env.ENABLE_WHATSAPP === 'true') {
      try {
        const messagingResult = await messagingService.sendMessage({
          to: variables.phone || to, // Use phone from variables if available
          template_id: `${template_id}_sms`,
          variables
        });
        results.messaging = messagingResult;
        logger.info('Message sent successfully', { 
          messageId: messagingResult.messageId,
          channel: messagingResult.channel,
          to: variables.phone ? variables.phone.replace(/(.{3}).*(.{3})/, '$1***$2') : 'N/A'
        });
      } catch (messagingError) {
        logger.error('Message sending failed', { error: messagingError.message });
        results.messaging = { error: messagingError.message };
      }
    }

    res.json({
      success: true,
      results,
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    logger.error('Booking notification failed', { error: error.message, stack: error.stack });
    res.status(500).json({
      error: 'Internal server error',
      message: process.env.NODE_ENV === 'development' ? error.message : 'Something went wrong'
    });
  }
});

/**
 * Send clinic adjustment notification
 * POST /api/notifications/clinic-adjusted
 */
app.post('/api/notifications/clinic-adjusted', async (req, res) => {
  try {
    const { 
      to, 
      template_id = 'clinic_adjusted',
      variables = {} 
    } = req.body;

    if (!to || !variables.reference_id) {
      return res.status(400).json({
        error: 'Missing required fields: to, variables.reference_id'
      });
    }

    const results = {};

    // Send email
    if (process.env.ENABLE_EMAIL === 'true') {
      try {
        const emailResult = await emailService.sendEmail({
          to,
          template_id,
          variables
        });
        results.email = emailResult;
      } catch (emailError) {
        logger.error('Email sending failed', { error: emailError.message });
        results.email = { error: emailError.message };
      }
    }

    // Send SMS/WhatsApp
    if (process.env.ENABLE_SMS === 'true' || process.env.ENABLE_WHATSAPP === 'true') {
      try {
        const messagingResult = await messagingService.sendMessage({
          to: variables.phone || to,
          template_id: `${template_id}_sms`,
          variables
        });
        results.messaging = messagingResult;
      } catch (messagingError) {
        logger.error('Message sending failed', { error: messagingError.message });
        results.messaging = { error: messagingError.message };
      }
    }

    res.json({
      success: true,
      results,
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    logger.error('Clinic adjustment notification failed', { error: error.message });
    res.status(500).json({
      error: 'Internal server error',
      message: process.env.NODE_ENV === 'development' ? error.message : 'Something went wrong'
    });
  }
});

/**
 * Send reschedule confirmation notification
 * POST /api/notifications/rescheduled-by-client
 */
app.post('/api/notifications/rescheduled-by-client', async (req, res) => {
  try {
    const { 
      to, 
      template_id = 'rescheduled_by_client',
      variables = {} 
    } = req.body;

    if (!to || !variables.reference_id) {
      return res.status(400).json({
        error: 'Missing required fields: to, variables.reference_id'
      });
    }

    const results = {};

    // Send email
    if (process.env.ENABLE_EMAIL === 'true') {
      try {
        const emailResult = await emailService.sendEmail({
          to,
          template_id,
          variables
        });
        results.email = emailResult;
      } catch (emailError) {
        logger.error('Email sending failed', { error: emailError.message });
        results.email = { error: emailError.message };
      }
    }

    // Send SMS/WhatsApp
    if (process.env.ENABLE_SMS === 'true' || process.env.ENABLE_WHATSAPP === 'true') {
      try {
        const messagingResult = await messagingService.sendMessage({
          to: variables.phone || to,
          template_id: `${template_id}_sms`,
          variables
        });
        results.messaging = messagingResult;
      } catch (messagingError) {
        logger.error('Message sending failed', { error: messagingError.message });
        results.messaging = { error: messagingError.message };
      }
    }

    res.json({
      success: true,
      results,
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    logger.error('Reschedule notification failed', { error: error.message });
    res.status(500).json({
      error: 'Internal server error',
      message: process.env.NODE_ENV === 'development' ? error.message : 'Something went wrong'
    });
  }
});

// ============================================================
// WEBHOOK ENDPOINTS
// ============================================================

if (process.env.ENABLE_WEBHOOKS === 'true') {
  app.use('/webhooks', webhookService);
}

// ============================================================
// ERROR HANDLING MIDDLEWARE
// ============================================================

app.use((err, req, res, next) => {
  logger.error('Unhandled error', { 
    error: err.message, 
    stack: err.stack,
    path: req.path,
    method: req.method
  });
  
  res.status(500).json({
    error: 'Internal server error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  });
});

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({
    error: 'Not found',
    message: `Route ${req.method} ${req.originalUrl} not found`
  });
});

// ============================================================
// SERVER STARTUP
// ============================================================

app.listen(PORT, () => {
  logger.info(`SmileBright Notifications Server running on port ${PORT}`, {
    environment: process.env.NODE_ENV,
    services: {
      email: process.env.ENABLE_EMAIL === 'true',
      sms: process.env.ENABLE_SMS === 'true',
      whatsapp: process.env.ENABLE_WHATSAPP === 'true',
      webhooks: process.env.ENABLE_WEBHOOKS === 'true'
    }
  });
});

// Graceful shutdown
process.on('SIGTERM', () => {
  logger.info('SIGTERM received, shutting down gracefully');
  process.exit(0);
});

process.on('SIGINT', () => {
  logger.info('SIGINT received, shutting down gracefully');
  process.exit(0);
});

module.exports = app;
