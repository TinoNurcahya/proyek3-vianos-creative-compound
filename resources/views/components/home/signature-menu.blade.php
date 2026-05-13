@props(['menus', 'userFavorites'])

<section id="signature-menu-section"
  class="py-12 sm:py-16 lg:py-20 bg-[#BC430D] relative overflow-hidden home-section-title sm:px-6 md:px-8 lg:px-12 xl:px-[8%] px-4">
  <div class="absolute inset-0 z-0 flex items-start justify-start pointer-events-none rotate-45 mt-20 overflow-hidden">
    <picture>
      <source srcset="{{ asset('images/default/paper-plane.webp') }}" type="image/webp">
      <img src="{{ asset('images/default/paper-plane.png') }}" alt="" aria-hidden="true"
        class="w-2/3 h-full object-cover object-left-top" loading="lazy" decoding="async" />
    </picture>
  </div>
  {{-- Content --}}
  <div class="container mx-auto relative z-10">
    <div class="text-center mb-3" data-aos="fade-up">
      <h2 class="text-3xl md:text-4xl font-semibold text-white mb-3 font-primary">
        Menu Unggulan
      </h2>
      <p class="text-lg md:text-xl text-white/90 font-secondary">
        Favorit banyak orang, wajib kamu coba
      </p>
    </div>

    {{-- Card Grid - 3 columns --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-7xl mx-auto">
      @forelse ($menus as $menu)
        {{-- Card Menu Unggulan --}}
        <div
          class="max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 overflow-hidden group cursor-pointer relative @if ($loop->first) data-aos="fade-right" @elseif($loop->iteration == 2) data-aos="fade-up" @else data-aos="fade-left" @endif"
          {{ $loop->iteration <= 3 ? 'data-aos="' . ($loop->first ? 'fade-right' : ($loop->iteration == 2 ? 'fade-up' : 'fade-left')) . '"' : '' }}>

          <div class="relative">
            <img class="w-full h-64 object-cover rounded-t-lg transition-transform duration-300 group-hover:scale-110"
              src="{{ $menu->main_image ? asset('storage/' . $menu->main_image) : asset('images/default/bg1.webp') }}"
              alt="{{ $menu->name }}" loading="lazy" decoding="async" />

            {{-- TOMBOL FAVORIT --}}
            <button type="button"
              class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full bg-white/70 backdrop-blur-md flex items-center justify-center hover:bg-white transition-all shadow-sm"
              @click.stop="toggleFavorite({{ $menu->id_produk ?? $menu->id }})"
              :class="{
                  'opacity-50 pointer-events-none animate-pulse': processing.includes(
                      {{ $menu->id_produk ?? $menu->id }})
              }">

              <i class="text-xl transition-all duration-300"
                :class="{
                    'fa-solid fa-heart text-red-500 scale-110': isFavorite({{ $menu->id_produk ?? $menu->id }}),
                    'fa-regular fa-heart text-gray-600 hover:scale-110': !isFavorite(
                        {{ $menu->id_produk ?? $menu->id }})
                }">
              </i>
            </button>

            <div
              class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/70 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex flex-col justify-end p-6">
              <h5 class="mb-3 text-2xl font-bold tracking-tight text-white">
                {{ $menu->name }}
              </h5>

              <button type="button" data-modal-target="menu-modal-{{ $menu->id_produk ?? $menu->id }}"
                data-modal-toggle="menu-modal-{{ $menu->id_produk ?? $menu->id }}"
                class="text-white bg-[#BC430D] hover:bg-[#a3370b] focus:ring-4 focus:ring-[#BC430D]/50 font-medium rounded-lg text-sm px-5 py-3 text-center inline-flex items-center w-fit transition-all duration-300">
                Detail
                <i class="fa-solid fa-arrow-right ms-3"></i>
              </button>
            </div>
          </div>
        </div>
      @empty
        <div class="col-span-full text-center py-12">
          <p class="text-white text-lg font-secondary">Menu unggulan tidak tersedia saat ini.</p>
        </div>
      @endforelse
    </div>

    {{-- Flowbite Button Lihat Semua --}}
    <div class="flex justify-center items-center mt-12">
      <a href="{{ route('menu.index') }}"
        class="text-white bg-[#BC430D] focus:ring-4 focus:ring-[#BC430D]/50 font-medium rounded-lg text-sm px-8 py-3 text-center border-2 border-white shadow-lg transition-all duration-300 hover:bg-white hover:text-[#BC430D] inline-flex items-center gap-3">
        Lihat Semua Menu
        <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

{{-- MODAL DETAIL MENU --}}
@foreach ($menus as $menu)
  <div id="menu-modal-{{ $menu->id_produk ?? $menu->id }}" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-[110] justify-center items-end md:items-center w-full h-full bg-gray-900/60 backdrop-blur-sm transition-opacity">

    <div
      class="relative w-full max-w-4xl h-[95vh] md:h-auto md:max-h-[90vh] md:p-4 flex items-end md:items-center justify-center transition-transform"
      @click.stop>
      <div
        class="relative bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-gray-100 w-full h-full md:h-auto md:max-h-full overflow-hidden flex flex-col">

        {{-- HEADER --}}
        <div
          class="sticky top-0 bg-white/95 backdrop-blur-md z-20 flex items-center justify-between p-4 md:px-6 border-b border-gray-100">
          <h3 class="font-primary text-xl font-bold text-gray-900 line-clamp-1 pr-4">{{ $menu->name }}
          </h3>
          <button type="button" @click.stop
            class="text-gray-400 bg-gray-50 hover:bg-red-50 hover:text-red-500 rounded-full text-sm w-9 h-9 flex items-center justify-center transition-all shrink-0"
            data-modal-hide="menu-modal-{{ $menu->id_produk ?? $menu->id }}">
            <i class="fas fa-times text-lg"></i>
          </button>
        </div>

        {{-- CONTENT --}}
        <div class="flex-1 overflow-y-auto flex flex-col md:flex-row text-left">
          {{-- Kiri: Gambar Produk --}}
          <div class="w-full md:w-1/2 md:border-r border-gray-100 bg-gray-50/30 p-4 md:p-6 flex flex-col">
            <div class="aspect-square md:aspect-[4/3] rounded-2xl overflow-hidden bg-gray-100 shadow-inner mb-4">
              <img
                src="{{ $menu->main_image ? asset('storage/' . $menu->main_image) : asset('images/default/herobg.png') }}"
                alt="{{ $menu->name }}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-700"
                loading="lazy">
            </div>
            <div class="hidden md:block bg-amber-50 rounded-xl p-4 border border-amber-100 mt-auto">
              <div class="flex items-start gap-3">
                <div class="bg-amber-100 rounded-full p-1.5 shrink-0">
                  <i class="fas fa-lightbulb text-amber-600 text-sm"></i>
                </div>
                <p class="text-xs text-amber-800 leading-relaxed pt-0.5">
                  <span class="font-bold">Tips:</span> Pesan menu favoritmu ini sekarang sebelum
                  kehabisan!
                </p>
              </div>
            </div>
          </div>

          {{-- Kanan: Informasi Produk --}}
          <div class="w-full md:w-1/2 p-5 md:p-6 flex flex-col gap-6">
            <div>
              <h4 class="font-primary text-sm uppercase tracking-wider font-bold text-gray-400 mb-2">
                Deskripsi</h4>
              <p class="font-secondary text-gray-600 text-sm md:text-base leading-relaxed">
                {{ $menu->description ?? 'Menu favorit pilihan Anda dengan rasa yang tak terlupakan.' }}
              </p>
            </div>

            <div>
              <h4 class="font-primary text-sm uppercase tracking-wider font-bold text-gray-400 mb-3">
                Detail Spesifikasi</h4>
              <div class="space-y-3 bg-white border border-gray-100 shadow-sm p-4 rounded-xl">
                <div class="flex items-center justify-between text-sm">
                  <div class="flex items-center gap-3 text-gray-500">
                    <i class="fas fa-tag w-4 text-center"></i>
                    <span>Kategori</span>
                  </div>
                  <span
                    class="text-gray-900 font-bold font-secondary">{{ $menu->category?->name ?? 'Umum' }}</span>
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
                    class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider {{ $menu->is_available && $menu->stock > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $menu->is_available && $menu->stock > 0 ? 'Tersedia' : 'Habis' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- FOOTER MODAL --}}
        <div
          class="sticky bottom-0 bg-white border-t border-gray-100 p-4 md:px-6 md:py-5 z-20 shadow-[0_-10px_20px_-10px_rgba(0,0,0,0.05)]">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center justify-between md:justify-start md:gap-4">
              <div class="flex flex-col">
                <span class="text-xs text-gray-400 font-secondary uppercase font-bold tracking-wider mb-1">Total
                  Harga</span>
                <div class="font-primary text-2xl font-bold text-amber-600">Rp
                  {{ number_format($menu->price, 0, ',', '.') }}</div>
              </div>
            </div>

            <div class="flex gap-3 md:w-auto">
              <button type="button"
                class="px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors font-secondary"
                data-modal-hide="menu-modal-{{ $menu->id_produk ?? $menu->id }}">
                Kembali
              </button>

              {{-- Tombol Tambah Keranjang --}}
              <button type="button" x-data="{ isAdding: false, added: false }" @click.stop.prevent="
              @auth
                if(isAdding || {{ !$menu->is_available || $menu->stock <= 0 ? 'true' : 'false' }}) return;
                isAdding = true;
                fetch('{{ route('cart.add') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ id_produk: {{ $menu->id_produk ?? $menu->id }}, quantity: 1 })
                })
                .then(response => response.json())
                .then(data => {
                    isAdding = false;
                    if(data.success) {
                        added = true;
                        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: data.cart_count } }));
                        setTimeout(() => { added = false; }, 2000);
                    }
                });
              @else window.location.href = '{{ route('login') }}'; @endauth"
                class="flex-1 md:w-64 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all shadow-md font-secondary {{ !$menu->is_available || $menu->stock <= 0 ? 'bg-gray-400 opacity-50 cursor-not-allowed' : '' }}"
                :class="{
                    'bg-[#BC430D] hover:bg-[#9e380b]': !isAdding && !added &&
                        {{ $menu->is_available && $menu->stock > 0 ? 'true' : 'false' }},
                    'bg-green-600': added
                }" {{ !$menu->is_available || $menu->stock <= 0 ? 'disabled' : '' }}>
                <template x-if="!isAdding && !added"><span>{{ $menu->is_available && $menu->stock > 0 ? 'Tambah Keranjang' : 'Stok Habis' }}</span></template>
                <template x-if="isAdding"><span>Memproses...</span></template>
                <template x-if="added"><span>Berhasil!</span></template>
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
@endforeach
