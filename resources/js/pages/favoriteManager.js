export default function favoriteManager(initialFavorites, toggleUrl, loginUrl, csrfToken) {
    return {
        favorites: (initialFavorites || []).map(Number),
        processing: [],
        toastMsg: null,
        toastVisible: false,
        toastTimer: null,

        async toggleFavorite(productId) {
            const pId = Number(productId);
            if (this.processing.includes(pId)) return;
            this.processing.push(pId);

            try {
                const response = await fetch(toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ id_produk: pId })
                });

                if (response.status === 401) {
                    window.location.href = loginUrl;
                    return;
                }

                const data = await response.json();
                if (data.status === 'added') {
                    this.favorites.push(pId);
                    this.showToast('Ditambahkan ke favorit');
                } else if (data.status === 'removed') {
                    this.favorites = this.favorites.filter(id => Number(id) !== pId);
                    this.showToast('Dihapus dari favorit');
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.processing = this.processing.filter(id => id !== pId);
            }
        },

        isFavorite(productId) {
            return this.favorites.includes(Number(productId));
        },

        showToast(message) {
            this.toastMsg = message;
            this.toastVisible = true;
            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastVisible = false;
                setTimeout(() => { this.toastMsg = null; }, 300);
            }, 1500);
        }
    };
}
