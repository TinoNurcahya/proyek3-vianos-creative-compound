@extends('layouts.app')

@section('title', 'Rekomendasi AI')

@section('content')
  <x-favorite-manager :userFavorites="$userFavoriteIds">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
      <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 mt-12">

        {{-- Sidebar --}}
        <div class="w-full lg:w-80 flex-shrink-0">
          <x-sidebar />
        </div>

        {{-- Main Content --}}
        <div class="flex-1 space-y-6">
          <div class="bg-white rounded-xl shadow-sm border border-[#3E1E04]/10 p-6 lg:p-8">

            {{-- Header --}}
            <header class="mb-6 lg:mb-8 border-b border-[#3E1E04]/10 pb-4">
              <div class="flex items-center gap-3 mb-1 flex-wrap">
                <h2 class="text-2xl font-bold text-[#3E1E04] font-primary">Dipilih Oleh AI</h2>
                <span
                  class="bg-purple-100 text-purple-700 text-xs font-bold px-2.5 py-0.5 rounded-full flex items-center gap-1 font-secondary">
                  <i class="fa-solid fa-sparkles text-[10px]"></i> Smart For You
                </span>
                @if ($algorithm === 'content-based')
                  <span
                    class="bg-green-100 text-green-700 text-xs font-semibold px-2.5 py-0.5 rounded-full flex items-center gap-1 font-secondary">
                    <i class="fa-solid fa-microchip text-[10px]"></i> Content-Based
                  </span>
                @elseif ($algorithm === 'collaborative')
                  <span
                    class="bg-blue-100 text-blue-700 text-xs font-semibold px-2.5 py-0.5 rounded-full flex items-center gap-1 font-secondary">
                    <i class="fa-solid fa-users text-[10px]"></i> Collaborative
                  </span>
                @elseif ($algorithm === 'hybrid')
                  <span
                    class="bg-indigo-100 text-indigo-700 text-xs font-semibold px-2.5 py-0.5 rounded-full flex items-center gap-1 font-secondary">
                    <i class="fa-solid fa-network-wired text-[10px]"></i> Hybrid ML
                  </span>
                @else
                  <span
                    class="bg-amber-100 text-amber-700 text-xs font-semibold px-2.5 py-0.5 rounded-full flex items-center gap-1 font-secondary">
                    <i class="fa-solid fa-fire text-[10px]"></i> Trending
                  </span>
                @endif
              </div>
              <p class="text-sm text-gray-500 mt-1 font-secondary">
                @if ($algorithm === 'hybrid')
                  Rekomendasi hybrid menggunakan Content-Based + Collaborative Filtering untuk hasil terbaik.
                @elseif ($algorithm === 'collaborative')
                  Rekomendasi berdasarkan preferensi pengguna serupa dengan Anda.
                @elseif ($algorithm === 'content-based')
                  Rekomendasi ini dipersonalisasi oleh model ML berdasarkan pola pembelianmu.
                @elseif ($hasHistory)
                  Model sedang belajar dari riwayatmu. Berikut menu populer sementara itu.
                @else
                  Mulai pesan untuk mendapatkan rekomendasi personal! Berikut pilihan populer kami.
                @endif
              </p>
            </header>

            {{-- Grid --}}
            @if (count($recommendations) > 0)
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6">
                @foreach ($recommendations as $index => $item)
                  @php
                    $imgPath = $item->main_image
                        ? asset('storage/' . $item->main_image)
                        : asset('images/default/Latte.jpg');
                    $price = 'Rp ' . number_format($item->price, 0, ',', '.');
                  @endphp

                  <div
                    class="bg-[#FBF8F5] rounded-2xl p-3 border border-[#3E1E04]/10 hover:border-purple-300 transition-all duration-300 group hover:shadow-md flex flex-col h-full relative">

                    {{-- Image --}}
                    <div class="relative w-full aspect-square rounded-xl overflow-hidden mb-4 bg-gray-100">
                      <img src="{{ $imgPath }}" alt="{{ $item->name }}"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        onerror="this.src='{{ asset('images/default/Latte.jpg') }}'">

                      {{-- AI Badge --}}
                      <div
                        class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm px-2 py-1.5 rounded-lg flex items-center justify-center shadow-sm border border-white/20">
                        <i class="fa-solid fa-microchip text-purple-600 text-sm"></i>
                      </div>

                      {{-- Rank badge (ML only) --}}
                      @if ($usedMl && $index < 3)
                        <div
                          class="absolute bottom-3 left-3 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full font-secondary">
                          #{{ $index + 1 }} Match
                        </div>
                      @endif

                      {{-- Favorite toggle --}}
                    <button type="button" @click.prevent="toggleFavorite({{ $item->id_produk }})"
                      class="absolute top-3 right-3 transition-colors bg-white/70 backdrop-blur-sm hover:bg-white w-8 h-8 rounded-full flex items-center justify-center shadow-sm {{ in_array($item->id_produk, $userFavoriteIds ?? []) ? 'text-red-500' : 'text-gray-400 hover:text-red-500' }}"
                      :class="{ 'text-red-500': isFavorite({{ $item->id_produk }}), 'text-gray-400 hover:text-red-500': !isFavorite({{ $item->id_produk }}) }"
                      title="Tambah ke favorit">
                      <i class="fa-heart text-lg {{ in_array($item->id_produk, $userFavoriteIds ?? []) ? 'fa-solid' : 'fa-regular' }}"
                        :class="{ 'fa-solid': isFavorite({{ $item->id_produk }}), 'fa-regular': !isFavorite({{ $item->id_produk }}) }"></i>
                    </button>
                    </div>

                    {{-- Content --}}
                    <div class="flex flex-col flex-1 px-1">
                      @if ($item->category)
                        <span class="text-[10px] font-semibold text-purple-500 uppercase tracking-wide mb-1 font-secondary">
                          {{ $item->category->name }}
                        </span>
                      @endif
                      <h3 class="text-lg font-bold text-[#3E1E04] font-primary mb-1 leading-tight">{{ $item->name }}</h3>
                      <p class="text-xs text-gray-500 font-secondary line-clamp-2 mb-4 flex-1 leading-relaxed">
                        {{ $item->description ?: 'Menu pilihan terbaik dari Vianos Creative Compound.' }}
                      </p>

                      {{-- Footer --}}
                      <div class="flex items-center justify-between mt-auto pt-2 border-t border-[#3E1E04]/5">
                        <span class="font-bold text-[#3E1E04] font-secondary text-sm">{{ $price }}</span>
                        <button type="button" data-modal-target="rec-modal-{{ $item->id_produk }}"
                          data-modal-toggle="rec-modal-{{ $item->id_produk }}"
                          class="bg-[#3E1E04] hover:bg-[#BC430D] text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition-colors font-secondary shadow-sm">
                          Lihat
                        </button>
                      </div>
                    </div>
                  </div>

                  {{-- MODAL DETAIL --}}
                  <div id="rec-modal-{{ $item->id_produk }}" tabindex="-1" aria-hidden="true"
                    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-[110] justify-center items-end md:items-center w-full h-full bg-gray-900/60 backdrop-blur-sm transition-opacity">

                    <div
                      class="relative w-full max-w-4xl h-[95vh] md:h-auto md:max-h-[90vh] md:p-4 flex items-end md:items-center justify-center transition-transform">
                      <div
                        class="relative bg-white rounded-t-3xl md:rounded-2xl shadow-2xl border border-gray-100 w-full h-full md:h-auto md:max-h-full overflow-hidden flex flex-col">

                        {{-- HEADER --}}
                        <div
                          class="sticky top-0 bg-white/95 backdrop-blur-md z-20 flex items-center justify-between p-4 md:px-6 border-b border-gray-100">
                          <h3 class="font-primary text-xl font-bold text-gray-900 line-clamp-1 pr-4">{{ $item->name }}
                          </h3>
                          <button type="button"
                            class="text-gray-400 bg-gray-50 hover:bg-red-50 hover:text-red-500 rounded-full text-sm w-9 h-9 flex items-center justify-center transition-all shrink-0"
                            data-modal-hide="rec-modal-{{ $item->id_produk }}">
                            <i class="fas fa-times text-lg"></i>
                          </button>
                        </div>

                        {{-- CONTENT --}}
                        <div class="flex-1 overflow-y-auto flex flex-col md:flex-row">
                          {{-- Kiri: Gambar Produk --}}
                          <div class="w-full md:w-1/2 md:border-r border-gray-100 bg-gray-50/30 p-4 md:p-6 flex flex-col">
                            <div
                              class="aspect-square md:aspect-[4/3] rounded-2xl overflow-hidden bg-gray-100 shadow-inner mb-4">
                              <img
                                src="{{ $item->main_image ? asset('storage/' . $item->main_image) : asset('images/default/herobg.png') }}"
                                alt="{{ $item->name }}"
                                class="w-full h-full object-cover hover:scale-105 transition-transform duration-700"
                                loading="lazy">
                            </div>
                            <div class="hidden md:block bg-amber-50 rounded-xl p-4 border border-amber-100 mt-auto">
                              <div class="flex items-start gap-3">
                                <div class="bg-amber-100 rounded-full p-1.5 shrink-0">
                                  <i class="fas fa-lightbulb text-amber-600 text-sm"></i>
                                </div>
                                <p class="text-xs text-amber-800 leading-relaxed pt-0.5">
                                  <span class="font-bold">Tips:</span> Pesan menu rekomendasi ini sekarang sebelum kehabisan!
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
                                {{ $item->description ?? 'Menu rekomendasi pilihan Anda dengan rasa yang tak terlupakan.' }}
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
                                    class="text-gray-900 font-bold font-secondary">{{ $item->category?->name ?? 'Umum' }}</span>
                                </div>
                                <div class="w-full h-px bg-gray-50"></div>
                                <div class="flex items-center justify-between text-sm">
                                  <div class="flex items-center gap-3 text-gray-500">
                                    <i class="fas fa-boxes w-4 text-center"></i>
                                    <span>Sisa Stok</span>
                                  </div>
                                  <span class="text-gray-900 font-bold font-secondary">{{ $item->stock ?? 0 }} porsi</span>
                                </div>
                                <div class="w-full h-px bg-gray-50"></div>
                                <div class="flex items-center justify-between text-sm">
                                  <div class="flex items-center gap-3 text-gray-500">
                                    <i class="fas fa-check-circle w-4 text-center"></i>
                                    <span>Status</span>
                                  </div>
                                   <span
                                     class="px-2.5 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider {{ (($item->is_available ?? true) && ($item->stock ?? 0) > 0) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                     {{ (($item->is_available ?? true) && ($item->stock ?? 0) > 0) ? 'Tersedia' : 'Habis' }}
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
                                <span
                                  class="text-xs text-gray-400 font-secondary uppercase font-bold tracking-wider mb-1">Total
                                  Harga</span>
                                <div class="font-primary text-2xl font-bold text-amber-600">Rp
                                  {{ number_format($item->price, 0, ',', '.') }}</div>
                              </div>
                            </div>

                            <div class="flex gap-3 md:w-auto">
                              <button
                                class="px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors font-secondary"
                                data-modal-hide="rec-modal-{{ $item->id_produk }}">
                                Kembali
                              </button>

                              {{-- Tombol Tambah Keranjang --}}
                              <button type="button" x-data="{ isAdding: false, added: false }"
                                @click.prevent="
                  @auth
if(isAdding || {{ (!($item->is_available ?? true) || ($item->stock ?? 0) <= 0) ? 'true' : 'false' }}) return;
                    isAdding = true;
                    fetch('{{ route('cart.add') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ id_produk: {{ $item->id_produk }}, quantity: 1 })
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
                                class="flex-1 md:w-64 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all shadow-md font-secondary {{ (!($item->is_available ?? true) || ($item->stock ?? 0) <= 0) ? 'bg-gray-400 opacity-50 cursor-not-allowed' : '' }}"
                                :class="{
                                    'bg-[#BC430D] hover:bg-[#9e380b]': !isAdding && !added &&
                                        {{ (($item->is_available ?? true) && ($item->stock ?? 0) > 0) ? 'true' : 'false' }},
                                    'bg-green-600': added
                                }"
                                {{ (!($item->is_available ?? true) || ($item->stock ?? 0) <= 0) ? 'disabled' : '' }}>
                                <template x-if="!isAdding && !added"><span>{{ (($item->is_available ?? true) && ($item->stock ?? 0) > 0) ? 'Tambah Keranjang' : 'Stok Habis' }}</span></template>
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
              </div>
            @else
              {{-- Empty State --}}
              <div class="text-center py-16 px-4">
                <div class="w-24 h-24 bg-purple-50 rounded-full flex items-center justify-center mx-auto mb-4 relative">
                  <i class="fa-solid fa-microchip text-4xl text-purple-300"></i>
                  <i class="fa-solid fa-sparkles text-amber-400 absolute top-4 right-4 text-sm animate-pulse"></i>
                </div>
                <h3 class="text-lg font-bold text-[#3E1E04] font-primary mb-2">AI Sedang Mempelajari Seleramu</h3>
                <p class="text-sm text-gray-500 font-secondary max-w-sm mx-auto mb-6">
                  Lakukan pemesanan agar AI kami dapat memberikan rekomendasi personal terbaik untukmu.
                </p>
                <a href="{{ route('menu.index') }}"
                  class="inline-flex items-center gap-2 bg-[#BC430D] hover:bg-[#3E1E04] text-white px-6 py-2.5 rounded-lg transition-colors font-secondary font-medium shadow-sm">
                  <i class="fa-solid fa-utensils"></i> Eksplorasi Menu
                </a>
              </div>
            @endif

          </div>
        </div>

      </div>
    </div>
  </x-favorite-manager>
@endsection
