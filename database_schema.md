## Go4Rent - Database Schema

This document outlines the database schema for the Go4Rent platform.

### 1. Users (`users`)
Stores information about all users (customers, admins, staff).

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `full_name` (VARCHAR(255))
- `email` (VARCHAR(255), Unique)
- `phone_number` (VARCHAR(50), Unique, Nullable)
- `password` (VARCHAR(255))
- `profile_image_url` (VARCHAR(2048), Nullable)
- `otp` (VARCHAR(10), Nullable) - For email/phone verification
- `otp_expires_at` (TIMESTAMP, Nullable)
- `email_verified_at` (TIMESTAMP, Nullable)
- `phone_verified_at` (TIMESTAMP, Nullable)
- `reward_points` (INT, Default: 0)
- `is_verified_badge` (BOOLEAN, Default: false)
- `verified_badge_request_status` (ENUM('none', 'pending', 'approved', 'rejected'), Default: 'none')
- `remember_token` (VARCHAR(100), Nullable)
- `deleted_at` (TIMESTAMP, Nullable) - For soft deletes
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 2. Roles (`roles`)
Defines user roles.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `name` (VARCHAR(50), Unique) - e.g., 'customer', 'admin', 'staff'
- `guard_name` (VARCHAR(255), Default: 'web') - For spatie/laravel-permission
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 3. User Roles (Pivot Table - `model_has_roles`)
Links users to roles (managed by spatie/laravel-permission).

- `role_id` (Foreign Key to `roles.id`)
- `model_type` (VARCHAR(255))
- `model_id` (BIGINT, Unsigned) - User ID

### 4. Permissions (`permissions`) & Role Permissions (`role_has_permissions`)
(Managed by spatie/laravel-permission if granular permissions are needed beyond roles)

### 5. Equipment Categories (`equipment_categories`)
Stores categories for equipment.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 6. Equipment Category Translations (`equipment_category_translations`)
Stores translations for equipment category names.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `equipment_category_id` (Foreign Key to `equipment_categories.id`)
- `locale` (VARCHAR(10), Index) - e.g., 'en', 'ar'
- `name` (VARCHAR(255))
- UNIQUE (`equipment_category_id`, `locale`)

### 7. Equipment (`equipment`)
Stores details of rentable equipment items.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `equipment_category_id` (Foreign Key to `equipment_categories.id`)
- `barcode_value` (VARCHAR(255), Unique) - Unique identifier for barcode generation
- `images` (JSON, Nullable) - Array of image URLs
- `min_rental_period_hours` (INT, Unsigned)
- `max_rental_period_hours` (INT, Unsigned)
- `rewards_points_acceptable` (BOOLEAN, Default: false)
- `status` (ENUM('available', 'rented', 'in_maintenance', 'unavailable'), Default: 'available')
- `rental_counter` (INT, Unsigned, Default: 0)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 8. Equipment Translations (`equipment_translations`)
Stores translations for equipment name and description.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `equipment_id` (Foreign Key to `equipment.id`)
- `locale` (VARCHAR(10), Index) - e.g., 'en', 'ar'
- `name` (VARCHAR(255))
- `description` (TEXT)
- UNIQUE (`equipment_id`, `locale`)

### 9. Rentals (`rentals`)
Stores information about rental bookings.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `user_id` (Foreign Key to `users.id`)
- `equipment_id` (Foreign Key to `equipment.id`)
- `rental_start_datetime` (TIMESTAMP)
- `rental_end_datetime` (TIMESTAMP)
- `total_amount` (DECIMAL(10, 2))
- `payment_method` (ENUM('thawani', 'wire_transfer', 'reward_points'))
- `payment_status` (ENUM('pending_payment', 'pending_approval', 'paid', 'failed', 'rejected', 'cancelled'), Default: 'pending_payment')
- `status` (ENUM('pending_payment', 'confirmed', 'pickup_pending_signature', 'active', 'completed', 'cancelled', 'damage_reported'), Default: 'pending_payment')
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 10. Contracts (`contracts`)
Stores rental contract details.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `rental_id` (Foreign Key to `rentals.id`, Unique)
- `go4rent_signature_image_url` (VARCHAR(2048)) - Path to the company's stamp/signature image used
- `user_signature_footprint` (JSON, Nullable) - {ip, location_approx, os, device_brand}
- `signed_by_user_at` (TIMESTAMP, Nullable)
- `contract_pdf_url` (VARCHAR(2048), Nullable) - Path to the generated PDF
- `status` (ENUM('company_signed', 'user_signed', 'active', 'completed'), Default: 'company_signed')
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 11. Payments (`payments`)
Stores payment transaction details.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `rental_id` (Foreign Key to `rentals.id`, Nullable - could be for other payment types in future)
- `user_id` (Foreign Key to `users.id`)
- `amount` (DECIMAL(10, 2))
- `payment_method` (ENUM('thawani', 'wire_transfer', 'reward_points'))
- `transaction_id` (VARCHAR(255), Nullable) - From payment gateway or internal ref
- `status` (ENUM('pending', 'successful', 'failed', 'pending_approval', 'approved', 'rejected'), Default: 'pending')
- `wire_transfer_receipt_url` (VARCHAR(2048), Nullable)
- `payment_gateway_response` (JSON, Nullable)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 12. Reward Points History (`reward_points_history`)
Tracks changes in user reward points.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `user_id` (Foreign Key to `users.id`)
- `points_change` (INT) - Positive for earning, negative for spending
- `reason` (VARCHAR(255)) - e.g., 'signup_bonus', 'rental_payment_equip_id_X', 'admin_adjustment'
- `related_rental_id` (Foreign Key to `rentals.id`, Nullable)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 13. Damage Reports (`damage_reports`)
Stores information about reported damages to equipment.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `rental_id` (Foreign Key to `rentals.id`)
- `user_id` (Foreign Key to `users.id`)
- `equipment_id` (Foreign Key to `equipment.id`)
- `description` (TEXT)
- `images` (JSON) - Array of image URLs
- `ai_assessment_details` (TEXT, Nullable)
- `status` (ENUM('submitted', 'under_review', 'ai_assessing', 'assessment_complete', 'resolved', 'closed'), Default: 'submitted')
- `resolution_details` (TEXT, Nullable)
- `reported_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `resolved_at` (TIMESTAMP, Nullable)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 14. Reviews & Ratings (`reviews_ratings`)
Stores user reviews and ratings for equipment.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `user_id` (Foreign Key to `users.id`)
- `equipment_id` (Foreign Key to `equipment.id`)
- `rating` (TINYINT, Unsigned) - 1 to 5
- `review_text` (TEXT, Nullable)
- `is_approved` (BOOLEAN, Default: true) - Admin can unapprove/hide
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 15. Banners (`banners`)
Stores promotional banners for the mobile app.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `image_url_en` (VARCHAR(2048))
- `image_url_ar` (VARCHAR(2048), Nullable)
- `link_url` (VARCHAR(2048), Nullable)
- `display_order` (INT, Default: 0)
- `is_active` (BOOLEAN, Default: true)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 16. Email Templates (`email_templates`)
Stores editable email templates.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `template_name` (VARCHAR(100), Unique) - e.g., 'otp_verification', 'rental_confirmation'
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 17. Email Template Translations (`email_template_translations`)
Stores translations for email templates.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `email_template_id` (Foreign Key to `email_templates.id`)
- `locale` (VARCHAR(10), Index) - e.g., 'en', 'ar'
- `subject` (VARCHAR(255))
- `body_html` (TEXT)
- UNIQUE (`email_template_id`, `locale`)

### 18. Push Notification Templates (`push_notification_templates`)
Stores editable push notification templates (similar to email templates).

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `template_name` (VARCHAR(100), Unique)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 19. Push Notification Template Translations (`push_notification_template_translations`)
Stores translations for push notification templates.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `push_notification_template_id` (Foreign Key to `push_notification_templates.id`)
- `locale` (VARCHAR(10), Index)
- `title` (VARCHAR(255))
- `body` (TEXT)
- UNIQUE (`push_notification_template_id`, `locale`)

### 20. Sent Push Notifications (`sent_push_notifications`)
Logs push notifications that have been sent.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `user_id` (Foreign Key to `users.id`, Nullable - for targeted notifications, or null for broadcast)
- `title` (VARCHAR(255))
- `body` (TEXT)
- `data` (JSON, Nullable) - Additional data sent with notification
- `sent_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `status` (ENUM('sent', 'failed'), Nullable)
- `response` (TEXT, Nullable) - Response from Firebase
- `created_by_admin_id` (Foreign Key to `users.id`, Nullable)
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

### 21. Global Settings (`global_settings`)
Key-value store for global application settings.

- `id` (Primary Key, BIGINT, Unsigned, Auto Increment)
- `setting_key` (VARCHAR(255), Unique)
- `setting_value` (TEXT, Nullable)
- `group` (VARCHAR(50), Nullable, Index) - e.g., 'thawani', 'ai', 'firebase', 'general', 'contract', 'email', 'rewards', 'language'
- `is_translatable` (BOOLEAN, Default: false) - Indicates if value is a JSON of translations
- `created_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP)
- `updated_at` (TIMESTAMP, Default: CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

**Notes:**
- Timestamps (`created_at`, `updated_at`) are standard.
- `deleted_at` for soft deletes will be added to relevant tables like `users`, `equipment` etc. as needed.
- Foreign key constraints, indexes, and specific data type lengths will be refined during implementation.
- Multi-language support for text fields will be handled using translation tables (e.g., `equipment_translations`) linked by a foreign key and locale, or by storing JSON in the main table if using a package like `spatie/laravel-translatable` that supports JSON-based translations.
The translation table approach is shown for clarity.
- `spatie/laravel-permission` package will manage `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` tables.

