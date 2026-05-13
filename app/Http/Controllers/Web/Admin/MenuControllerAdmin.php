<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuControllerAdmin extends Controller
{
  /**
   * Daftar menu (tidak termasuk soft deleted)
   */
  public function index(Request $request)
  {
    $query = Product::with('category')->latest();

    if ($search = $request->input('search')) {
      $query->where('name', 'like', "%{$search}%")
        ->orWhere('description', 'like', "%{$search}%");
    }
    if ($request->input('filter') === 'featured') {
      $query->where('is_signature', true);
    }


    $menus = $query->get();

    return view('admin.menu.index', compact('menus'));
  }

  /**
   * Tampilkan menu yang sudah di-soft delete (trash)
   */
  public function trash(Request $request)
  {
    $query = Product::onlyTrashed()->with('category')->latest();

    if ($search = $request->input('search')) {
      $query->where('name', 'like', "%{$search}%");
    }

    $menus = $query->get();

    return view('admin.menu.trash', compact('menus'));
  }

  public function create()
  {
    $categories = Category::all();
    return view('admin.menu.create', compact('categories'));
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name'          => 'required|string|max:255',
      'description'   => 'required|string',
      'price'         => 'required|numeric|min:0',
      'stock'         => 'required|integer|min:0',
      'id_kategori'   => 'required|exists:categories,id_kategori',
      'main_image'    => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
      'is_signature'  => 'boolean',
      'is_available'  => 'boolean',
    ]);

    $validated['is_signature'] = $request->has('is_signature');
    $validated['is_available'] = $request->has('is_available');
    $validated['slug'] = Str::slug($validated['name']) . '-' . time();

    $product = new Product($validated);

    if ($request->hasFile('main_image')) {
      $product->main_image = $request->file('main_image')->store('menus', 'public');
    }

    $product->save();

    return redirect()->route('admin.menu.index')
      ->with('success', 'Menu berhasil ditambahkan.');
  }

  public function edit(Product $menu)
  {
    $categories = Category::all();
    return view('admin.menu.edit', compact('menu', 'categories'));
  }

  public function update(Request $request, Product $menu)
  {
    $validated = $request->validate([
      'name'          => 'required|string|max:255',
      'description'   => 'required|string',
      'price'         => 'required|numeric|min:0',
      'stock'         => 'required|integer|min:0',
      'id_kategori'   => 'required|exists:categories,id_kategori',
      'main_image'    => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
      'is_signature'  => 'boolean',
      'is_available'  => 'boolean',
    ]);

    $validated['is_signature'] = $request->has('is_signature');
    $validated['is_available'] = $request->has('is_available');

    if ($request->hasFile('main_image')) {
      if ($menu->main_image) {
        Storage::disk('public')->delete($menu->main_image);
      }
      $validated['main_image'] = $request->file('main_image')->store('menus', 'public');
    }

    $menu->update($validated);

    return redirect()->route('admin.menu.index')
      ->with('success', 'Menu berhasil diperbarui.');
  }

  /**
   * Soft delete single menu
   */
  public function destroy(Product $menu)
  {
    $menu->delete();
    return redirect()->route('admin.menu.index')
      ->with('success', 'Menu dipindahkan ke sampah.');
  }

  /**
   * Restore from soft delete
   */
  public function restore($id)
  {
    $menu = Product::withTrashed()->findOrFail($id);
    $menu->restore();

    return redirect()->route('admin.menu.trash')
      ->with('success', 'Menu berhasil dipulihkan.');
  }

  /**
   * Force delete (permanen) dan hapus gambar
   */
  public function forceDelete($id)
  {
    $menu = Product::withTrashed()->findOrFail($id);

    // Cek apakah produk sudah pernah dipesan (ada di order_items)
    $hasOrders = OrderItem::where('id_produk', $menu->id_produk)->exists();

    if ($hasOrders) {
      return redirect()->route('admin.menu.trash')
        ->with('error', '⚠️ Menu "' . $menu->name . '" sudah pernah dipesan, tidak dapat dihapus permanen. Biarkan di sampah atau pulihkan kembali.');
    }

    if ($menu->main_image) {
      Storage::disk('public')->delete($menu->main_image);
    }
    $menu->forceDelete();

    return redirect()->route('admin.menu.trash')
      ->with('success', 'Menu "' . $menu->name . '" berhasil dihapus permanen.');
  }

  /**
   * Bulk soft delete (AJAX)
   */
  public function bulkDelete(Request $request)
  {
    $ids = $request->validate(['ids' => 'required|array'])['ids'];
    Product::whereIn('id_produk', $ids)->delete();

    return response()->json([
      'success' => true,
      'message' => count($ids) . ' menu dipindahkan ke sampah.'
    ]);
  }

  /**
   * Bulk restore (AJAX)
   */
  public function bulkRestore(Request $request)
  {
    $ids = $request->validate(['ids' => 'required|array'])['ids'];
    Product::withTrashed()->whereIn('id_produk', $ids)->restore();

    return response()->json([
      'success' => true,
      'message' => count($ids) . ' menu berhasil dipulihkan.'
    ]);
  }

  /**
   * Bulk force delete (AJAX)
   */
  public function bulkForceDelete(Request $request)
  {
    $ids = $request->validate(['ids' => 'required|array'])['ids'];
    $menus = Product::withTrashed()->whereIn('id_produk', $ids)->get();

    $deleted = 0;
    $skipped = 0;
    $skippedNames = [];

    foreach ($menus as $menu) {
      $hasOrders = OrderItem::where('id_produk', $menu->id_produk)->exists();
      if ($hasOrders) {
        $skipped++;
        $skippedNames[] = $menu->name;
        continue;
      }
      if ($menu->main_image) {
        Storage::disk('public')->delete($menu->main_image);
      }
      $menu->forceDelete();
      $deleted++;
    }

    $message = "$deleted menu berhasil dihapus permanen.";
    if ($skipped > 0) {
      $message .= " $skipped menu tidak dapat dihapus karena sudah pernah dipesan: " . implode(', ', $skippedNames);
    }

    return response()->json([
      'success' => true,
      'message' => $message
    ]);
  }

  // Pada fungsi toggleSignature
  public function toggleSignature(Request $request, Product $menu)
  {
    $menu->is_signature = !$menu->is_signature;
    $menu->save();

    $status = $menu->is_signature ? 'diaktifkan' : 'dinonaktifkan';
    return response()->json([
      'success' => true,
      'message' => "Menu signature {$status}."
    ]);
  }

  // Pada fungsi toggleAvailability
  public function toggleAvailability(Request $request, Product $menu)
  {
    $menu->is_available = !$menu->is_available;
    $menu->save();

    $status = $menu->is_available ? 'tersembunyi' : 'terlihat';
    return response()->json([
      'success' => true,
      'message' => "Status tampilan menu diubah menjadi {$status}."
    ]);
  }
}
