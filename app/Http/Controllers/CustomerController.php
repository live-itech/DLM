<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:customers.view', only: ['index']),
            new Middleware('permission:customers.create', only: ['create', 'store']),
            new Middleware('permission:customers.update', only: ['edit', 'update']),
            new Middleware('permission:customers.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $customers = Customer::query()
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer(['payment_term_days' => 0, 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: $this->nextCode();
        Customer::create($data);

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request, $customer));

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return back()->with('status', 'Pelanggan berhasil dihapus.');
    }

    private function validated(Request $request, ?Customer $customer = null): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:customers,code' . ($customer ? ',' . $customer->id : '')],
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
        $n = (int) Customer::max('id') + 1;

        return 'CUST-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
    }
}
