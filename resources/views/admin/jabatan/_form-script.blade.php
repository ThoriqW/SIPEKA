{{--
    Komponen Alpine form Jabatan — satu definisi untuk Tambah dan Edit.

    Sebelumnya blok ini disalin utuh ke kedua view (hanya berbeda satu baris
    komentar), sehingga setiap perbaikan harus ditulis dua kali.

    Data per halaman dioper lewat atribut data-* pada elemen x-data, BUKAN
    lewat x-init berisi string. Nilai yang disisipkan ke dalam ekspresi
    JavaScript bisa keluar dari tanda kutipnya — entitas HTML yang ditulis
    Blade di-decode ulang oleh browser saat atribut dibaca Alpine, sehingga
    tanda kutip tunggal pada input pengguna berubah menjadi kode yang
    dieksekusi. Atribut data-* dibaca sebagai teks biasa, jadi tidak pernah
    dieksekusi.

    Mengharapkan: $jenjangOptions, $referensiJabatanData, $unorByInduk dari controller.
--}}
<script>
function jabatanForm() {
    var options = @json($jenjangOptions);
    var referensiData = @json($referensiJabatanData);
    var unorData = @json($unorByInduk);

    return {
        selectedJenis: '',
        hasChildren: false,
        unitList: [],
        namaJabatanList: [], subJabatanList: [], jenjangList: [],
        currentJenjang: '',
        currentUnitId: null,

        // Search state (only for Nama + Sub)
        namaSearch: '', namaOpen: false, namaSelected: '',
        subSearch: '', subOpen: false, subSelected: '',

        get filteredNamaList() { return this.filterList(this.namaJabatanList, this.namaSearch); },
        get filteredSubList() { return this.filterList(this.subJabatanList, this.subSearch); },

        filterList: function(list, s) {
            if (!s) return list;
            var q = s.toLowerCase();
            return list.filter(function(i) { return (i.nama || '').toLowerCase().includes(q); });
        },

        // Dipanggil Alpine sekali tanpa argumen; nilai awalnya dibaca dari
        // atribut data-* sehingga tidak ada lagi x-init berisi string.
        init: function() {
            var data = this.$root.dataset;

            // Dibaca sebagai state supaya pemilihan ulang di template x-for
            // tidak perlu menyisipkan nilai ke dalam ekspresi JavaScript.
            this.currentJenjang = data.jenjangTerpilih || '';
            this.currentUnitId = data.unitId || null;

            if (data.jenisJabatan) {
                this.onJenisChange(data.jenisJabatan, data.namaJabatan || '');
            }

            if (data.indukId) {
                this.unitList = unorData[data.indukId] || [];
                if (data.unitId) {
                    this.$nextTick(function() {
                        var sel = document.querySelector('[x-ref="unitSelect"]');
                        if (sel) sel.value = data.unitId;
                    });
                }
            }
        },

        onJenisChange: function(jenis, preNama) {
            this.selectedJenis = jenis;
            this.hasChildren = false;
            this.subJabatanList = [];
            this.subSearch = ''; this.subSelected = '';

            var pn = preNama || '';
            var parentName = pn.split(' - ')[0] || '';
            var subName = pn.split(' - ').slice(1).join(' - ') || '';

            this.jenjangList = [];
            if (jenis && options[jenis]) {
                this.jenjangList = Object.entries(options[jenis]).map(function(e) {
                    return {id: e[0], nama: e[1]};
                });
            }

            this.namaJabatanList = [];
            if (jenis && referensiData[jenis]) {
                this.namaJabatanList = referensiData[jenis].map(function(item) {
                    return {id: item.id, nama: item.nama, children: item.children || []};
                });
            }

            if (parentName) this.selectNamaByName(parentName, subName);
            this.updateHidden();
        },

        selectNamaByName: function(name, subName) {
            this.namaSearch = ''; this.namaSelected = ''; this.namaOpen = false;
            var match = this.namaJabatanList.find(function(i) { return i.nama === name; });
            if (match) {
                this.namaSelected = match.nama;
                if (match.children && match.children.length > 0) {
                    this.hasChildren = true;
                    this.subJabatanList = match.children;
                    if (subName) this.subSelected = subName;
                }
            }
        },

        selectNama: function(item) {
            this.namaOpen = false; this.namaSearch = ''; this.namaSelected = item.nama;
            this.hasChildren = false;
            this.subJabatanList = [];
            this.subSearch = ''; this.subSelected = '';
            if (item.children && item.children.length > 0) {
                this.hasChildren = true;
                this.subJabatanList = item.children;
            }
            this.updateHidden();
        },

        selectSub: function(item) {
            this.subOpen = false; this.subSearch = ''; this.subSelected = item.nama;
            this.updateHidden();
        },

        onIndukChange: function(indukId) {
            this.unitList = unorData[indukId] || [];
        },

        updateHidden: function() {
            if (this.hasChildren && this.subSelected) {
                this.$refs.namaJabatanHidden.value = this.namaSelected + ' - ' + this.subSelected;
            } else {
                this.$refs.namaJabatanHidden.value = this.namaSelected;
            }
        }
    };
}
</script>
