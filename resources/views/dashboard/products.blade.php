@extends('layouts.jwt-app')

@section('title', 'Products')

@section('content')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .product-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        transition: all 0.3s;
        background: white;
    }

    .product-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .product-card.my-product {
        border-color: #667eea;
        background: #f9fafb;
    }

    .product-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 15px;
    }

    .product-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .product-owner {
        font-size: 12px;
        color: #667eea;
        font-weight: 500;
    }

    .product-description {
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
        line-height: 1.5;
    }

    .product-details {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        padding: 10px;
        background: white;
        border-radius: 5px;
    }

    .product-detail {
        text-align: center;
    }

    .product-detail-label {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }

    .product-detail-value {
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }

    .product-actions {
        display: flex;
        gap: 8px;
    }

    .btn-sm {
        padding: 8px 16px;
        font-size: 13px;
    }

    .btn-info {
        background: #3b82f6;
        color: white;
    }

    .btn-info:hover {
        background: #2563eb;
    }

    .btn-warning {
        background: #f59e0b;
        color: white;
    }

    .btn-warning:hover {
        background: #d97706;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
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

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
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

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }

    .modal.show {
        display: flex;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        max-width: 500px;
        width: 90%;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-family: inherit;
    }

    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        margin-top: 30px;
        padding: 20px;
        flex-wrap: wrap;
    }

    .pagination-btn {
        padding: 10px 20px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .pagination-btn:hover:not(.disabled) {
        background: #5568d3;
        transform: translateY(-1px);
    }

    .pagination-btn.disabled {
        background: #ccc;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .pagination-pages {
        display: flex;
        gap: 5px;
        align-items: center;
    }

    .pagination-number {
        padding: 8px 12px;
        background: white;
        color: #667eea;
        border: 1px solid #667eea;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s;
        min-width: 40px;
    }

    .pagination-number:hover {
        background: #f0f0f0;
    }

    .pagination-number.active {
        background: #667eea;
        color: white;
        font-weight: bold;
    }

    .pagination-ellipsis {
        padding: 0 8px;
        color: #666;
    }

    .pagination-info {
        text-align: center;
        color: #666;
        font-size: 14px;
        margin-top: 10px;
        width: 100%;
    }
</style>

<div class="page-header">
    <h1>Products</h1>
    <button onclick="showAddProductModal()" class="btn btn-primary">+ Add New Product</button>
</div>

<div id="loadingIndicator" class="loading">
    <div class="spinner"></div>
    <p>Loading products...</p>
</div>

<div id="productsContainer" style="display: none;"></div>
<div id="paginationContainer"></div>

<!-- Add/Edit Product Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Add New Product</h2>
        <form id="productForm">
            <input type="hidden" id="productId">
            <div class="form-group">
                <label for="productName">Product Name *</label>
                <input type="text" id="productName" required>
            </div>
            <div class="form-group">
                <label for="productDescription">Description</label>
                <textarea id="productDescription"></textarea>
            </div>
            <div class="form-group">
                <label for="productPrice">Price ($) *</label>
                <input type="number" id="productPrice" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="productStock">Stock Quantity *</label>
                <input type="number" id="productStock" min="0" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Product</button>
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let allProducts = [];
    let currentPage = 1;
    let lastPage = 1;
    let totalProducts = 0;
    const currentUser = getUserData();

    async function loadProducts(page = 1) {
        const token = getToken();

        if (!token) {
            window.location.href = '{{ route("login") }}';
            return;
        }

        // Show loading indicator
        if (page === 1) {
            document.getElementById('loadingIndicator').style.display = 'block';
            document.getElementById('productsContainer').style.display = 'none';
        }

        try {
            const response = await apiCall(`/api/product?page=${page}`, 'GET');
            if (response.status === 429) {
                throw new Error('Rate limit exceeded. Please try again later.');
            }

            // Handle paginated response
            allProducts = response.data || response; // Support both paginated and non-paginated
            currentPage = response.current_page || 1;
            lastPage = response.last_page || 1;
            totalProducts = response.total || allProducts.length;

            renderProducts();
            renderPagination(response);

            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('productsContainer').style.display = 'block';
        } catch (error) {
            showAlert(`Failed to load products.${error}`, 'danger');
            document.getElementById('loadingIndicator').innerHTML = '<p>Failed to load products. <button onclick="loadProducts(1)" class="btn btn-primary">Retry</button></p>';
        }
    }

    function renderPagination(paginationData) {
        const container = document.getElementById('paginationContainer');

        if (!paginationData.last_page || paginationData.last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '<div class="pagination">';

        // Previous button
        if (paginationData.prev_page_url) {
            paginationHTML += `<button onclick="loadProducts(${currentPage - 1})" class="pagination-btn">← Previous</button>`;
        } else {
            paginationHTML += `<button class="pagination-btn disabled" disabled>← Previous</button>`;
        }

        // Page info and numbers
        paginationHTML += `<div class="pagination-pages">`;

        // Show page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(lastPage, currentPage + 2);

        if (startPage > 1) {
            paginationHTML += `<button onclick="loadProducts(1)" class="pagination-number">1</button>`;
            if (startPage > 2) {
                paginationHTML += `<span class="pagination-ellipsis">...</span>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
            paginationHTML += `<button onclick="loadProducts(${i})" class="pagination-number ${activeClass}">${i}</button>`;
        }

        if (endPage < lastPage) {
            if (endPage < lastPage - 1) {
                paginationHTML += `<span class="pagination-ellipsis">...</span>`;
            }
            paginationHTML += `<button onclick="loadProducts(${lastPage})" class="pagination-number">${lastPage}</button>`;
        }

        paginationHTML += `</div>`;

        // Next button
        if (paginationData.next_page_url) {
            paginationHTML += `<button onclick="loadProducts(${currentPage + 1})" class="pagination-btn">Next →</button>`;
        } else {
            paginationHTML += `<button class="pagination-btn disabled" disabled>Next →</button>`;
        }

        paginationHTML += '</div>';
        paginationHTML += `<div class="pagination-info">Showing ${paginationData.from || 0} to ${paginationData.to || 0} of ${paginationData.total || 0} products</div>`;

        container.innerHTML = paginationHTML;
    }

    function renderProducts() {
        const container = document.getElementById('productsContainer');

        if (allProducts.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <h3>No Products Found</h3>
                    <p>Be the first to add a product!</p>
                    <button onclick="showAddProductModal()" class="btn btn-primary" style="margin-top: 20px;">Add Your First Product</button>
                </div>
            `;
            document.getElementById('paginationContainer').innerHTML = '';
            return;
        }

        const productsHTML = allProducts.map(product => {
            const isMyProduct = product.user_id === currentUser.id;
            const inStock = product.stock > 0;

            return `
                <div class="product-card ${isMyProduct ? 'my-product' : ''}">
                    <div class="product-header">
                        <div>
                            <div class="product-title">${product.name}</div>
                            ${isMyProduct
                                ? '<span class="product-owner">Your Product</span>'
                                : `<span class="badge badge-primary">${product.user.name || 'Unknown'}</span>`
                            }
                        </div>
                        ${inStock
                            ? '<span class="badge badge-success">In Stock</span>'
                            : '<span class="badge badge-warning">Out of Stock</span>'
                        }
                    </div>

                    ${product.description ? `
                        <div class="product-description">
                            ${product.description.substring(0, 100)}${product.description.length > 100 ? '...' : ''}
                        </div>
                    ` : ''}

                    <div class="product-details">
                        <div class="product-detail">
                            <div class="product-detail-label">Price</div>
                            <div class="product-detail-value">$${parseFloat(product.price).toFixed(2)}</div>
                        </div>
                        <div class="product-detail">
                            <div class="product-detail-label">Stock</div>
                            <div class="product-detail-value">${product.stock}</div>
                        </div>
                    </div>

                    <div class="product-actions">
                        <a href="/products/${product.id}" class="btn btn-info btn-sm">View</a>
                        ${isMyProduct ? `
                            <button onclick="editProduct(${product.id})" class="btn btn-warning btn-sm">Edit</button>
                            <button onclick="deleteProduct(${product.id})" class="btn btn-danger btn-sm">Delete</button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = `<div class="product-grid">${productsHTML}</div>`;
    }

    function showAddProductModal() {
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.getElementById('productForm').reset();
        document.getElementById('productId').value = '';
        document.getElementById('productModal').classList.add('show');
    }

    function editProduct(id) {
        const product = allProducts.find(p => p.id === id);
        if (!product) return;

        document.getElementById('modalTitle').textContent = 'Edit Product';
        document.getElementById('productId').value = product.id;
        document.getElementById('productName').value = product.name;
        document.getElementById('productDescription').value = product.description || '';
        document.getElementById('productPrice').value = product.price;
        document.getElementById('productStock').value = product.stock;
        document.getElementById('productModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('productModal').classList.remove('show');
    }

    document.getElementById('productForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const productId = document.getElementById('productId').value;
        const data = {
            name: document.getElementById('productName').value,
            description: document.getElementById('productDescription').value,
            price: parseFloat(document.getElementById('productPrice').value),
            stock: parseInt(document.getElementById('productStock').value)
        };

        try {
            if (productId) {
                await apiCall(`/api/product/${productId}`, 'PUT', data);
                showAlert('Product updated successfully!', 'success');
            } else {
                await apiCall('/api/product', 'POST', data);
                showAlert('Product created successfully!', 'success');
            }

            closeModal();
            await loadProducts(currentPage); // Reload current page
        } catch (error) {
            showAlert(error.message || 'Failed to save product. Please try again.', 'danger');
        }
    });

    async function deleteProduct(id) {
        if (!confirm('Are you sure you want to delete this product?')) return;

        try {
            await apiCall(`/api/product/${id}`, 'DELETE');
            if(apiCall.status === 204){
            showAlert('Product deleted successfully!', 'success');
            }
            await loadProducts(currentPage); // Reload current page
        } catch (error) {
            showAlert(error.message || 'Failed to delete product. Please try again.', 'danger');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadProducts(1); // Load first page on initialization
    });
</script>
@endpush
@endsection

