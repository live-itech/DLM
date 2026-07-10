<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UnitController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:units.view', only: ['index']),
            new Middleware('permission:units.create', only: ['create', 'store']),
            new Middleware('permission:units.update', only: ['edit', 'update']),
            new Middleware('permission:units.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $units = Unit::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('symbol', 'like', "%{$s}%"))
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.form', ['unit' => new Unit()]);
    }

    public function store(Request $request)
    {
        Unit::create($this->validated($request));

        return redirect()->route('units.index')->with('status', 'Satuan berhasil ditambahkan.');
    }

    public function edit(Unit $unit)
    {
        return view('units.form', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $unit->update($this->validated($request));

        return redirect()->route('units.index')->with('status', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'Satuan tidak bisa dihapus karena masih dipakai produk.');
        }

        $unit->delete();

        return back()->with('status', 'Satuan berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:20'],
        ]);
    }
}
