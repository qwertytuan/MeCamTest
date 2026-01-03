<?php

namespace App\Http\Controllers;


class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function products()
    {
        return view('dashboard.products');
    }

    public function productDetail($id)
    {
        return view('dashboard.product-detail', ['productId' => $id]);
    }
}

