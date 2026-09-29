{{--
    Komponen Alpine pohon Kebutuhan & Bezetting.

    Muatan awal dari server hanya berisi root dan anak langsungnya, sehingga
    tidak ada baris tersembunyi yang sempat ter-render. Saat node pertama kali
    dibuka, baris anaknya diambil dari data-children-url milik baris tersebut
    lalu disisipkan tepat setelahnya. Anak yang sudah dimuat tetap berada di
    DOM — membuka ulang tidak memuat ulang, dan menutup cukup menyembunyikan
    lewat x-show.

    Mengharapkan: <tbody> dengan atribut data-colspan.
--}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('treeData', () => ({
        colspan: 1,
        expandedItems: new Set(),
        loadedItems: new Set(),
        loadingItems: new Set(),

        init() {
            this.colspan = Number(this.$root.dataset.colspan) || 1;

            // Root dirender server dalam keadaan terbuka dan anak-anaknya sudah
            // ikut terkirim, jadi baris level 0 adalah node yang isinya sudah
            // ada di DOM. Diturunkan dari markup, bukan dari data terpisah,
            // supaya state awal tidak mungkin berbeda dari yang dirender.
            this.$root.querySelectorAll('tr[data-level="0"][data-id]').forEach(row => {
                const id = String(row.dataset.id);
                this.expandedItems.add(id);
                this.loadedItems.add(id);
            });
        },

        isVisible(id, parentId) {
            if (parentId === '' || parentId === 'u-0' || parentId === '0' || parentId === 0) return true;
            return this.expandedItems.has(String(parentId));
        },

        isExpanded(id) {
            return this.expandedItems.has(String(id));
        },

        async toggleNode(row) {
            const id = String(row.dataset.id);

            if (this.expandedItems.has(id)) {
                this.expandedItems.delete(id);
                this.collapseDescendants(id);
                return;
            }

            // Buka lebih dulu supaya baris status ikut terlihat; bila pemuatan
            // gagal node dibiarkan terbuka agar pesan errornya terbaca dan
            // klik berikutnya memuat ulang.
            this.expandedItems.add(id);

            if (!this.loadedItems.has(id)) {
                await this.loadChildren(row, id);
            }
        },

        async loadChildren(row, id) {
            if (this.loadingItems.has(id)) return;

            this.loadingItems.add(id);
            this.removeTransient(row);
            row.insertAdjacentHTML('afterend', this.statusRow(id, 'Memuat…', 'text-gray-400 italic'));

            try {
                const response = await fetch(row.dataset.childrenUrl, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);

                const data = await response.json();
                this.removeTransient(row);

                if (data.html) {
                    row.insertAdjacentHTML('afterend', data.html);
                }

                this.loadedItems.add(id);
            } catch (error) {
                this.removeTransient(row);
                row.insertAdjacentHTML('afterend', this.statusRow(id, 'Gagal memuat data. Klik baris untuk mencoba lagi.', 'text-red-600'));
            } finally {
                this.loadingItems.delete(id);
            }
        },

        /**
         * Baris status sementara. Isinya teks tetap, tidak ada data pengguna
         * yang disisipkan. x-show-nya mengikuti induk supaya ikut tersembunyi
         * bila induknya ditutup saat pemuatan masih berjalan.
         */
        statusRow(parentId, message, classes) {
            return '<tr data-transient x-show="isVisible(\'\', \'' + parentId + '\')">'
                + '<td colspan="' + this.colspan + '" class="px-3 py-3 text-sm ' + classes + '">' + message + '</td>'
                + '</tr>';
        },

        removeTransient(row) {
            const next = row.nextElementSibling;
            if (next && next.hasAttribute('data-transient')) next.remove();
        },

        collapseDescendants(parentId) {
            document.querySelectorAll('tr[data-parent-id="' + parentId + '"]').forEach(row => {
                const childId = row.dataset.id;
                this.expandedItems.delete(childId);
                this.collapseDescendants(childId);
            });
        },
    }));
});
</script>
