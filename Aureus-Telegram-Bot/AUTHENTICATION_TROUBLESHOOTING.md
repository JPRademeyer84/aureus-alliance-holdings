# 🔧 Authentication Troubleshooting Guide

## ❌ **Issue: Bot Asking for Registration Instead of Login**

### **Problem Description:**
User has an existing account but the bot is asking them to create a new account instead of allowing login.

### **✅ Solutions:**

#### **1. Use the Switch Button**
- When the bot asks for "Create Password", look for the button:
- **Click: "🔑 Login Instead"**
- This will switch you from registration mode to login mode

#### **2. Use the Reset Command**
- Type: `/reset`
- This clears your session and allows you to start fresh
- Then use `/start` and select "🔑 Login"

#### **3. Manual Reset Process**
1. Type `/start`
2. Select "🔑 Login" (not "📝 Register")
3. Enter your email address
4. Enter your password
5. If you see registration options, click "🔑 Login Instead"

---

## 🔄 **Quick Fix Commands**

### **Reset Your Session:**
```
/reset
```
Clears all temporary data and allows fresh start.

### **Start Fresh:**
```
/start
```
Begin the authentication process from the beginning.

### **Get Help:**
```
/help
/support
```
Access help and support options.

---

## 🎯 **Step-by-Step Login Process**

### **For Existing Users:**
1. **Start:** Type `/start`
2. **Choose Login:** Click "🔑 Login" button
3. **Enter Email:** Type your registered email address
4. **Enter Password:** Type your account password
5. **Success:** Account will be linked automatically

### **If You See Registration Screen:**
1. **Look for:** "🔑 Login Instead" button
2. **Click it:** This switches to login mode
3. **Continue:** Follow normal login process

---

## 🔍 **Common Issues & Solutions**

### **Issue: "Create New Account" Screen**
**Solution:** Click "🔑 Login Instead" button

### **Issue: Stuck in Registration Mode**
**Solution:** Use `/reset` command, then `/start`

### **Issue: "Account Already Exists" Message**
**Solution:** This is correct! Click "🔑 Login Instead"

### **Issue: Bot Not Responding**
**Solution:** 
1. Try `/reset`
2. Wait 30 seconds
3. Try `/start` again

---

## 📧 **Password Issues**

### **Forgot Password:**
1. Start login process
2. Enter your email
3. Click "🔄 Forgot Password?" button
4. Check your email for reset token
5. Enter token and set new password

### **Wrong Password:**
1. Try again with correct password
2. Use "🔄 Forgot Password?" if needed
3. Contact support if still having issues

---

## 🆘 **Still Having Problems?**

### **Contact Support:**
- **Telegram:** @aureusafrica
- **Email:** support@aureusangelalliance.com
- **Command:** `/support` in the bot

### **Provide This Information:**
- Your registered email address
- What screen you're seeing
- What buttons are available
- Any error messages

---

## ✅ **Prevention Tips**

### **For Future Use:**
1. **Always choose "🔑 Login"** if you have an existing account
2. **Use `/reset`** if you get confused
3. **Look for switch buttons** if you're in the wrong mode
4. **Contact support** if you're unsure

### **Account Linking:**
- Once successfully logged in, your Telegram is permanently linked
- Future `/start` commands will auto-login
- No need to enter credentials again unless you logout

---

## 🎯 **Success Indicators**

### **You Know It's Working When:**
- ✅ You see "Login Successful!" message
- ✅ Bot shows "Welcome back, [Your Name]!"
- ✅ You can access `/menu`, `/portfolio`, etc.
- ✅ Auto-login works on future `/start` commands

The authentication system is now more robust with better error handling and user guidance!
