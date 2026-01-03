@extends('layouts.jwt-app')

@section('title', 'Product Details')

@section('content')
<style>
    .product-detail-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .product-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .product-title-section h1 {
        margin-bottom: 10px;
    }

    .product-meta {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-primary {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .product-actions {
        display: flex;
        gap: 10px;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .product-info {
        background: #f9fafb;
        padding: 30px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
    }

    .info-item {
        text-align: center;
    }

    .info-label {
        font-size: 14px;
        color: #666;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }

    .product-description-section {
        margin-bottom: 30px;
    }

    .product-description-section h2 {
        margin-bottom: 15px;
        font-size: 20px;
    }

    .product-description {
        color: #666;
        line-height: 1.8;
        font-size: 15px;
    }

    .product-owner-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 20px;
        border-radius: 10px;
        color: white;
        margin-bottom: 30px;
    }

    .product-owner-section h3 {
        color: white;
        margin-bottom: 10px;
        font-size: 16px;
    }

    .owner-name {
        font-size: 20px;
        font-weight: bold;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: #666;
    }

    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<div id="loadingIndicator" class="loading">
    <div class="spinner"></div>
    <p>Loading product details...</p>
</div>

<div id="productContent" class="product-detail-container" style="display: none;"></div>

@push('scripts')
<script>
    const productId = {{ $productId }};
    const currentUser = getUserData();

    async function loadProduct() {
        const token = getToken();

        if (!token) {
            window.location.href = '{{ route("login") }}';
            return;
        }

        try {
            const product = await apiCall(`/api/product/${productId}`, 'GET');
            renderProduct(product);
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('productContent').style.display = 'block';
        } catch (error) {
            showAlert('Failed to load product. Please try again.', 'danger');
            document.getElementById('loadingIndicator').innerHTML = '<p>Failed to load product. <a href="{{ route("products.web") }}" class="btn btn-primary">Back to Products</a></p>';
        }
    }

    function renderProduct(product) {
        const isMyProduct = product.user_id === currentUser.id;
        const productOwner = product.user.name || 'Unknown';
        const inStock = product.stock > 0;
        const totalValue = parseFloat(product.price) * product.stock;

        const actionsHTML = isMyProduct ? `
            <button onclick="deleteProduct()" class="btn btn-danger">Delete</button>
        ` : '';

        const content = `
            <div class="product-header">
                <div class="product-title-section">
                    <h1>${product.name}</h1>
                    <div class="product-meta">
                        ${isMyProduct ? '<span class="badge badge-primary">Your Product</span>' : ''}
                        ${inStock
                            ? '<span class="badge badge-success">In Stock</span>'
                            : '<span class="badge badge-warning">Out of Stock</span>'
                        }
                    </div>
                </div>
                <div class="product-actions">
                    ${actionsHTML}
                    <a href="{{ route('products.web') }}" class="btn btn-secondary">Back to List</a>
                </div>
            </div>

            <div class="product-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Price</div>
                        <div class="info-value">$${parseFloat(product.price).toFixed(2)}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Stock</div>
                        <div class="info-value">${product.stock}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Value</div>
                        <div class="info-value">$${totalValue.toFixed(2)}</div>
                    </div>
                </div>
            </div>

            ${product.description ? `
                <div class="product-description-section">
                    <h2>Description</h2>
                    <div class="product-description">
                        ${product.description}
                    </div>
                </div>
            ` : ''}

            <div class="product-owner-section">
                <h3>Product Owner</h3>
                <div class="owner-name">${productOwner}</div>
            </div>

            <div style="font-size: 12px; color: #999; text-align: center;">
                <p>Product ID: ${product.id}</p>
            </div>
        `;

        document.getElementById('productContent').innerHTML = content;
    }

    async function deleteProduct() {
        if (!confirm('Are you sure you want to delete this product?')) return;

        try {
            await apiCall(`/api/product/${productId}`, 'DELETE');
            showAlert('Product deleted successfully!', 'success');
            setTimeout(() => {
                window.location.href = '{{ route("products.web") }}';
            }, 1500);
        } catch (error) {
            showAlert(error.message || 'Failed to delete product. Please try again.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadProduct();
    });
</script>
@endpush
@endsection

