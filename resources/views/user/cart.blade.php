@extends('layouts.app')

@section('title', 'Keranjang Belanja')

@section('content')
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 md:py-8" x-data="cartApp({{ Js::from($carts) }})">

    <div class="flex flex-col lg:flex-row gap-4 md:gap-6 lg:gap-8 mt-8 sm:mt-10 lg:mt-12">

      {{-- Sidebar Component --}}
      <div class="w-full lg:w-80 flex-shrink-0">
        <x-sidebar />
      </div>

      {{-- Main Content --}}
      <div class="flex-1 space-y-4 md:space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-[#3E1E04]/10 p-4 sm:p-6 relative">

          {{-- Loading Overlay --}}
          <div x-show="isLoading"
            class="absolute inset-0 bg-white/60 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"
            style="display: none;">
            <i class="fas fa-spinner fa-spin text-2xl sm:text-3xl text-[#BC430D]"></i>
          </div>

          {{-- Header --}}
          <header class="mb-4 sm:mb-6">
            <h2 class="text-lg sm:text-xl font-bold text-[#3E1E04] font-primary">Keranjang Belanja</h2>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 font-secondary">Pilih menu yang ingin kamu pesan sekarang.</p>
          </header>

          {{-- Pengecekan Kondisi Keranjang --}}
          <template x-if="items.length > 0">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-6">

              {{-- Daftar Pesanan (Kiri) --}}
              <div class="flex-1 space-y-3 sm:space-y-4 min-w-0">

                {{-- Aksi Massal: Pilih Semua & Hapus Terpilih --}}
                <div class="flex justify-between items-center pb-3 sm:pb-4 border-b border-gray-100">
                  <label class="flex items-center gap-2 sm:gap-3 cursor-pointer group">
                    <input type="checkbox" :checked="allSelected" @change="toggleAll()"
                      class="w-4 h-4 sm:w-5 sm:h-5 text-[#BC430D] bg-gray-100 border-gray-300 rounded focus:ring-[#BC430D] focus:ring-2 cursor-pointer transition-all">
                    <span
                      class="text-sm sm:text-base font-bold text-[#3E1E04] font-secondary group-hover:text-[#BC430D] transition-colors">Pilih
                      Semua</span>
                  </label>

                  <template x-if="selectedItems.length > 0">
                    <button @click="removeSelected()"
                      class="text-xs sm:text-sm text-red-500 hover:text-red-700 font-secondary font-medium flex items-center gap-1 sm:gap-1.5 transition-colors">
                      <i class="fa-regular fa-trash-can text-xs sm:text-sm"></i> <span class="hidden sm:inline">Hapus
                        Terpilih</span>
                      <span class="sm:hidden">Hapus</span>
                    </button>
                  </template>
                </div>

                {{-- Looping Item --}}
                <template x-for="item in items" :key="item.id_keranjang">
                  <div
                    class="border border-gray-200 rounded-lg sm:rounded-xl p-3 sm:p-4 transition-all hover:border-[#3E1E04]/30"
                    :class="{ 'bg-orange-50/30 border-[#BC430D]/30': item.selected }"
                    x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90">

                    {{-- Layout Mobile: Grid untuk checkbox & gambar --}}
                    <div class="flex gap-3 sm:gap-4">
                      {{-- Checkbox Individual --}}
                      <div class="flex items-start pt-5 sm:pt-6">
                        <input type="checkbox" x-model="item.selected"
                          class="w-4 h-4 sm:w-5 sm:h-5 text-[#BC430D] bg-gray-100 border-gray-300 rounded focus:ring-[#BC430D] focus:ring-2 cursor-pointer transition-all">
                      </div>

                      {{-- Gambar Produk --}}
                      <div
                        class="w-16 h-16 sm:w-20 sm:h-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-pointer"
                        @click="item.selected = !item.selected">
                        <img :src="item.product?.main_image ? '{{ asset('storage') }}/' + item.product.main_image : '{{ asset('images/default/Latte.jpg') }}'"
                          :alt="item.product?.name || 'Produk'" class="w-full h-full object-cover">
                      </div>

                      {{-- Detail Info --}}
                      <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start gap-2">
                          <div class="cursor-pointer flex-1 min-w-0" @click="item.selected = !item.selected">
                            <h3 class="font-bold text-sm sm:text-base md:text-lg text-[#3E1E04] font-primary truncate"
                              x-text="item.product?.name || 'Produk'"></h3>
                            <p class="text-xs sm:text-sm text-gray-600 font-secondary mt-0.5">Rp <span
                                x-text="formatRupiah(item.product?.price || 0)"></span></p>
                          </div>
                          <p class="font-bold text-sm sm:text-base text-[#3E1E04] font-secondary whitespace-nowrap">Rp
                            <span x-text="formatRupiah((item.product?.price || 0) * item.quantity)"></span>
                          </p>
                        </div>

                        {{-- Kuantitas Control --}}
                        <div class="mt-2 sm:mt-3 flex items-center justify-between gap-2">
                          <div class="inline-flex items-center border border-gray-200 rounded-lg bg-white">
                            <button type="button" @click="updateQuantity(item.id_keranjang, -1)"
                              :disabled="item.quantity <= 1"
                              class="px-2 sm:px-3 py-1 text-xs sm:text-sm font-medium text-gray-600 hover:text-[#3E1E04] disabled:opacity-50 disabled:cursor-not-allowed">-</button>
                            <span
                              class="px-2 sm:px-4 py-1 text-xs sm:text-sm font-medium text-[#3E1E04] font-secondary border-x border-gray-200"
                              x-text="item.quantity"></span>
                            <button type="button" @click="updateQuantity(item.id_keranjang, 1)"
                              class="px-2 sm:px-3 py-1 text-xs sm:text-sm font-medium text-gray-600 hover:text-[#3E1E04]">+</button>
                          </div>

                          {{-- Tombol Hapus Satuan --}}
                          <button @click="removeItem(item.id_keranjang)"
                            class="text-gray-400 hover:text-red-500 transition-colors text-xs sm:text-sm font-secondary flex items-center gap-1">
                            <i class="fa-regular fa-trash-can text-xs sm:text-sm"></i>
                            <span class="hidden sm:inline">Hapus</span>
                          </button>
                        </div>
                      </div>
                    </div>

                    {{-- Input Catatan --}}
                    <div
                      class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-gray-100 flex items-center gap-2 sm:gap-3 font-secondary">
                      <label :for="'catatan_' + item.id_keranjang"
                        class="text-xs sm:text-sm text-gray-500 whitespace-nowrap">
                        <i class="fa-regular fa-pen-to-square mr-1 text-xs sm:text-sm"></i>
                        <span class="hidden sm:inline">Catatan:</span>
                      </label>
                      <input type="text" :id="'catatan_' + item.id_keranjang" x-model="item.notes"
                        @change="updateNote(item.id_keranjang, item.notes)" placeholder="Tambahkan catatan..."
                        class="block w-full text-xs sm:text-sm text-gray-900 bg-gray-50 rounded-lg border-0 focus:ring-1 focus:ring-[#BC430D] px-2 sm:px-3 py-1.5 transition-colors">
                    </div>
                  </div>
                </template>
              </div>

              {{-- Ringkasan Pesanan (Kanan) - Sticky untuk mobile --}}
              <div class="w-full lg:w-[280px] xl:w-[320px] flex-shrink-0">
                <div class="bg-[#F9F8F6] rounded-xl border border-[#3E1E04]/10 p-4 sm:p-5 lg:sticky lg:top-28">
                  <h3 class="text-base sm:text-lg font-bold text-[#3E1E04] mb-3 sm:mb-5 font-primary">Ringkasan Pesanan
                  </h3>

                  <div class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 font-secondary text-xs sm:text-sm">
                    <div class="text-gray-600 font-medium">Terpilih: <span class="font-bold text-[#BC430D]"
                        x-text="selectedItems.length"></span> Item</div>

                    <div class="flex justify-between items-center">
                      <span class="text-gray-600">Subtotal</span>
                      <span class="font-semibold text-[#3E1E04]">Rp <span
                          x-text="formatRupiah(calculateTotal())"></span></span>
                    </div>

                    <div class="flex justify-between items-center">
                      <span class="text-gray-600">Biaya Layanan</span>
                      <span class="font-semibold text-[#3E1E04]">-</span>
                    </div>
                  </div>

                  <hr class="border-[#3E1E04]/10 mb-4 sm:mb-5">

                  <div class="flex justify-between items-center mb-4 sm:mb-6">
                    <span class="font-medium text-gray-600 font-secondary text-xs sm:text-sm">Total Estimasi</span>
                    <span class="text-lg sm:text-xl font-bold text-[#3E1E04] font-primary">Rp <span
                        x-text="formatRupiah(calculateTotal())"></span></span>
                  </div>

                  <button type="button" @click="sendToWhatsApp()" :disabled="selectedItems.length === 0"
                    :class="selectedItems.length === 0 ? 'bg-gray-400 cursor-not-allowed opacity-70' :
                        'bg-[#3C6B3E] hover:bg-[#2A4D2B] hover:-translate-y-0.5'"
                    class="w-full text-white font-medium py-2.5 sm:py-3 px-4 rounded-xl flex items-center justify-center gap-2 transition-all shadow-sm font-secondary text-sm sm:text-base">
                    <i class="fa-brands fa-whatsapp text-base sm:text-lg"></i>
                    <span x-text="selectedItems.length === 0 ? 'Pilih Item Dulu' : 'Checkout via WhatsApp'"></span>
                  </button>

                  <p class="text-center text-[10px] sm:text-xs text-gray-500 mt-3 sm:mt-4 font-secondary leading-relaxed">
                    Pesanan akan dikirim ke WhatsApp<br class="hidden sm:block">untuk konfirmasi lebih lanjut.
                  </p>
                </div>
              </div>

            </div>
          </template>

          {{-- Kondisi Saat Keranjang Kosong --}}
          <template x-if="items.length === 0 && !isLoading">
            <div
              class="flex flex-col items-center justify-center py-12 sm:py-16 px-4 text-center border-2 border-dashed border-gray-200 rounded-xl bg-gray-50/50">
              <div
                class="w-16 h-16 sm:w-20 sm:h-20 bg-white rounded-full flex items-center justify-center mb-3 sm:mb-4 shadow-sm border border-gray-100">
                <i class="fa-solid fa-cart-shopping text-2xl sm:text-3xl text-gray-400"></i>
              </div>
              <h3 class="text-lg sm:text-xl font-bold text-[#3E1E04] font-primary mb-1 sm:mb-2">Keranjangmu masih kosong
              </h3>
              <p class="text-gray-500 font-secondary mb-4 sm:mb-6 text-xs sm:text-sm max-w-sm px-2">
                Sepertinya kamu belum menambahkan produk apa pun ke keranjang. Yuk, jelajahi produk kami dan temukan yang
                kamu suka!
              </p>
              <a href="{{ url('/menu') }}"
                class="px-5 sm:px-6 py-2 sm:py-2.5 bg-[#3E1E04] hover:bg-[#2A1402] text-white font-medium rounded-xl transition-colors font-secondary text-xs sm:text-sm shadow-sm inline-flex items-center gap-2">
                <i class="fa-solid fa-bag-shopping text-xs sm:text-sm"></i> Mulai Belanja
              </a>
            </div>
          </template>

        </div>
      </div>

    </div>
  </div>

  {{-- Script Alpine.js Component --}}
  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('cartApp', (initialCarts) => ({
        items: initialCarts.map(item => ({
          ...item,
          selected: true
        })),
        isLoading: false,

        // Getter untuk mengambil item yang Dicentang saja
        get selectedItems() {
          return this.items.filter(item => item.selected);
        },

        // Getter untuk mengecek apakah SEMUA item dicentang
        get allSelected() {
          return this.items.length > 0 && this.selectedItems.length === this.items.length;
        },

        // Fungsi ketika kotak "Pilih Semua" diklik
        toggleAll() {
          const newState = !this.allSelected;
          this.items.forEach(item => item.selected = newState);
        },

        // Kalkulasi Total HANYA untuk item yang dicentang
        calculateTotal() {
          return this.selectedItems.reduce((total, item) => {
            const price = item.product?.price || 0;
            return total + (price * item.quantity);
          }, 0);
        },

        formatRupiah(number) {
          return new Intl.NumberFormat('id-ID').format(number);
        },

        // API Call: Update Quantity
        async updateQuantity(cartId, change) {
          const index = this.items.findIndex(i => i.id_keranjang === cartId);
          if (index === -1) return;

          const newQuantity = this.items[index].quantity + change;
          if (newQuantity < 1) return;

          // Optimistic Update
          this.items[index].quantity = newQuantity;

          try {
            await fetch(`/api/cart/${cartId}/update`, {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                quantity: newQuantity
              })
            });
          } catch (error) {
            this.items[index].quantity -= change;
            alert('Gagal mengubah kuantitas.');
          }
        },

        // API Call: Hapus Single Item
        async removeItem(cartId) {
          if (!confirm('Yakin ingin menghapus menu ini dari keranjang?')) return;

          this.isLoading = true;
          try {
            const response = await fetch(`/api/cart/${cartId}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
              }
            });

            if (response.ok) {
              this.items = this.items.filter(i => i.id_keranjang !== cartId);
              window.dispatchEvent(new CustomEvent('cart-updated', {
                detail: {
                  count: this.items.length
                }
              }));
            }
          } catch (error) {
            alert('Gagal menghapus item.');
          } finally {
            this.isLoading = false;
          }
        },

        // API Call: Hapus Multi Item (Hapus Terpilih)
        async removeSelected() {
          if (!confirm(`Yakin ingin menghapus ${this.selectedItems.length} item terpilih?`)) return;

          this.isLoading = true;
          const idsToDelete = this.selectedItems.map(i => i.id_keranjang);

          try {
            // Melakukan looping fetch DELETE untuk setiap item yang dipilih
            const deletePromises = idsToDelete.map(id =>
              fetch(`/api/cart/${id}`, {
                method: 'DELETE',
                headers: {
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                  'Accept': 'application/json'
                }
              })
            );

            await Promise.all(deletePromises);

            // Hilangkan dari UI setelah sukses
            this.items = this.items.filter(i => !idsToDelete.includes(i.id_keranjang));
            window.dispatchEvent(new CustomEvent('cart-updated', {
              detail: {
                count: this.items.length
              }
            }));

          } catch (error) {
            alert('Beberapa item gagal dihapus. Silakan muat ulang halaman.');
          } finally {
            this.isLoading = false;
          }
        },

        // API Call: Update Catatan
        async updateNote(cartId, newNote) {
          try {
            await fetch(`/api/cart/${cartId}/note`, {
              method: 'PATCH',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                notes: newNote
              })
            });
          } catch (error) {}
        },

        async sendToWhatsApp() {
          if (this.selectedItems.length === 0) return;

          this.isLoading = true;

          try {
            // 1. Simpan ke Database Internal dulu
            const response = await fetch('{{ route('cart.checkout') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                cart_ids: this.selectedItems.map(i => i.id_keranjang)
              })
            });

            const data = await response.json();

            if (data.success) {
              // 2. Jika sukses tersimpan di riwayat, baru buat format teks WhatsApp
              let text = `Halo Admin {{ Cache::get('store_name', 'Seven Coffee') }}!\n`;
              text += `Saya ingin memesan dengan *Kode: ${data.order_code}*\n\n`;

              this.selectedItems.forEach((item, index) => {
                text += `${index + 1}. ${item.product.name} (${item.quantity}x)\n`;
                if (item.notes) text += `   Catatan: ${item.notes}\n`;
              });

              text += `\n*Total Estimasi:* Rp ${this.formatRupiah(this.calculateTotal())}\n`;
              text += `\nMohon dicek ya, terima kasih!`;

              const noWa = "{{ preg_replace('/[^0-9]/', '', Cache::get('whatsapp', '6281234567890')) }}";
              const waUrl = `https://wa.me/${noWa}?text=${encodeURIComponent(text)}`;

              // 3. Update Navbar & Redirect
              window.dispatchEvent(new CustomEvent('cart-updated', {
                detail: {
                  count: data.cart_count
                }
              }));

              window.open(waUrl, '_blank');

              window.location.href = '{{ route('user.history') }}';
            }
          } catch (error) {
            alert('Gagal memproses pesanan.');
          } finally {
            this.isLoading = false;
          }
        }
      }));
    });
  </script>
@endsection
