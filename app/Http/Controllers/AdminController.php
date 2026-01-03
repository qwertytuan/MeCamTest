<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('role:Admin');
        $this->middleware('permission:can_manage_users');
    }

    public function listUsers(): JsonResponse
    {
        $user = User::all();
        return response()->json(['data' => $user]);
    }

    public function deleteUser(string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        if ($user->is_admin) {
            return response()->json(['error' => 'Cannot delete admin users'], 403);
        }
        if (auth()->id() === $user->id) {
            return response()->json(['error' => 'Cannot delete your own account yet'], 403);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function listSoftDeletedUsers()
    {
        $users = User::onlyTrashed()->get();
        return response()->json(['data' => $users]);
    }

    public function restoreUser(string $id)
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return response()->json(['error' => 'User not found or not deleted'], 404);
        }
        $user->restore();
        $user->products()->onlyTrashed()->restore();
        return response()->json(['message' => 'User restored successfully']);
    }
}
