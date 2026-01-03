# Testing RBAC System

## Quick Test Guide

### 1. Reset Database and Seed Data
```bash
php artisan migrate:fresh --seed
```

This will create:
- Admin user: admin@example.com / password
- Regular user: test@example.com / password
- Guest user: guest@example.com / password

### 2. Login and Get Token

#### Login as Admin
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

#### Login as Regular User
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password"
  }'
```

#### Login as Guest
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "guest@example.com",
    "password": "password"
  }'
```

Save the `access_token` from the response.

### 3. Test Permission Checks

#### Test Creating Product (Requires can_create permission)

**As Admin (Should work):**
```bash
curl -X POST http://localhost:8000/api/product \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "name": "Test Product",
    "description": "Test Description",
    "price": 99.99,
    "stock": 10
  }'
```

**As Guest (Should fail with 403):**
```bash
curl -X POST http://localhost:8000/api/product \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_GUEST_TOKEN" \
  -d '{
    "name": "Test Product",
    "description": "Test Description",
    "price": 99.99,
    "stock": 10
  }'
```

Expected response:
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

#### Test Reading Products (Requires can_read permission)

**As Any User (Should work):**
```bash
curl -X GET http://localhost:8000/api/product \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Test Managing Users (Requires Admin role + can_manage_users permission)

**As Admin (Should work):**
```bash
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

**As Regular User (Should fail with 403):**
```bash
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer YOUR_REGULAR_USER_TOKEN"
```

Expected response:
```json
{
  "success": false,
  "message": "You do not have the required role to access this resource."
}
```

#### Test Managing Cameras (Requires can_manage_cameras permission)

**As Admin (Should work):**
```bash
curl -X POST http://localhost:8000/api/admin/cameras \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -d '{
    "name": "Front Door Camera",
    "location": "Front Entrance",
    "connection_type": "USB",
    "usb_path": "/dev/video0",
    "description": "Main entrance monitoring"
  }'
```

**As Guest (Should fail with 403):**
```bash
curl -X POST http://localhost:8000/api/admin/cameras \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_GUEST_TOKEN" \
  -d '{
    "name": "Front Door Camera",
    "location": "Front Entrance",
    "connection_type": "USB",
    "usb_path": "/dev/video0",
    "description": "Main entrance monitoring"
  }'
```

### 4. Check User Permissions via API

Get current user profile with roles:
```bash
curl -X GET http://localhost:8000/api/auth/user-profile \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Testing in Laravel Tinker

```bash
php artisan tinker
```

```php
// Get a user
$user = User::find(1);

// Check role
$user->hasRole('Admin'); // true for admin

// Check permission
$user->hasPermission('can_create'); // true for admin and regular users

// Get all roles
$user->roles;

// Get all permissions
$user->getPermissions();

// Check multiple roles
$user->hasAnyRole(['Admin', 'User']); // true if user has any of these roles
```

### 6. Common Test Scenarios

| Action | Admin | User | Guest | Expected Result |
|--------|-------|------|-------|----------------|
| Login | ✓ | ✓ | ✓ | All can login |
| View Products | ✓ | ✓ | ✓ | All can view |
| Create Product | ✓ | ✓ | ✗ | Guest gets 403 |
| Update Own Product | ✓ | ✓ | ✗ | Guest gets 403 |
| Delete Product | ✓ | ✓ | ✗ | Guest gets 403 |
| View All Users | ✓ | ✗ | ✗ | Only Admin |
| Delete Users | ✓ | ✗ | ✗ | Only Admin |
| Manage Cameras | ✓ | ✓ | ✗ | Guest gets 403 |
| Toggle Camera | ✓ | ✓ | ✗ | Guest gets 403 |

### 7. Expected Error Responses

**401 Unauthorized (No token or invalid token):**
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**403 Forbidden (Missing permission):**
```json
{
  "success": false,
  "message": "You do not have permission to perform this action."
}
```

**403 Forbidden (Missing role):**
```json
{
  "success": false,
  "message": "You do not have the required role to access this resource."
}
```

### 8. Adding Custom Roles/Permissions

Using Laravel Tinker:

```php
// Create a new role
$moderator = Role::create([
    'name' => 'Moderator',
    'description' => 'Can moderate content but not manage users'
]);

// Create permissions for the role
Permission::create([
    'role_id' => $moderator->id,
    'can_create' => true,
    'can_read' => true,
    'can_update' => true,
    'can_delete' => true,
    'can_manage_users' => false,
    'can_manage_roles' => false,
    'can_manage_permissions' => false,
    'can_manage_cameras' => true,
]);

// Assign role to a user
$user = User::find(2);
UserRole::create([
    'user_id' => $user->id,
    'role_id' => $moderator->id,
]);
```

### 9. Debugging Tips

**Check if user has permissions:**
```php
$user = auth()->user();
dd($user->getPermissions());
```

**Check if middleware is applied:**
```bash
php artisan route:list --path=admin
```

**View user with roles and permissions:**
```php
$user = User::with('roles.permissions')->find(1);
dd($user->toArray());
```

### 10. Postman Collection

Import the `yaak.authtestjwt-api-collection.json` file to test all endpoints with pre-configured permission tests.

## Troubleshooting

### Issue: "Method middleware not found"
**Solution:** Make sure Controller base class extends `Illuminate\Routing\Controller` and uses `AuthorizesRequests` trait.

### Issue: Permissions not working
**Solution:** 
1. Check if user has roles assigned: `$user->roles`
2. Check if roles have permissions: `$user->getPermissions()`
3. Verify middleware is registered in `bootstrap/app.php`

### Issue: All requests returning 403
**Solution:** 
1. Check JWT token is valid
2. Ensure user has the required role/permission
3. Verify middleware order in routes

### Issue: Migration error "Foreign key constraint"
**Solution:** Run `php artisan migrate:fresh --seed` to reset database with proper relationships.

