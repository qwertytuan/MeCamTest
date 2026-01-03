# Role-Based Access Control (RBAC) Implementation

## Overview
This Laravel application now has a complete Role-Based Access Control system with permissions that allows fine-grained control over user actions.

## Database Structure

### Tables:
1. **roles** - Stores role definitions
   - id, name, description, timestamps

2. **user_roles** - Pivot table linking users to roles
   - id, user_id, role_id, timestamps

3. **permissions** - Stores permissions for each role
   - id, role_id, can_create, can_read, can_update, can_delete, can_manage_users, can_manage_roles, can_manage_permissions, can_manage_cameras, timestamps

## Models

### User Model
Added methods:
- `roles()` - Relationship to get user's roles
- `hasRole(string $roleName)` - Check if user has a specific role
- `hasAnyRole(array $roles)` - Check if user has any of the given roles
- `getPermissions()` - Get all permissions from user's roles
- `hasPermission(string $permission)` - Check if user has a specific permission
- `isAdmin()` - Check if user is an admin (backwards compatible)

### Role Model
Added methods:
- `permissions()` - Relationship to get role's permissions

## Middleware

### CheckPermission
Usage: `permission:can_create`

Checks if the authenticated user has a specific permission. Use it to protect routes that require specific permissions.

Example:
```php
Route::post('/products', [ProductController::class, 'store'])
    ->middleware('permission:can_create');
```

### CheckRole
Usage: `role:Admin,User`

Checks if the authenticated user has any of the specified roles. Supports multiple roles.

Example:
```php
Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
    ->middleware('role:Admin');
```

## Controllers with Permission Checks

### ProductController
- `store` - Requires `can_create` permission
- `index`, `show` - Requires `can_read` permission
- `update` - Requires `can_update` permission
- `destroy` - Requires `can_delete` permission

### CameraController
- `store`, `update`, `destroy`, `toggleOnOff` - Requires `can_manage_cameras` permission
- `index`, `show`, `getUserCameras` - Requires `can_read` permission

### AdminController
- All methods - Requires `Admin` role AND `can_manage_users` permission

### CameraAccessController
- All methods - Requires `can_manage_cameras` permission

## Seeded Roles

### Admin Role
- Full access to all permissions
- can_create: ✓
- can_read: ✓
- can_update: ✓
- can_delete: ✓
- can_manage_users: ✓
- can_manage_roles: ✓
- can_manage_permissions: ✓
- can_manage_cameras: ✓

### User Role
- Standard user permissions
- can_create: ✓
- can_read: ✓
- can_update: ✓
- can_delete: ✓
- can_manage_users: ✗
- can_manage_roles: ✗
- can_manage_permissions: ✗
- can_manage_cameras: ✓

### Guest Role
- Limited read-only access
- can_create: ✗
- can_read: ✓
- can_update: ✗
- can_delete: ✗
- can_manage_users: ✗
- can_manage_roles: ✗
- can_manage_permissions: ✗
- can_manage_cameras: ✗

## Seeded Users

1. **Admin User**
   - Email: admin@example.com
   - Password: password
   - Role: Admin

2. **Test User**
   - Email: test@example.com
   - Password: password
   - Role: User

3. **Guest User**
   - Email: guest@example.com
   - Password: password
   - Role: Guest

## Usage Examples

### In Controllers
```php
// Check permission
if (auth()->user()->hasPermission('can_create')) {
    // Allow creation
}

// Check role
if (auth()->user()->hasRole('Admin')) {
    // Admin-specific logic
}

// Check multiple roles
if (auth()->user()->hasAnyRole(['Admin', 'User'])) {
    // Allow access
}
```

### In Routes (api.php)
```php
// Single permission
Route::post('/products', [ProductController::class, 'store'])
    ->middleware(['auth:api', 'permission:can_create']);

// Single role
Route::get('/admin/users', [AdminController::class, 'listUsers'])
    ->middleware(['auth:api', 'role:Admin']);

// Multiple roles
Route::get('/cameras', [CameraController::class, 'index'])
    ->middleware(['auth:api', 'role:Admin,User']);

// Combined permission and role
Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])
    ->middleware(['auth:api', 'role:Admin', 'permission:can_manage_users']);
```

### In Blade Views
```blade
@if(auth()->user()->hasPermission('can_create'))
    <button>Create New</button>
@endif

@if(auth()->user()->hasRole('Admin'))
    <a href="/admin">Admin Panel</a>
@endif
```

## Migration and Seeding

To set up the database with roles and permissions:

```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed

# Or separately
php artisan migrate
php artisan db:seed
```

## API Responses

### Unauthorized (401)
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### Forbidden - Missing Permission (403)
```json
{
    "success": false,
    "message": "You do not have permission to perform this action."
}
```

### Forbidden - Missing Role (403)
```json
{
    "success": false,
    "message": "You do not have the required role to access this resource."
}
```

## Adding New Permissions

To add new permissions:

1. Add column to permissions table migration
2. Update Permission model's `$fillable` and `$casts` arrays
3. Update seeders to set values for new permissions
4. Use in middleware: `permission:your_new_permission`

## Best Practices

1. Always use `auth:api` middleware before role/permission checks
2. Combine role and permission checks for sensitive operations
3. Check ownership in addition to permissions (e.g., users editing their own data)
4. Use descriptive permission names that clearly indicate the action
5. Document which roles have which permissions
6. Regularly audit user roles and permissions

