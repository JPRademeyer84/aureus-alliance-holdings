# Email Setup Guide for Telegram Bot

## 📧 **Email Functionality Implementation**

The Telegram bot now supports password reset via email. Currently, it's configured to show the reset token in the bot interface, but you can easily enable email sending.

## 🔧 **Setup Instructions**

### **1. Install Nodemailer**
```bash
cd Aureus-Telegram-Bot
npm install nodemailer
```

### **2. Configure Email Settings**
Update the `.env` file with your email credentials:

```env
# Email Configuration
EMAIL_USER=your-email@gmail.com
EMAIL_PASS=your-app-password
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_SECURE=false
```

### **3. Gmail Setup (Recommended)**

#### **For Gmail:**
1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password:**
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this password in `EMAIL_PASS`

#### **Alternative Email Providers:**
- **Outlook/Hotmail:** `smtp-mail.outlook.com:587`
- **Yahoo:** `smtp.mail.yahoo.com:587`
- **Custom SMTP:** Update `EMAIL_HOST` and `EMAIL_PORT`

### **4. Enable Email in Code**
In `fixed-investment-bot.js`, uncomment the email functionality:

```javascript
// Change this line:
const nodemailer = require('nodemailer'); // Remove comment

// Uncomment the email transporter:
const emailTransporter = nodemailer.createTransporter({
  host: process.env.EMAIL_HOST || 'smtp.gmail.com',
  port: parseInt(process.env.EMAIL_PORT) || 587,
  secure: process.env.EMAIL_SECURE === 'true',
  auth: {
    user: process.env.EMAIL_USER,
    pass: process.env.EMAIL_PASS
  }
});

// Update sendPasswordResetEmail function to actually send emails
```

## 🎯 **Current Functionality**

### **Password Reset Flow:**
1. User clicks "🔄 Forgot Password?" during login
2. System generates secure reset token (30-minute expiration)
3. **Currently:** Token shown in bot interface
4. **With Email:** Token sent to user's email address
5. User enters token to reset password
6. New password set and auto-login enabled

### **Account Linking:**
1. Successful login automatically links Telegram account
2. **Currently:** Console log confirmation
3. **With Email:** Welcome email sent to user
4. Auto-login enabled for future bot interactions

## 🔒 **Security Features**

- **Secure Tokens:** Random 30-character reset tokens
- **Token Expiration:** 30-minute validity period
- **Database Storage:** Tokens stored securely in database
- **Auto-Cleanup:** Expired tokens automatically cleared
- **Account Verification:** Validates linked accounts still exist

## 🧪 **Testing the System**

### **Test Password Reset:**
1. Start login process: `/start` → "🔑 Login"
2. Enter email address
3. Click "🔄 Forgot Password?"
4. **Current:** Token displayed in bot
5. **With Email:** Check email for token
6. Enter token and set new password

### **Test Account Linking:**
1. Complete login process
2. **Current:** Check console logs
3. **With Email:** Check email for welcome message
4. Restart bot and use `/start` - should auto-login

## 📋 **Production Checklist**

- [ ] Install nodemailer: `npm install nodemailer`
- [ ] Configure email credentials in `.env`
- [ ] Test email sending with your SMTP provider
- [ ] Uncomment email functionality in code
- [ ] Update email templates with your branding
- [ ] Test password reset flow end-to-end
- [ ] Test welcome email sending
- [ ] Monitor email delivery rates
- [ ] Set up email logging for debugging

## 🚨 **Troubleshooting**

### **Common Issues:**
- **"Authentication failed":** Check app password, not regular password
- **"Connection timeout":** Verify SMTP host and port
- **"Emails not received":** Check spam folder, verify email address
- **"Module not found":** Run `npm install nodemailer` in correct directory

### **Debug Mode:**
Enable detailed logging by adding to your `.env`:
```env
LOG_LEVEL=debug
```

## 🌟 **Advanced Features**

### **Email Templates:**
- Customize HTML templates in the email functions
- Add company branding and styling
- Include dynamic content based on user data

### **Email Service Providers:**
- **SendGrid:** Professional email service
- **Mailgun:** Reliable email API
- **AWS SES:** Scalable email solution
- **Custom SMTP:** Your own email server

The system is designed to be flexible and can easily integrate with any email service provider!
