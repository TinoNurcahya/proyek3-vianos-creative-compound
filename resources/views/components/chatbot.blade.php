<div x-data="chatbot()"
  class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 font-secondary pointer-events-none">

  <!-- Chat Button (Pill Shape) -->
  <button @click="toggleChat" x-show="!isOpen" x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    class="bg-[#BC430D] hover:bg-[#a0380a] text-white rounded-full px-5 py-3 shadow-lg transition-transform hover:scale-105 focus:outline-none flex items-center gap-2 pointer-events-auto">
    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path
        d="M12 2C6.477 2 2 6.046 2 11c0 1.62.482 3.14 1.332 4.436L2 21l5.586-1.54A10.871 10.871 0 0012 20c5.523 0 10-4.046 10-9s-4.477-9-10-9z">
      </path>
    </svg>
    <span class="font-semibold text-sm tracking-wide">Tanya Barista</span>
  </button>

  <!-- Chat Window -->
  <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-8 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-8 scale-95"
    class="absolute bottom-0 right-0 w-[calc(100vw-2rem)] sm:w-[360px] max-w-[360px] bg-white rounded-2xl shadow-[0_10px_40px_rgba(0,0,0,0.15)] border border-gray-100 overflow-hidden flex flex-col pointer-events-auto"
    style="height: min(600px, 90vh); max-height: 90vh; display:none;">

    <!-- Header -->
    <div class="bg-white px-4 py-3 flex justify-between items-center border-b border-gray-100 z-10 relative">
      <div class="flex items-center gap-3">
        <!-- Avatar branded -->
        <div
          class="w-9 h-9 rounded-full bg-[#BC430D] flex items-center justify-center text-white shadow-sm flex-shrink-0">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z" />
          </svg>
        </div>
        <div>
          <div class="font-bold text-gray-800 text-sm leading-tight">S7-Assistant</div>
          <!-- Status online indicator -->
          <div class="flex items-center gap-1 mt-0.5">
            <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>
            <span class="text-[11px] text-gray-400">Online sekarang</span>
          </div>
        </div>
      </div>
      <button @click="toggleChat"
        class="text-gray-400 hover:text-gray-700 hover:bg-gray-100 p-1.5 rounded-lg transition-colors focus:outline-none">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
        </svg>
      </button>
    </div>

    <!-- Body Area -->
    <div class="flex-1 overflow-y-auto flex flex-col relative bg-[#FAFAFA]" id="chatbot-messages">

      <!-- Welcome Screen -->
      <div x-show="!hasStarted" class="px-4 sm:px-6 pt-6 sm:pt-8 pb-2 flex flex-col items-center">
        <!-- Ikon Kopi lebih besar -->
        <div
          class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl bg-[#BC430D] flex items-center justify-center text-white mb-4 shadow-md">
          <svg class="w-7 h-7 sm:w-9 sm:h-9" fill="currentColor" viewBox="0 0 24 24">
            <path
              d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z" />
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-1">Halo! Selamat Datang 👋</h2>
        <p class="text-gray-400 text-sm mb-6 text-center">Saya S7-Assistant, siap membantu kamu!</p>

        <!-- Quick Replies List -->
        <div class="w-full flex flex-col border border-gray-100 rounded-xl overflow-hidden bg-white shadow-sm">
          <template x-for="(suggestion, index) in quickSuggestions" :key="index">
            <button @click="sendSuggestion(suggestion)"
              class="flex items-center gap-3 text-left p-4 border-b border-gray-100 last:border-b-0 hover:bg-[#FFF3EE] transition-colors group">
              <!-- Ikon kopi di kiri -->
              <div
                class="w-7 h-7 rounded-full bg-[#FFF3EE] group-hover:bg-[#FFDCCC] flex items-center justify-center flex-shrink-0 transition-colors">
                <svg class="w-3.5 h-3.5 text-[#BC430D]" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z" />
                </svg>
              </div>
              <span class="text-sm text-gray-700 flex-1" x-text="suggestion"></span>
              <svg class="w-4 h-4 text-gray-300 group-hover:text-[#BC430D] flex-shrink-0 transition-colors"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </template>
        </div>
      </div>

      <!-- Chat Messages -->
      <div x-show="hasStarted" class="p-4 flex flex-col space-y-4 min-h-full">
        <template x-for="(msg, index) in messages" :key="index">
          <div :class="msg.type === 'user' ? 'flex justify-end' : 'flex justify-start'">
            <!-- Bot Message -->
            <template x-if="msg.type === 'bot'">
              <div class="flex items-start max-w-[85%] gap-2">
                <div
                  class="w-6 h-6 rounded-full bg-[#BC430D] flex items-center justify-center text-white flex-shrink-0 mt-1 shadow-sm">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                    <path
                      d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z" />
                  </svg>
                </div>
                <!-- Subtle background pada bot bubble -->
                <div
                  class="bg-white border border-gray-100 text-gray-700 text-[14px] leading-relaxed px-3.5 py-2.5 rounded-2xl rounded-tl-sm shadow-sm"
                  x-html="formatMessage(msg.text)"></div>
              </div>
            </template>

            <!-- User Message — warna branded oranye muda -->
            <template x-if="msg.type === 'user'">
              <div
                class="bg-[#FFF0E9] border border-[#FFDCCC] text-gray-800 max-w-[85%] rounded-2xl rounded-br-sm px-4 py-2.5 text-[14px] leading-relaxed"
                x-text="msg.text"></div>
            </template>
          </div>
        </template>

        <!-- Loading Indicator — warna branded -->
        <div x-show="isLoading" class="flex justify-start items-center max-w-[85%] gap-2">
          <div
            class="w-6 h-6 rounded-full bg-[#BC430D] flex items-center justify-center text-white flex-shrink-0 shadow-sm">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M20 3H4v10c0 2.21 1.79 4 4 4h6c2.21 0 4-1.79 4-4v-3h2c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm0 5h-2V5h2v3zM4 19h16v2H4z" />
            </svg>
          </div>
          <div class="bg-white border border-gray-100 rounded-2xl rounded-tl-sm shadow-sm flex space-x-1.5 px-4 py-3">
            <div class="w-1.5 h-1.5 bg-[#BC430D] rounded-full animate-bounce opacity-70"></div>
            <div class="w-1.5 h-1.5 bg-[#BC430D] rounded-full animate-bounce opacity-70" style="animation-delay: 0.2s">
            </div>
            <div class="w-1.5 h-1.5 bg-[#BC430D] rounded-full animate-bounce opacity-70" style="animation-delay: 0.4s">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Input Area -->
    <div class="p-3 sm:p-4 bg-white border-t border-gray-100 z-10 pb-2">
      <div
        class="relative rounded-2xl border border-gray-200 shadow-sm focus-within:border-[#BC430D] focus-within:ring-1 focus-within:ring-[#BC430D] bg-white transition-all">
        <form @submit.prevent="sendMessage" class="flex items-end">
          <textarea x-model="newMessage" @keydown.enter.prevent="sendMessage" placeholder="Tanya soal menu, jam buka, promo..."
            class="w-full bg-transparent p-4 pr-12 text-sm text-gray-800 placeholder-gray-400 border-none focus:outline-none focus:ring-0 resize-none overflow-hidden"
            rows="2" :disabled="isLoading"></textarea>

          <!-- Send Button -->
          <button type="submit" :disabled="isLoading || newMessage.trim() === ''"
            :class="(newMessage.trim() === '') ? 'bg-gray-100 text-gray-400' :
            'bg-[#BC430D] text-white hover:bg-[#a0380a]'"
            class="absolute right-3 bottom-3 p-1.5 rounded-full transition-colors disabled:opacity-70">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
            </svg>
          </button>
        </form>
      </div>
    </div>

    <!-- Footer Text -->
    <div class="text-center text-[11px] text-[#BC430D]/40 pb-3 bg-white">
      S7-Assistant dapat membuat kesalahan. Cek kembali balasan.
    </div>
  </div>
</div>

@push('scripts')
  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('chatbot', () => ({
        isOpen: false,
        hasStarted: false,
        newMessage: '',
        isLoading: false,
        messages: [],
        conversationHistory: [],

        quickSuggestions: [
          'Saya ingin melihat menu hari ini',
          'Apa kopi best seller di sini?',
          'Jam berapa cafe buka dan tutup?'
        ],

        toggleChat() {
          this.isOpen = !this.isOpen;
          if (this.isOpen && this.hasStarted) {
            setTimeout(() => this.scrollToBottom(), 100);
          }
        },

        sendSuggestion(text) {
          this.newMessage = text;
          this.sendMessage();
        },

        formatMessage(text) {
          return text.replace(/\*\*(.*?)\*\*/g, '<strong class="text-gray-900">$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>');
        },

        scrollToBottom() {
          const container = document.getElementById('chatbot-messages');
          if (container) {
            container.scrollTop = container.scrollHeight;
          }
        },

        limitMessages() {
          if (this.messages.length > 30) {
            this.messages = this.messages.slice(-25);
          }
        },

        async sendMessage() {
          if (this.newMessage.trim() === '') return;

          const messageToSend = this.newMessage;

          this.hasStarted = true;

          this.messages.push({
            type: 'user',
            text: messageToSend
          });
          this.conversationHistory.push({
            role: 'user',
            content: messageToSend
          });

          this.newMessage = '';
          this.isLoading = true;

          setTimeout(() => this.scrollToBottom(), 50);

          try {
            const historyToSend = this.conversationHistory.slice(-10);

            const response = await fetch('{{ route('chatbot.ask') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                message: messageToSend,
                history: historyToSend
              })
            });

            const data = await response.json();

            if (response.ok) {
              this.messages.push({
                type: 'bot',
                text: data.reply
              });
              this.conversationHistory.push({
                role: 'assistant',
                content: data.reply
              });
            } else {
              const errorMsg = data.reply || 'Terjadi kesalahan saat memproses pesan.';
              this.messages.push({
                type: 'bot',
                text: errorMsg
              });
            }
          } catch (error) {
            console.error('Chatbot error:', error);
            this.messages.push({
              type: 'bot',
              text: 'Gagal terhubung ke server. Silakan coba lagi.'
            });
          } finally {
            this.isLoading = false;
            this.limitMessages();
            setTimeout(() => this.scrollToBottom(), 50);
          }
        }
      }));
    });
  </script>
@endpush
