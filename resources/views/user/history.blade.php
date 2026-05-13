@extends('layouts.app')

@section('title', 'Riwayat Pesanan')

@section('content')
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8" x-data="historyApp({{ Js::from($orders) }})"
    @keydown.escape.window="closeDetail()">

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 mt-12">
      <div class="w-full lg:w-80 flex-shrink-0">
        <x-sidebar />
      </div>

      <div class="flex-1 space-y-6">
        {{-- Ringkasan Pengeluaran --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            class="bg-gradient-to-br from-[#3E1E04] to-[#743a08] p-5 rounded-2xl shadow-sm text-white border border-white/10">
            <p class="text-xs font-secondary text-orange-200/80 uppercase tracking-widest mb-1">Pengeluaran Bulan Ini
              (Selesai)</p>
            <div class="flex items-end gap-2">
              <h3 class="text-2xl font-bold font-primary">Rp <span x-text="formatRupiah(calculateMonthlyTotal())"></span>
              </h3>
              <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full mb-1.5 border border-white/10"
                x-text="getCurrentMonthName()"></span>
            </div>
          </div>
          <div class="bg-white p-5 rounded-2xl shadow-sm border border-[#3E1E04]/10">
            <p class="text-xs font-secondary text-gray-400 uppercase tracking-widest mb-1">Total Pesanan</p>
            <h3 class="text-2xl font-bold font-primary text-[#3E1E04]"><span x-text="orders.length"></span> <span
                class="text-sm font-secondary font-medium text-gray-500">Transaksi</span></h3>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-[#3E1E04]/10 p-6 lg:p-8 relative">
          <div x-show="isLoading"
            class="absolute inset-0 bg-white/60 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"
            style="display: none;">
            <i class="fas fa-spinner fa-spin text-3xl text-[#BC430D]"></i>
          </div>

          {{-- Header & Tabs --}}
          <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 lg:mb-8 border-b border-[#3E1E04]/10 pb-4">
            <header>
              <h2 class="text-2xl font-bold text-[#3E1E04] font-primary">Riwayat Pesanan</h2>
              <p class="text-sm text-gray-500 mt-1 font-secondary">Daftar menu yang pernah kamu pesan di
                {{ Cache::get('store_name', 'Seven Coffee') }}.</p>
            </header>

            <div
              class="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-200 font-secondary overflow-x-auto shrink-0">
              <button @click="filter = 'all'"
                :class="filter === 'all' ? 'bg-[#F4EFEA] text-[#3E1E04] font-semibold shadow-sm' :
                    'text-gray-500 font-medium'"
                class="px-4 py-1.5 rounded-md text-sm transition-colors whitespace-nowrap">Semua</button>
              <button @click="filter = 'week'"
                :class="filter === 'week' ? 'bg-[#F4EFEA] text-[#3E1E04] font-semibold shadow-sm' :
                    'text-gray-500 font-medium'"
                class="px-4 py-1.5 rounded-md text-sm transition-colors whitespace-nowrap">Minggu Ini</button>
              <button @click="filter = 'month'"
                :class="filter === 'month' ? 'bg-[#F4EFEA] text-[#3E1E04] font-semibold shadow-sm' :
                    'text-gray-500 font-medium'"
                class="px-4 py-1.5 rounded-md text-sm transition-colors whitespace-nowrap">Bulan Ini</button>
            </div>
          </div>

          {{-- List Riwayat --}}
          <div class="space-y-4">
            <template x-for="order in filteredOrders" :key="order.id_pesanan">
              <div
                class="border border-[#3E1E04]/10 rounded-xl p-5 hover:border-[#BC430D]/30 transition-all duration-300 hover:shadow-md bg-white">
                <div class="flex flex-col md:flex-row justify-between gap-4">
                  <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 font-secondary mb-3">
                      <span class="font-bold text-[#3E1E04]" x-text="'#' + order.order_code"></span>
                      <span class="text-gray-300">|</span>
                      <div class="flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i><span
                          x-text="formatDate(order.created_at)"></span></div>
                    </div>

                    <div class="flex flex-wrap gap-2 mb-4">
                      <span :class="getStatusClasses(order.order_status)"
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold font-secondary border"><span
                          x-text="getStatusLabel(order.order_status)"></span></span>
                      <span :class="getPaymentClasses(order.payment_status)"
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold font-secondary border border-gray-100 bg-gray-50"><i
                          class="fa-solid fa-wallet mr-1.5"></i><span
                          x-text="getPaymentLabel(order.payment_status)"></span></span>
                    </div>

                    <div class="text-sm font-secondary space-y-1.5">
                      <div class="font-medium text-[#3E1E04] mb-2 flex items-center gap-2">☕ <span
                          x-text="order.items.length + ' Macam Menu'"></span></div>
                      <template x-for="(item, index) in order.items.slice(0, 2)" :key="index">
                        <div class="flex items-center gap-2 text-gray-600"><span class="font-medium"
                            x-text="item.quantity + 'x'"></span><span
                            x-text="item.product?.name || item.product_name_snapshot"></span></div>
                      </template>
                      <template x-if="order.items.length > 2">
                        <div class="text-gray-400 text-xs mt-1"><span
                            x-text="'+' + (order.items.length - 2) + ' menu lainnya...'"></span></div>
                      </template>
                    </div>
                  </div>

                  <div class="flex flex-col justify-end items-start md:items-end min-w-[200px]">
                    <div class="text-sm text-gray-500 font-secondary mb-1">Total Belanja</div>
                    <div class="text-lg font-bold text-[#3E1E04] font-primary mb-4">Rp <span
                        x-text="formatRupiah(order.total)"></span></div>
                    <div class="flex flex-wrap items-center gap-2 w-full md:w-auto mt-auto">
                      <button @click="reorder(order)"
                        class="flex-1 md:flex-none bg-[#4A3219] hover:bg-[#BC430D] text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors duration-300 font-secondary">Pesan
                        Lagi</button>

                      {{-- BUTTON DETAIL DINAMIS --}}
                      <button @click="openDetail(order)"
                        class="flex-1 md:flex-none bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium transition-colors font-secondary">
                        Detail
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>

    {{-- MODAL DETAIL PESANAN --}}
    <div x-show="showDetail" class="fixed inset-0 z-[150] overflow-y-auto"
      x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" style="display: none;">

      {{-- Backdrop --}}
      <div class="fixed inset-0 bg-[#3E1E04]/60 backdrop-blur-sm" @click="closeDetail()"></div>

      {{-- Modal Content --}}
      <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-2xl rounded-3xl shadow-2xl overflow-hidden transform transition-all"
          x-show="showDetail" x-transition:enter="transition ease-out duration-300"
          x-transition:enter-start="opacity-0 scale-95 translate-y-8"
          x-transition:enter-end="opacity-100 scale-100 translate-y-0">

          {{-- Modal Header --}}
          <div class="bg-[#FBF8F5] px-6 py-4 flex justify-between items-center border-b border-gray-100">
            <div>
              <h4 class="text-lg font-bold text-[#3E1E04] font-primary">Rincian Pesanan</h4>
              <p class="text-xs text-gray-500 font-secondary"
                x-text="selectedOrder ? '#' + selectedOrder.order_code : ''"></p>
            </div>
            <button @click="closeDetail()"
              class="w-10 h-10 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-200 hover:text-gray-600 transition-all">
              <i class="fa-solid fa-xmark text-xl"></i>
            </button>
          </div>

          {{-- Modal Body --}}
          <div class="p-6 max-h-[70vh] overflow-y-auto font-secondary">
            <template x-if="selectedOrder">
              <div>
                {{-- Status Overview --}}
                <div class="flex flex-wrap gap-4 mb-8 bg-orange-50/50 p-4 rounded-2xl border border-orange-100">
                  <div class="flex-1">
                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-1">Status Pesanan</p>
                    <span :class="getStatusClasses(selectedOrder.order_status)"
                      class="px-3 py-1 rounded-full text-xs font-bold border"
                      x-text="getStatusLabel(selectedOrder.order_status)"></span>
                  </div>
                  <div class="flex-1">
                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-1">Pembayaran</p>
                    <span :class="getPaymentClasses(selectedOrder.payment_status)" class="text-xs font-bold"
                      x-text="getPaymentLabel(selectedOrder.payment_status)"></span>
                  </div>
                  <div class="flex-1">
                    <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-1">Tanggal</p>
                    <p class="text-xs font-bold text-[#3E1E04]" x-text="formatDate(selectedOrder.created_at)"></p>
                  </div>
                </div>

                {{-- Items List --}}
                <h5 class="font-bold text-[#3E1E04] mb-4 flex items-center gap-2">
                  <i class="fa-solid fa-list-ul text-[#BC430D]"></i> Daftar Menu
                </h5>
                <div class="space-y-4 mb-8">
                  <template x-for="item in selectedOrder.items" :key="item.id_item_pesanan">
                    <div class="flex gap-4 items-start pb-4 border-b border-gray-50 last:border-0">
                      <div class="w-14 h-14 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100">
                        <img
                          :src="item.product?.main_image ? '{{ asset('storage') }}/' + item.product.main_image :
                              '{{ asset('images/default/default.jpg') }}'"
                          class="w-full h-full object-cover">
                      </div>
                      <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start gap-2">
                          <h6 class="font-bold text-[#3E1E04] text-sm truncate"
                            x-text="item.product?.name || item.product_name_snapshot"></h6>
                          <span class="text-sm font-bold text-[#3E1E04] whitespace-nowrap"
                            x-text="'Rp ' + formatRupiah(item.subtotal)"></span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5"
                          x-text="item.quantity + ' x Rp ' + formatRupiah(item.unit_price)"></p>
                        <template x-if="item.notes">
                          <p class="mt-2 text-[11px] bg-gray-50 text-gray-500 p-2 rounded-lg italic">
                            <i class="fa-regular fa-comment-dots mr-1"></i> <span x-text="item.notes"></span>
                          </p>
                        </template>
                      </div>
                    </div>
                  </template>
                </div>

                {{-- Calculation --}}
                <div class="space-y-3 bg-gray-50 p-5 rounded-2xl border border-gray-100">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-bold text-[#3E1E04]" x-text="'Rp ' + formatRupiah(selectedOrder.subtotal)"></span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Biaya Layanan</span>
                    <span class="font-bold text-[#3E1E04]">Rp 0</span>
                  </div>
                  <hr class="border-gray-200 my-2">
                  <div class="flex justify-between items-center">
                    <span class="font-bold text-[#3E1E04]">Total Pembayaran</span>
                    <span class="text-xl font-bold text-[#BC430D]"
                      x-text="'Rp ' + formatRupiah(selectedOrder.total)"></span>
                  </div>
                </div>
              </div>
            </template>
          </div>

          {{-- Modal Footer --}}
          <div class="px-6 py-5 bg-[#FBF8F5] border-t border-gray-100 flex gap-3">
            <button @click="closeDetail()"
              class="flex-1 bg-white border border-gray-200 text-gray-600 py-3 rounded-xl font-bold text-sm hover:bg-gray-50 transition-all">Tutup</button>
            <button @click="reorder(selectedOrder)"
              class="flex-1 bg-[#3E1E04] text-white py-3 rounded-xl font-bold text-sm hover:bg-[#BC430D] transition-all shadow-lg shadow-[#3E1E04]/20">Pesan
              Lagi</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('historyApp', (orders) => ({
        orders: orders,
        filter: 'all',
        isLoading: false,
        // State Modal
        showDetail: false,
        selectedOrder: null,

        openDetail(order) {
          this.selectedOrder = order;
          this.showDetail = true;
          document.body.classList.add('overflow-hidden');
        },

        closeDetail() {
          this.showDetail = false;
          // Delay sedikit agar transisi selesai baru hapus data
          setTimeout(() => {
            this.selectedOrder = null;
          }, 300);
          document.body.classList.remove('overflow-hidden');
        },

        get filteredOrders() {
          if (this.filter === 'all') return this.orders;
          const now = new Date();
          return this.orders.filter(order => {
            const date = new Date(order.created_at.replace(' ', 'T'));
            if (this.filter === 'week') {
              const weekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000);
              return date >= weekAgo;
            }
            if (this.filter === 'month') {
              return date.getMonth() === now.getMonth() && date.getFullYear() === now.getFullYear();
            }
          });
        },

        calculateMonthlyTotal() {
          const now = new Date();
          const currentMonth = now.getMonth();
          const currentYear = now.getFullYear();

          return this.orders.reduce((acc, order) => {
            const orderDate = new Date(order.created_at.replace(' ', 'T'));
            const status = order.order_status ? order.order_status.toLowerCase().trim() : '';

            if (orderDate.getMonth() === currentMonth &&
              orderDate.getFullYear() === currentYear &&
              status === 'completed') {
              return acc + parseFloat(order.total || 0);
            }
            return acc;
          }, 0);
        },

        getCurrentMonthName() {
          return new Date().toLocaleDateString('id-ID', {
            month: 'long'
          });
        },

        formatRupiah(number) {
          return new Intl.NumberFormat('id-ID').format(number);
        },

        formatDate(dateString) {
          if (!dateString) return '-';
          const date = new Date(dateString.replace(' ', 'T'));
          return date.toLocaleDateString('id-ID', {
              day: '2-digit',
              month: 'short',
              year: 'numeric'
            }) + ' • ' +
            date.toLocaleTimeString('id-ID', {
              hour: '2-digit',
              minute: '2-digit'
            });
        },

        getStatusLabel(status) {
          const labels = {
            'pending_confirmation': 'Menunggu Konfirmasi',
            'processing': 'Sedang Diproses',
            'ready_for_pickup': 'Siap Diambil',
            'completed': 'Selesai',
            'cancelled': 'Dibatalkan'
          };
          return labels[status] || status;
        },

        getStatusClasses(status) {
          const classes = {
            'pending_confirmation': 'bg-yellow-50 text-yellow-700 border-yellow-200',
            'processing': 'bg-blue-50 text-blue-700 border-blue-200',
            'ready_for_pickup': 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'completed': 'bg-green-50 text-green-700 border-green-200',
            'cancelled': 'bg-red-50 text-red-700 border-red-200'
          };
          return classes[status] || 'bg-gray-50 text-gray-700 border-gray-200';
        },

        getPaymentLabel(status) {
          const labels = {
            'pending': 'Belum Dibayar',
            'paid': 'Lunas',
            'failed': 'Gagal',
            'refunded': 'Dikembalikan'
          };
          return labels[status] || status;
        },

        getPaymentClasses(status) {
          const colors = {
            'pending': 'text-yellow-600',
            'paid': 'text-green-600',
            'failed': 'text-red-600',
            'refunded': 'text-gray-600'
          };
          return colors[status] || 'text-gray-600';
        },

        async reorder(order) {
          if (!order || !confirm('Masukkan kembali semua menu ini ke keranjang?')) return;
          this.isLoading = true;
          this.showDetail = false; // Tutup modal saat proses
          try {
            const promises = order.items.map(item => {
              return fetch('{{ route('cart.add') }}', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': '{{ csrf_token() }}',
                  'Accept': 'application/json'
                },
                body: JSON.stringify({
                  id_produk: item.id_produk,
                  quantity: item.quantity
                })
              });
            });
            const responses = await Promise.all(promises);
            const lastData = await responses[responses.length - 1].json();
            window.dispatchEvent(new CustomEvent('cart-updated', {
              detail: {
                count: lastData.cart_count
              }
            }));
            alert('Semua menu berhasil ditambahkan ke keranjang!');
            window.location.href = '{{ route('user.cart') }}';
          } catch (e) {
            alert('Gagal memproses pesanan ulang.');
          } finally {
            this.isLoading = false;
          }
        }
      }));
    });
  </script>
@endsection
