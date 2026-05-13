@forelse ($allMenus as $menu)
  {{-- KARTU PRODUK --}}
  <div
    class="group bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-2xl hover:shadow-amber-900/10 hover:-translate-y-2 hover:border-amber-200 transition-all duration-500 ease-out overflow-hidden flex flex-col">
    <div class="relative overflow-hidden aspect-[4/3] bg-gray-100 m-2 rounded-xl">
      <img src="{{ $menu->main_image ? asset('storage/' . $menu->main_image) : asset('images/default/herobg.png') }}"
        alt="{{ $menu->name }}"
        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out"
        loading="lazy">

      <button type="button"
        class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-white/70 backdrop-blur-md flex items-center justify-center hover:bg-white transition-all shadow-sm"
        @click.stop="toggleFavorite({{ $menu->id_produk }})">
        <i class="text-base transition-transform duration-300"
          :class="isFavorite({{ $menu->id_produk }}) ? 'fa-solid fa-heart text-red-500 scale-110' :
              'fa-regular fa-heart text-gray-500 hover:scale-110'"></i>
      </button>

      @if ($menu->is_signature)
        <div class="absolute top-3 left-3 z-20">
          <span
            class="bg-amber-600/90 backdrop-blur-sm text-white text-[10px] uppercase tracking-wider font-bold px-2.5 py-1 rounded-md shadow-sm">
            Signature
          </span>
        </div>
      @endif
    </div>

    <div class="p-5 flex flex-col flex-1">
      <h3 class="font-primary text-lg font-bold text-gray-800 mb-1 group-hover:text-amber-600 transition-colors">
        {{ $menu->name }}
      </h3>
      <p class="font-secondary text-gray-500 text-sm leading-relaxed mb-4 line-clamp-2 flex-1">
        {{ $menu->description ?? 'Produk berkualitas dengan rasa istimewa.' }}
      </p>

      <div class="flex items-center justify-between pt-4 border-t border-gray-100 mt-auto">
        <span class="font-primary text-xl font-bold text-gray-900">Rp
          {{ number_format($menu->price, 0, ',', '.') }}</span>
        <button data-modal-target="menu-modal-{{ $menu->id_produk }}"
          data-modal-toggle="menu-modal-{{ $menu->id_produk }}"
          class="inline-flex items-center justify-center w-10 h-10 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white rounded-xl transition-all duration-300 group/btn shadow-sm">
          <i class="fas fa-plus text-lg group-hover/btn:rotate-90 transition-transform duration-300"></i>
        </button>
      </div>
    </div>
  </div>

  {{-- MODAL DETAIL PRODUK (Berada di dalam loop agar ikut ter-load AJAX) --}}
  <div id="menu-modal-{{ $menu->id_produk }}" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-[110] justify-center items-end md:items-center w-full h-full bg-gray-900/70 backdrop-blur-sm transition-opacity">

    <div
      class="relative w-full max-w-4xl h-[95vh] md:h-auto md:max-h-[90vh] md:p-4 flex items-end md:items-center justify-center transition-transform">
      <div
        class="relative bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-gray-100 w-full h-full md:h-auto md:max-h-full overflow-hidden flex flex-col">

        {{-- HEADER --}}
        <div
          class="sticky top-0 bg-white/95 backdrop-blur-md z-20 flex items-center justify-between p-4 md:px-6 border-b border-gray-100">
          <div class="flex items-center gap-3 pr-4">
            @if ($menu->is_signature)
              <span class="bg-amber-600 text-white text-[10px] uppercase font-bold px-2 py-1 rounded shadow-sm">
                Signature
              </span>
            @endif
            <h3 class="font-primary text-lg md:text-xl font-bold text-gray-900 line-clamp-1">{{ $menu->name }}</h3>
          </div>
          <button type="button"
            class="text-gray-400 bg-gray-50 hover:bg-red-50 hover:text-red-500 rounded-full text-sm w-9 h-9 flex items-center justify-center transition-all shrink-0"
            data-modal-hide="menu-modal-{{ $menu->id_produk }}">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>

        {{-- CONTENT: 1 Kolom Mobile, 2 Kolom Desktop --}}
        <div class="flex-1 overflow-y-auto flex flex-col md:flex-row">

          {{-- Kiri: Gambar Produk --}}
          <div
            class="w-full md:w-1/2 md:border-r border-gray-100 bg-gray-50/50 p-4 md:p-6 flex flex-col justify-center">
            <div class="relative aspect-square md:aspect-[4/3] rounded-2xl overflow-hidden bg-gray-100 shadow-inner">
              <img
                src="{{ $menu->main_image ? asset('storage/' . $menu->main_image) : asset('images/default/herobg.png') }}"
                alt="{{ $menu->name }}"
                class="w-full h-full object-cover hover:scale-105 transition-transform duration-700" loading="lazy">
            </div>
          </div>

          {{-- Kanan: Informasi Produk --}}
          <div class="w-full md:w-1/2 p-5 md:p-6 flex flex-col gap-6">

            {{-- Deskripsi --}}
            <div>
              <h4 class="font-primary text-sm uppercase tracking-wider font-bold text-gray-400 mb-2">Deskripsi</h4>
              <p class="font-secondary text-gray-600 text-sm md:text-base leading-relaxed">
                {{ $menu->description ?? 'Produk berkualitas dengan rasa istimewa yang diracik khusus untuk Anda.' }}
              </p>
            </div>

            {{-- Spesifikasi --}}
            <div>
              <h4 class="font-primary text-sm uppercase tracking-wider font-bold text-gray-400 mb-3">Spesifikasi</h4>
              <div class="space-y-3 bg-white border border-gray-100 shadow-sm p-4 rounded-xl">
                <div class="flex items-center justify-between text-sm">
                  <div class="flex items-center gap-3 text-gray-500">
                    <i class="fas fa-mug-hot w-4 text-center"></i>
                    <span>Kategori</span>
                  </div>
                  <span class="text-gray-900 font-bold font-secondary">{{ $menu->category?->name ?? 'Umum' }}</span>
                </div>

                <div class="w-full h-px bg-gray-50"></div>

                <div class="flex items-center justify-between text-sm">
                  <div class="flex items-center gap-3 text-gray-500">
                    <i class="fas fa-boxes w-4 text-center"></i>
                    <span>Sisa Stok</span>
                  </div>
                  <span class="text-gray-900 font-bold font-secondary">{{ $menu->stock }} porsi</span>
                </div>

                <div class="w-full h-px bg-gray-50"></div>

                <div class="flex items-center justify-between text-sm">
                  <div class="flex items-center gap-3 text-gray-500">
                    <i class="fas fa-check-circle w-4 text-center"></i>
                    <span>Status</span>
                  </div>
                  <span
                    class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider {{ ($menu->is_available && $menu->stock > 0) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ ($menu->is_available && $menu->stock > 0) ? 'Tersedia' : 'Habis' }}
                  </span>
                </div>
              </div>
            </div>

          </div>
        </div>

        {{-- FOOTER --}}
        <div
          class="sticky bottom-0 bg-white border-t border-gray-100 p-4 md:px-6 md:py-5 z-20 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.05)]">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">

            <div class="flex items-center justify-between md:justify-start md:gap-4">
              <span class="text-sm text-gray-500 font-secondary md:hidden">Total Harga</span>
              <div class="flex flex-col">
                <span
                  class="text-xs text-gray-400 font-secondary hidden md:block uppercase font-bold tracking-wider mb-1">Total
                  Harga</span>
                <div class="font-primary text-2xl font-bold text-amber-600">Rp
                  {{ number_format($menu->price, 0, ',', '.') }}</div>
              </div>
            </div>

            <div class="flex gap-3 md:w-auto">
              <button
                class="px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl flex items-center justify-center gap-2 transition-colors font-secondary"
                data-modal-hide="menu-modal-{{ $menu->id_produk }}">
                Kembali
              </button>

              <button type="button" x-data="{ isAdding: false, added: false }"
                @click.prevent="
      @auth
if(isAdding || {{ (!$menu->is_available || $menu->stock <= 0) ? 'true' : 'false' }}) return;
          
          isAdding = true;
          
          fetch('{{ route('cart.add') }}', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Accept': 'application/json'
              },
              body: JSON.stringify({ 
                  id_produk: {{ $menu->id_produk }}, 
                  quantity: 1 
              })
          })
          .then(response => response.json())
          .then(data => {
              isAdding = false;
              if(data.success) {
                  added = true;
                  // Trigger event agar Navbar otomatis update angkanya
                  window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                  
                  // Kembalikan tombol ke kondisi semula setelah 2 detik
                  setTimeout(() => { added = false; }, 2000);
              }
          })
          .catch(error => {
              isAdding = false;
              alert('Terjadi kesalahan pada server.');
              console.error('Error:', error);
          });
      @else
          // Arahkan ke halaman login jika user belum masuk
          window.location.href = '{{ route('login') }}'; @endauth
        "
                class="flex-1
                md:w-64 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all
                shadow-md font-secondary {{ (!$menu->is_available || $menu->stock <= 0) ? 'bg-gray-400 opacity-50 cursor-not-allowed' : '' }}"
                :class="{
                    'bg-[#BC430D] hover:bg-[#9e380b] shadow-[#BC430D]/20 hover:-translate-y-0.5': !isAdding && !added &&
                        {{ ($menu->is_available && $menu->stock > 0) ? 'true' : 'false' }},
                    'bg-[#9e380b] opacity-75 cursor-wait': isAdding,
                    'bg-green-600': added
                }"
                {{ (!$menu->is_available || $menu->stock <= 0) ? 'disabled' : '' }}>

                {{-- State Default --}}
                <template x-if="!isAdding && !added">
                  <div class="flex items-center gap-2">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span>{{ ($menu->is_available && $menu->stock > 0) ? 'Tambah Keranjang' : 'Stok Habis' }}</span>
                  </div>
                </template>

                {{-- State Loading --}}
                <template x-if="isAdding">
                  <div class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin text-lg"></i>
                    <span>Memproses...</span>
                  </div>
                </template>

                {{-- State Sukses --}}
                <template x-if="added">
                  <div class="flex items-center gap-2">
                    <i class="fas fa-check text-lg"></i>
                    <span>Berhasil!</span>
                  </div>
                </template>
              </button>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
@empty
  <div class="col-span-1 sm:col-span-2 lg:col-span-4 flex flex-col items-center justify-center py-16 px-4 text-center">
    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
      <i class="fas fa-search text-4xl text-gray-400"></i>
    </div>
    <h3 class="font-primary text-2xl font-bold text-gray-800 mb-2">Oops! Menu tidak ditemukan</h3>
    <p class="font-secondary text-gray-500 max-w-md mx-auto">Kami tidak dapat menemukan menu yang cocok dengan filter
      atau pencarian Anda. Coba gunakan kata kunci lain.</p>
    <button @click="resetFilters"
      class="mt-6 inline-flex items-center gap-2 px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-full font-secondary font-bold transition-colors shadow-md">
      <span>Reset Pencarian</span> <i class="fas fa-rotate-left"></i>
    </button>
  </div>
@endforelse
