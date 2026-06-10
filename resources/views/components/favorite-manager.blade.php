@props(['userFavorites' => []])

<div x-data="favoriteManager(
    {{ Js::from($userFavorites) }},
    '{{ route('user.favorite.toggle') }}',
    '{{ route('login') }}',
    '{{ csrf_token() }}'
)">

  {{-- ALL SECTION --}}
  {{ $slot }}

  {{-- TOAST NOTIFICATION --}}
  <div x-cloak x-show="toastVisible" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-10 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-10 scale-95"
    class="fixed bottom-8 left-1/2 transform -translate-x-1/2 z-[100]">
    <div
      class="bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 text-sm font-secondary border border-gray-700">
      <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
        <i class="fas"
          :class="toastMsg && toastMsg.includes('Ditambahkan') ? 'fa-heart text-red-400' : 'fa-trash text-gray-300'"></i>
      </div>
      <span x-text="toastMsg" class="font-medium"></span>
    </div>
  </div>

</div>