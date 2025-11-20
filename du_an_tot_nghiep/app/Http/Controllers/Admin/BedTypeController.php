<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BedType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BedTypeController extends Controller
{
    public function index()
    {
        $bedTypes = BedType::orderByDesc('id')->get();
        return view('admin.bed_types.index', compact('bedTypes'));
    }

    public function create()
    {
        return view('admin.bed_types.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'price' => str_replace('.', '', $request->price)
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:bed_types,slug',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $data = $request->only(['name','slug','capacity','price','description','icon']);
        if (empty($data['slug'])) $data['slug'] = Str::slug($data['name']);
        BedType::create($data);

        return redirect()->route('admin.bed-types.index')->with('success','Bed type created.');
    }

    public function edit(BedType $bedType)
    {
        return view('admin.bed_types.edit', compact('bedType'));
    }

    public function update(Request $request, BedType $bedType)
    {
        $request->merge([
            'price' => str_replace('.', '', $request->price)
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:bed_types,slug,'.$bedType->id,
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $data = $request->only(['name','slug','capacity','price','description','icon']);
        if (empty($data['slug'])) $data['slug'] = Str::slug($data['name']);
        $bedType->update($data);

        return redirect()->route('admin.bed-types.index')->with('success','Bed type updated.');
    }

    public function destroy(BedType $bedType)
    {
        $bedType->loaiPhongs()->detach();
        $bedType->phongs()->detach();
        $bedType->delete();

        return back()->with('success','Deleted.');
    }
}
