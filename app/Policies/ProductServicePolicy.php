<?php

namespace App\Policies;

use App\Models\ProductService;
use App\Models\User;

class ProductServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('products_services.view_any');
    }

    public function view(User $user, ?ProductService $productService = null): bool
    {
        return $user->can('products_services.view');
    }

    public function create(User $user): bool
    {
        return $user->can('products_services.create');
    }

    public function update(User $user, ?ProductService $productService = null): bool
    {
        return $user->can('products_services.update');
    }

    public function delete(User $user, ProductService $productService): bool
    {
        return $user->can('products_services.delete');
    }

    public function restore(User $user, ProductService $productService): bool
    {
        return $user->can('products_services.restore');
    }

    public function forceDelete(User $user, ProductService $productService): bool
    {
        return $user->can('products_services.force_delete');
    }
}
