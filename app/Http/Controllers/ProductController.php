<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:products.view', only: ['index', 'show']),
            new Middleware('permission:products.create', only: ['create', 'store']),
            new Middleware('permission:products.update', only: ['edit', 'update']),
            new Middleware('permission:products.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")))
            ->when($request->category, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('products.form', [
            'product' => new Product(['is_active' => true, 'stock' => 0, 'min_stock' => 0]),
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: $this->nextCode();
        $data = $this->handleCostPrice($request, $data);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('products.index')->with('status', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'unit']);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        $data = $this->handleCostPrice($request, $data, $product);

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $rules = [
            'code' => ['nullable', 'string', 'max:50', 'unique:products,code' . ($product ? ',' . $product->id : '')],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ];

        // Stok awal hanya boleh diisi saat membuat produk baru.
        if (! $product) {
            $rules['stock'] = ['required', 'numeric', 'min:0'];
        }

        // Harga modal hanya divalidasi jika user berhak (admin).
        if (auth()->user()->can('reports.finance')) {
            $rules['cost_price'] = ['required', 'numeric', 'min:0'];
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    /** Jaga agar Staff tidak bisa mengubah harga modal. */
    private function handleCostPrice(Request $request, array $data, ?Product $product = null): array
    {
        if (! auth()->user()->can('reports.finance')) {
            // Staff: pertahankan nilai lama (edit) atau 0 (baru).
            $data['cost_price'] = $product?->cost_price ?? 0;
        }

        return $data;
    }

    private function nextCode(): string
    {
        $n = (int) Product::max('id') + 1;

        return 'PRD-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
