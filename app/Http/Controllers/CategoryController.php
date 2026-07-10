<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:categories.view', only: ['index']),
            new Middleware('permission:categories.create', only: ['create', 'store']),
            new Middleware('permission:categories.update', only: ['edit', 'update']),
            new Middleware('permission:categories.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.form', ['category' => new Category()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Category::create($data);

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih dipakai produk.');
        }

        $category->delete();

        return back()->with('status', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name' . ($category ? ',' . $category->id : '')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
