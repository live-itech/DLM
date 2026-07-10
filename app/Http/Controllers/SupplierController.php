<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:suppliers.view', only: ['index']),
            new Middleware('permission:suppliers.create', only: ['create', 'store']),
            new Middleware('permission:suppliers.update', only: ['edit', 'update']),
            new Middleware('permission:suppliers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.form', ['supplier' => new Supplier(['payment_term_days' => 0, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: $this->nextCode();
        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('status', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validated($request, $supplier));

        return redirect()->route('suppliers.index')->with('status', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('status', 'Supplier berhasil dihapus.');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:suppliers,code' . ($supplier ? ',' . $supplier->id : '')],
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'payment_term_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function nextCode(): string
    {
        $n = (int) Supplier::max('id') + 1;

        return 'SUP-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
