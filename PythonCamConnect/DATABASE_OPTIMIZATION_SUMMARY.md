# Database Schema Optimization Summary

## Overview
The database schema has been optimized to remove the separate Admin table and implement a comprehensive Role-Based Access Control (RBAC) system using a unified User table with roles and permissions.

## Key Changes

### 1. **Removed Admin Table**
- The separate `Admin` table has been completely removed
- Admin functionality is now handled through the User table with `is_admin` flag and role-based permissions

### 2. **Enhanced User Table (Table 1)**
Added new fields to support RBAC and Laravel conventions:
- `avatar_url` - User profile picture URL
- `is_admin` - Boolean flag for quick admin identification
- `email_verified_at` - Email verification timestamp
- `remember_token` - For "remember me" functionality
- `deleted_at` - Soft delete support
- Added indexes for `is_admin`, `is_active`, and `deleted_at`

### 3. **New RBAC Tables**

#### **Role Table (Table 2)**
- Defines system roles (Admin, Manager, User, etc.)
- Fields: `role_id`, `name`, `description`, `created_at`, `updated_at`
- Indexed on `name` for fast lookups

#### **Permission Table (Table 3)**
- Associates permissions with roles
- Granular permissions:
  - `can_create`, `can_read`, `can_update`, `can_delete`
  - `can_manage_users`, `can_manage_roles`, `can_manage_permissions`
  - `can_manage_cameras`
- One-to-one relationship with Role (UNIQUE constraint on `role_id`)

#### **UserRole Table (Table 4)**
- Junction table for many-to-many User-Role relationship
- Supports multiple roles per user
- UNIQUE constraint prevents duplicate role assignments
- Cascading deletes maintain referential integrity

### 4. **Updated Foreign Key References**
All tables that previously referenced `admin_id` now reference `user_id`:

#### **Camera Table (Table 5)**
- Changed `added_by_admin_id` → `added_by_user_id`
- Added `websocket_url` field for real-time streaming
- Added `is_active` boolean field
- Foreign key now references `User(user_id)`

#### **UserCameraAccess Table (Table 6)**
- Changed `granted_by_admin_id` → `granted_by_user_id`
- Foreign key now references `User(user_id)`

#### **AIModel Table (Table 8)**
- Changed `created_by_admin_id` → `created_by_user_id`
- Foreign key now references `User(user_id)`

#### **CameraAIModel Table (Table 9)**
- Removed `requested_by_admin_id` field entirely
- Kept only `requested_by_user_id` for tracking requests
- Simplified authorization model

#### **StreamShare Table (Table 10)**
- Changed `shared_by_admin_id` → `shared_by_user_id`
- Foreign key now references `User(user_id)`

#### **ActivityLog Table (Table 11)**
- Removed `admin_id` field
- Consolidated to single `user_id` field for all user actions
- Added new action types for RBAC operations:
  - `ROLE_ASSIGNED`, `ROLE_REVOKED`
  - `PERMISSION_CHANGED`
  - `USER_CREATED`, `USER_UPDATED`, `USER_DELETED`

### 5. **Standardization**
- All tables now include `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`
- Consistent indexing strategy across all tables
- Proper foreign key constraints with CASCADE/SET NULL behavior

## Benefits of This Design

### 1. **Simplified Architecture**
- Single user authentication system
- No need to maintain separate admin credentials
- Unified user management

### 2. **Flexible Access Control**
- Users can have multiple roles simultaneously
- Fine-grained permissions per role
- Easy to add new roles and permissions without schema changes

### 3. **Better Scalability**
- Role-based system scales better than boolean flags
- New permission types can be added to Permission table
- Supports complex organizational hierarchies

### 4. **Audit Trail**
- ActivityLog tracks all user actions (including admins)
- Complete history of role and permission changes
- Better compliance and security monitoring

### 5. **Laravel Integration**
- Schema aligns perfectly with existing Laravel models:
  - `User`, `Role`, `Permission`, `UserRole` models already implemented
  - Eloquent relationships match database structure
  - Supports soft deletes (`deleted_at`)

## Migration Path

If you need to migrate from the old schema:

```sql
-- 1. Migrate existing Admin data to User table
INSERT INTO User (username, password_hash, email, full_name, is_admin, created_at, updated_at)
SELECT username, password_hash, email, full_name, 1 as is_admin, created_at, updated_at
FROM Admin;

-- 2. Create Admin role
INSERT INTO Role (name, description) VALUES ('Admin', 'System Administrator with full access');

-- 3. Assign Admin role to migrated admin users
INSERT INTO UserRole (user_id, role_id)
SELECT u.user_id, r.role_id 
FROM User u, Role r 
WHERE u.is_admin = 1 AND r.name = 'Admin';

-- 4. Create Admin permissions
INSERT INTO Permission (role_id, can_create, can_read, can_update, can_delete, 
    can_manage_users, can_manage_roles, can_manage_permissions, can_manage_cameras)
SELECT role_id, 1, 1, 1, 1, 1, 1, 1, 1
FROM Role WHERE name = 'Admin';
```

## Database Tables Overview

1. **User** - Central authentication and user data
2. **Role** - System roles definition
3. **Permission** - Role-based permissions
4. **UserRole** - User-Role assignments (many-to-many)
5. **Camera** - Camera device information
6. **UserCameraAccess** - User-specific camera access rights
7. **UserCameraSettings** - User-specific camera preferences
8. **AIModel** - AI model configurations
9. **CameraAIModel** - Camera-AI model associations
10. **StreamShare** - Shared stream tokens and access
11. **ActivityLog** - Comprehensive audit trail
12. **DetectionEvent** - AI detection event records

## Security Considerations

1. **Password Security**: Uses `password_hash` (should use bcrypt/argon2)
2. **Soft Deletes**: User data preserved with `deleted_at` timestamp
3. **Token Security**: 
   - `remember_token` for session persistence
   - `share_token` for stream sharing
4. **Access Control**: Three-level camera access (`can_view`, `can_control`, `can_configure`)
5. **Audit Trail**: Complete logging of all user actions
6. **Cascading Deletes**: Proper cleanup of related records

## Next Steps

1. Ensure Laravel migrations match this schema
2. Update seeder files to create default roles and permissions
3. Test RBAC middleware with new structure
4. Update API documentation to reflect unified user system
5. Implement migration script if transitioning from old schema

