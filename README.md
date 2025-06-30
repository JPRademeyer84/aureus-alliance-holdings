# Aureus Angel Alliance

## Project info

A React-based investment platform with MySQL database backend.

## How to run this project locally

**Prerequisites**

- Node.js & npm installed - [install with nvm](https://github.com/nvm-sh/nvm#installing-and-updating)
- XAMPP with MySQL and PHP
- Local MySQL database named `aureus_angels`

**Setup Steps**

```sh
# Step 1: Clone the repository
git clone <YOUR_GIT_URL>

# Step 2: Navigate to the project directory
cd <YOUR_PROJECT_NAME>

# Step 3: Install the necessary dependencies
npm i

# Step 4: Start XAMPP and ensure MySQL and Apache are running

# Step 5: Create the database 'aureus_angels' in phpMyAdmin

# Step 6: Start the development server
npm run dev
```

**Database Setup**

1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Create a new database named `aureus_angels`
3. The application will automatically create the required tables on first run

## What technologies are used for this project?

This project is built with:

- Vite
- TypeScript
- React
- shadcn-ui
- Tailwind CSS
- MySQL (Local database)
- PHP (Backend API)
- XAMPP (Local development environment)

## Project Structure

- `/src` - React frontend application
- `/api` - PHP backend API endpoints
- `/database` - MySQL database schema and setup scripts

## Features

- Investment package management
- Wallet connection and management
- Admin dashboard
- Investment processing
- Local MySQL database integration

Read more here: [Setting up a custom domain](https://docs.lovable.dev/tips-tricks/custom-domain#step-by-step-guide)
