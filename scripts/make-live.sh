#!/bin/bash

echo "========================================"
echo "   MAKING YOUR SITE LIVE ON INTERNET"
echo "========================================"
echo

echo "Step 1: Checking if your React app is running..."
echo "Please make sure you have run 'npm run dev' in another terminal"
echo

echo "Step 2: Installing ngrok (if not already installed)..."
npm install -g ngrok
echo

echo "Step 3: Creating public tunnel..."
echo "Your site will be accessible from anywhere in the world!"
echo
echo "Starting ngrok tunnel on port 5173..."
echo
echo "========================================"
echo "  YOUR SITE WILL BE LIVE AT THE URL BELOW"
echo "========================================"
echo

ngrok http 5173
