<?php

namespace App\Http\Controllers\Web\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
  public function index()
  {
    $userId = Auth::id();

    $carts = Cart::with('product')
      ->where('id_users', $userId)
      ->get()
      ->map(function ($cart) {
        if (!$cart->product || !$cart->product->exists) {
          $cart->product = (object) [
            'name' => 'Produk tidak ditemukan',
            'price' => 0,
            'stock' => 0,
            'image' => 'default.jpg',
          ];
          $cart->stock_sufficient = false;
        } else {
          $cart->stock_sufficient = $cart->product->stock >= $cart->quantity;
        }
        $cart->subtotal = ($cart->product->price ?? 0) * $cart->quantity;
        return $cart;
      });

    $total = $carts->sum('subtotal');

    return view('user.cart', [
      'theme' => 'light',
      'carts' => $carts,
      'total' => $total,
    ]);
  }

  // Tambahkan method ini di dalam CartController
  public function add(Request $request)
  {
    // Validasi input dari AJAX
    $request->validate([
      'id_produk' => 'required|exists:products,id_produk',
      'quantity'  => 'integer|min:1'
    ]);

    $userId = Auth::id();
    $productId = $request->id_produk;
    $quantity = $request->input('quantity', 1);

    // Cek stok produk
    $product = \App\Models\Product::find($productId);
    if (!$product || $product->stock < $quantity) {
      return response()->json([
        'success' => false,
        'message' => 'Maaf, stok produk tidak mencukupi atau produk tidak ditemukan.'
      ], 400);
    }

    // Cek apakah produk ini sudah ada di keranjang user
    $cartItem = Cart::where('id_users', $userId)
      ->where('id_produk', $productId)
      ->first();

    if ($cartItem) {
      // Cek stok lagi jika diakumulasikan
      if ($product->stock < ($cartItem->quantity + $quantity)) {
        return response()->json([
          'success' => false,
          'message' => 'Maaf, total jumlah di keranjang melebihi stok yang tersedia.'
        ], 400);
      }
      $cartItem->quantity += $quantity;
      $cartItem->save();
    } else {
      Cart::create([
        'id_users'  => $userId,
        'id_produk' => $productId,
        'quantity'  => $quantity,
      ]);
    }

    $cartCount = Cart::where('id_users', $userId)->count();

    return response()->json([
      'success' => true,
      'message' => 'Produk berhasil ditambahkan ke keranjang.',
      'cart_count' => $cartCount
    ]);
  }

  /**
   * Update Kuantitas Item di Keranjang (AJAX)
   */
  public function updateQuantity(Request $request, $id)
  {
    $request->validate([
      'quantity' => 'required|integer|min:1'
    ]);

    // Cari item berdasarkan ID keranjang dan pastikan milik user yang login
    $cartItem = Cart::where('id_keranjang', $id)
      ->where('id_users', Auth::id())
      ->first();

    if (!$cartItem) {
      return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
    }

    $cartItem->quantity = $request->quantity;
    $cartItem->save();

    return response()->json([
      'success' => true,
      'message' => 'Kuantitas berhasil diperbarui'
    ]);
  }

  /**
   * Update Catatan Item di Keranjang (AJAX)
   */
  public function updateNote(Request $request, $id)
  {
    // Validasi opsional, jika notes boleh kosong (nullable)
    $request->validate([
      'notes' => 'nullable|string|max:255'
    ]);

    $cartItem = Cart::where('id_keranjang', $id)
      ->where('id_users', Auth::id())
      ->first();

    if (!$cartItem) {
      return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
    }

    $cartItem->notes = $request->notes;
    $cartItem->save();

    return response()->json([
      'success' => true,
      'message' => 'Catatan berhasil disimpan'
    ]);
  }

  /**
   * Hapus Item dari Keranjang (AJAX)
   */
  public function destroy($id)
  {
    $cartItem = Cart::where('id_keranjang', $id)
      ->where('id_users', Auth::id())
      ->first();

    if (!$cartItem) {
      return response()->json(['success' => false, 'message' => 'Item tidak ditemukan'], 404);
    }

    $cartItem->delete();

    return response()->json([
      'success' => true,
      'message' => 'Item berhasil dihapus'
    ]);
  }

  public function checkout(Request $request)
  {
    $cartIds = $request->cart_ids;
    $userId = Auth::id();

    $cartItems = Cart::whereIn('id_keranjang', $cartIds)
      ->where('id_users', $userId)
      ->with('product')
      ->get();

    if ($cartItems->isEmpty()) {
      return response()->json(['success' => false, 'message' => 'Pilih menu dulu!'], 400);
    }

    DB::beginTransaction();
    try {
      $totalHarga = 0;
      foreach ($cartItems as $item) {
        $totalHarga += ($item->product->price * $item->quantity);
      }

      $todayOrderCount = Order::whereDate('created_at', today())->count();
      $newQueueNumber = 'A-' . str_pad($todayOrderCount + 1, 3, '0', STR_PAD_LEFT);

      $order = Order::create([
        'id_users' => $userId,
        'order_code' => 'SC-' . strtoupper(Str::random(6)),
        'queue_number' => $newQueueNumber,
        'subtotal' => $totalHarga,
        'total' => $totalHarga,
        'payment_status' => 'pending',
        'order_status' => 'pending_confirmation',
      ]);

      foreach ($cartItems as $item) {
        if ($item->product->stock < $item->quantity) {
          throw new \Exception("Stok {$item->product->name} tidak mencukupi.");
        }

        // Kurangi stok produk secara otomatis di database
        $item->product->decrement('stock', $item->quantity);

        OrderItem::create([
          'id_pesanan' => $order->id_pesanan,
          'id_produk' => $item->id_produk,
          'product_name_snapshot' => $item->product->name,
          'unit_price' => $item->product->price,
          'quantity' => $item->quantity,
          'subtotal' => $item->product->price * $item->quantity,
          'notes' => $item->notes,
        ]);
      }

      Cart::whereIn('id_keranjang', $cartIds)->delete();

      // Notifikasi Pesanan Berhasil Dibuat
      Notification::create([
        'id_users' => $userId,
        'title' => 'Pesanan Berhasil Dibuat!',
        'message' => "Pesanan kamu dengan nomor {$order->order_code} telah kami terima dan menunggu konfirmasi. Terima kasih!",
        'type' => 'system',
        'is_read' => false,
      ]);

      DB::commit();

      return response()->json([
        'success' => true,
        'order_code' => $order->order_code,
        'cart_count' => Cart::where('id_users', $userId)->count()
      ]);
    } catch (\Exception $e) {
      DB::rollback();
      return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
    }
  }
}
