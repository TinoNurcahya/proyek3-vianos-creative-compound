@extends('layouts.admin')

@section('content')
  <x-admin.card title="Manajemen Menu" subtitle="Kelola menu/produk restoran Anda">
    {{-- Tombol Tambah & Search Bar --}}
    <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <a href="{{ route('admin.menu.create') }}"
        class="inline-flex items-center px-4 py-2.5 text-sm font-medium bg-blue-600 text-white rounded-xl hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-all shadow-lg shadow-blue-500/25">
        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Menu
      </a>
    </div>

    {{-- Bulk Actions & Link ke Sampah --}}
    @if ($menus->count())
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
            <input type="checkbox" id="select-all"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
            <span>Pilih Semua</span>
          </label>
          <button id="bulk-delete-btn"
            class="hidden items-center px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 transition-all">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Hapus Terpilih
          </button>
        </div>
        <a href="{{ route('admin.menu.trash') }}"
          class="text-sm text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 inline-flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          Lihat Sampah
        </a>
      </div>
    @endif

    @if ($menus->count())
      <div class="relative overflow-x-auto border border-gray-100 dark:border-gray-700 rounded-xl">
        <table id="tabel-menu" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs uppercase tracking-wider">
            <tr>
              <th scope="col" class="px-6 py-4 w-10 font-semibold">#</th>
              <th scope="col" class="px-6 py-4 font-semibold">Gambar</th>
              <th scope="col" class="px-6 py-4 font-semibold">Nama</th>
              <th scope="col" class="px-6 py-4 font-semibold">Kategori</th>
              <th scope="col" class="px-6 py-4 font-semibold">Harga</th>
              <th scope="col" class="px-6 py-4 font-semibold text-center">Stok</th>
              <th scope="col" class="px-6 py-4 font-semibold text-center">Menu Signature</th>
              <th scope="col" class="px-6 py-4 font-semibold text-center">Tampilkan</th>
              <th scope="col" class="px-6 py-4 font-semibold text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-transparent">
            @foreach ($menus as $menu)
              <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">

                {{-- Checkbox --}}
                <td class="px-6 py-4">
                  <input type="checkbox"
                    class="menu-checkbox rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-blue-600 focus:ring-blue-500 cursor-pointer"
                    value="{{ $menu->id_produk }}">
                </td>

                {{-- Gambar dengan lazy loading --}}
                <td class="px-6 py-4">
                  @if ($menu->main_image)
                    <img src="{{ Storage::url($menu->main_image) }}" alt="{{ $menu->name }}"
                      class="w-12 h-12 rounded-lg object-cover border border-gray-200 dark:border-gray-700"
                      loading="lazy">
                  @else
                    <div
                      class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center">
                      <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                    </div>
                  @endif
                </td>

                {{-- Nama Menu --}}
                <td class="px-6 py-4 font-semibold text-gray-900 whitespace-nowrap dark:text-white">
                  {{ $menu->name }}
                </td>

                {{-- Kategori --}}
                <td class="px-6 py-4">
                  <span
                    class="px-2.5 py-1 rounded-full text-xs font-semibold border bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600">
                    {{ $menu->category->name ?? 'Tanpa Kategori' }}
                  </span>
                </td>

                {{-- Harga --}}
                <td class="px-6 py-4 font-medium text-gray-900 dark:text-gray-300">
                  Rp {{ number_format($menu->price, 0, ',', '.') }}
                </td>

                {{-- Stok --}}
                <td class="px-6 py-4 text-center">
                  @if ($menu->stock > 0)
                    <span
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-green-50 text-green-700 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                      {{ $menu->stock }}
                    </span>
                  @else
                    <span
                      class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-red-50 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800">
                      Habis
                    </span>
                  @endif
                </td>

                {{-- Menu Signature (Toggle Switch) --}}
                <td class="px-6 py-4">
                  <div class="flex justify-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" class="sr-only peer" onchange="toggleSignature({{ $menu->id_produk }}, this)"
                        {{ $menu->is_signature ? 'checked' : '' }}>
                      <div
                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600">
                      </div>
                    </label>
                  </div>
                </td>

                {{-- Status Available --}}
                <td class="px-6 py-4">
                  <div class="flex justify-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" class="sr-only peer"
                        onchange="toggleAvailability({{ $menu->id_produk }}, this)"
                        {{ $menu->is_available ? 'checked' : '' }}>
                      <div
                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600">
                      </div>
                    </label>
                  </div>
                </td>

                {{-- Aksi --}}
                <td class="px-6 py-4">
                  <div class="flex justify-end gap-3">
                    <x-admin.action-button :route="route('admin.menu.edit', $menu)" action="edit" />
                    <x-admin.action-button :route="route('admin.menu.destroy', $menu)" action="delete" confirm="true"
                      confirmMessage="Apakah Anda yakin ingin menghapus menu '{{ $menu->name }}'?" />
                  </div>
                </td>

              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div
        class="text-center py-12 bg-gray-50/50 dark:bg-gray-800/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-700">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <p class="mt-4 text-gray-500 dark:text-gray-400 font-medium">Belum ada menu yang terdaftar.</p>
        <a href="{{ route('admin.menu.create') }}"
          class="mt-2 inline-block text-blue-600 hover:text-blue-700 font-semibold underline">Tambah menu sekarang</a>
      </div>
    @endif
  </x-admin.card>

  @push('scripts')
    <script>

      // Toggle Signature (Menu Unggulan)
      function toggleSignature(menuId, element) {
        element.disabled = true;

        fetch(`/admin/menu/${menuId}/toggle-signature`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              signature: element.checked
            })
          })
          .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan');
            showToast(data.message || 'Berhasil diubah', 'success');
          })
          .catch(error => {
            element.checked = !element.checked;
            let errorMsg = error.message;
            if (error.message === 'Failed to fetch') errorMsg = 'Koneksi bermasalah, periksa jaringan Anda.';
            showToast('Gagal: ' + errorMsg, 'error');
          })
          .finally(() => {
            element.disabled = false;
          });
      }

      // Toggle Availability (Ketersediaan)
      function toggleAvailability(menuId, element) {
        element.disabled = true;

        fetch(`/admin/menu/${menuId}/toggle-availability`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              available: element.checked
            })
          })
          .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Terjadi kesalahan');
            showToast(data.message || 'Berhasil diubah', 'success');
          })
          .catch(error => {
            element.checked = !element.checked;
            let errorMsg = error.message;
            if (error.message === 'Failed to fetch') errorMsg = 'Koneksi bermasalah, periksa jaringan Anda.';
            showToast('Gagal: ' + errorMsg, 'error');
          })
          .finally(() => {
            element.disabled = false;
          });
      }

      $(document).ready(function() {
        // 1. Inisialisasi DataTables untuk tabel menu
        var table = $('#tabel-menu').DataTable({
          "destroy": true,
          "ordering": true,
          "columnDefs": [{
              "orderable": false,
              "targets": [0, 1, 8]
            }
          ],
          "language": {
            "search": "Cari Menu:",
            "lengthMenu": "Tampilkan _MENU_ data",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
            "infoFiltered": "(difilter dari _MAX_ total data)",
            "zeroRecords": "Tidak ada data yang cocok",
            "paginate": {
              "first": "Pertama",
              "last": "Terakhir",
              "next": "Selanjutnya",
              "previous": "Sebelumnya"
            }
          }
        });

        // 2. Logic Bulk Delete & Checkbox
        const selectAllCheckbox = document.getElementById('select-all');
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');

        // Toggle visibilitas tombol Bulk Delete
        function toggleBulkDeleteButton() {
          var selectedCount = table.$('input[type="checkbox"].menu-checkbox:checked').length;
          if (selectedCount > 0) {
            bulkDeleteBtn.classList.remove('hidden');
            bulkDeleteBtn.classList.add('inline-flex');
          } else {
            bulkDeleteBtn.classList.add('hidden');
            bulkDeleteBtn.classList.remove('inline-flex');
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
          }
        }

        // Event saat "Pilih Semua" diklik
        if (selectAllCheckbox) {
          selectAllCheckbox.addEventListener('change', function() {
            var rows = table.rows({
              'search': 'applied'
            }).nodes();
            $('input[type="checkbox"].menu-checkbox', rows).prop('checked', this.checked);
            toggleBulkDeleteButton();
          });
        }

        // Event saat checkbox individual diklik (harus pakai event delegation karena DataTable)
        $('#tabel-menu tbody').on('change', 'input[type="checkbox"].menu-checkbox', function() {
          toggleBulkDeleteButton();
        });

        // Aksi ketika tombol Bulk Delete ditekan
        if (bulkDeleteBtn) {
          bulkDeleteBtn.addEventListener('click', function() {
            var selectedIds = [];
            table.$('input[type="checkbox"].menu-checkbox:checked').each(function() {
              selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
              showToast('Pilih menu yang akan dihapus.', 'error');
              return;
            }

            if (!confirm(`Anda yakin ingin memindahkan ${selectedIds.length} menu ke sampah?`)) return;

            const originalText = this.innerHTML;
            this.innerHTML =
              '<span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true"></span> Memproses...';
            this.disabled = true;

            fetch('{{ route('admin.menu.bulkDelete') }}', {
                method: 'POST',
                headers: {
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Content-Type': 'application/json',
                  'Accept': 'application/json'
                },
                body: JSON.stringify({
                  ids: selectedIds
                })
              })
              .then(async response => {
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Gagal menghapus');
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
              })
              .catch(error => {
                showToast('Error: ' + error.message, 'error');
                this.innerHTML = originalText;
                this.disabled = false;
              });
          });
        }
      });

      // Loading state untuk form submit (Tombol Aksi Hapus Satuan)
      document.querySelectorAll('form[method="POST"][action*="/admin/menu/"]').forEach(form => {
        form.addEventListener('submit', function(e) {
          if (this.querySelector('input[name="_method"]')?.value === 'DELETE') {
            const button = this.querySelector('button[type="submit"]');
            if (button && button.disabled) {
              e.preventDefault();
              return false;
            }
            if (button) {
              button.disabled = true;
              button.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>...';
            }
          }
        });
      });
    </script>
  @endpush
@endsection
