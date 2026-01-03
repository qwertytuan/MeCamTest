<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:can_create')->only(['store']);
        $this->middleware('permission:can_read')->only(['index', 'show']);
        $this->middleware('permission:can_update')->only(['update']);
        $this->middleware('permission:can_delete')->only(['destroy']);
    }

    public function index(): JsonResponse
    {
        //user's products first
        $products = Product::where('user_id', auth()->id())
            ->orWhere('user_id', '!=', auth()->id())
            ->orderByRaw('user_id = ? DESC', [auth()->id()])
            ->orderBy('created_at', 'desc')
            ->with('user:id,name')
            ->paginate(15);
        return response()->json($products);
    }
    public function store(Request $request): JsonResponse
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'user_id' => auth()->id(),
        ]);

        return response()->json($product, 201);
    }
    public function show(Product $product): JsonResponse
    {
        $product = Product::findOrFail($product->id)->with('user:id,name')->first();
        return response()->json($product);
    }
    public function update(Request $request, Product $product): JsonResponse
    {
        // Check if user is the owner of the product
        if (auth()->id() !== $product->user_id) {
            return response()->json(['error' => 'Forbidden - You can only update your own products'], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric',
            'stock' => 'sometimes|required|integer',
        ]);

        $product->update($request->only(['name', 'description', 'price', 'stock']));

        return response()->json($product);
    }
    public function destroy(Product $product): JsonResponse
    {
        // Check if user is the owner of the product
        if (auth()->id() !== $product->user_id) {
            return response()->json(['error' => 'Forbidden - You can only delete your own products'], 403);
        }

        if ($product->deleted_at !== null) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
