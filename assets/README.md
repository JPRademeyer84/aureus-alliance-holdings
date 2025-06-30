# Assets Directory

This directory contains user-uploaded files and system assets.

## Directory Structure

```
assets/
├── kyc/                    # KYC document uploads
│   ├── .htaccess          # Security configuration
│   └── [user_documents]   # User KYC documents
├── profile/               # Profile images (future)
├── marketing/             # Marketing assets (future)
└── README.md             # This file
```

## KYC Documents (`/kyc/`)

### File Naming Convention
- Format: `{user_id}_{document_type}_{timestamp}.{extension}`
- Example: `1_passport_1703123456.jpg`

### Supported Document Types
- `passport` - Passport documents
- `drivers_license` - Driver's license
- `national_id` - National ID cards
- `proof_of_address` - Proof of address documents

### Supported File Formats
- **Images**: JPG, JPEG, PNG
- **Documents**: PDF
- **Max Size**: 5MB per file

### Security Features
- Direct access denied via .htaccess
- Only accessible through authorized API endpoints
- Files stored with unique names to prevent conflicts
- Directory browsing disabled

### API Access
KYC documents can only be accessed through:
- Upload: `POST /api/users/enhanced-profile.php` (action: upload_kyc)
- List: `GET /api/users/enhanced-profile.php` (action: kyc_documents)
- Admin review endpoints (future implementation)

## Security Notes

1. **No Direct Access**: Files cannot be accessed directly via URL
2. **API Only**: All file operations must go through authorized API endpoints
3. **User Isolation**: Users can only access their own documents
4. **Admin Review**: KYC documents require admin approval
5. **Audit Trail**: All uploads and access logged in database

## Future Expansions

- Profile images in `/profile/` directory
- Marketing assets in `/marketing/` directory
- Document thumbnails and previews
- Automated document processing
- Integration with KYC verification services
