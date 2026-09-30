{{--
    Komponen Alpine form Pegawai — satu definisi untuk Tambah dan Edit.

    Sebelumnya blok ini disalin utuh ke kedua view (±93% identik), sehingga
    setiap perbaikan harus ditulis dua kali dan rawan menyimpang.

    Data per halaman dioper lewat atribut data-* pada elemen x-data, BUKAN
    lewat x-init berisi string. Nilai yang disisipkan ke dalam ekspresi
    JavaScript bisa keluar dari tanda kutipnya — entitas HTML yang ditulis
    Blade di-decode ulang oleh browser saat atribut dibaca, sehingga tanda
    kutip tunggal pada input pengguna berubah menjadi kode yang dieksekusi.
    Atribut data-* dibaca sebagai teks biasa, jadi tidak pernah dieksekusi.

    Mengharapkan: $golonganPangkatList dan $pppkGolonganList dari controller.
--}}
<script>
function pegawaiForm() {
    var golonganPNS = @json($golonganPangkatList);
    var golonganPPPK = @json($pppkGolonganList);

    return {
        opdSelected: false,
        jabatanLoading: false,
        jabatanGagal: false,
        nip: '',
        nipLoading: false,
        nipError: '',
        nipSuccess: false,
        currentGolongan: '',
        currentJabatanId: null,

        init() {
            var data = this.$root.dataset;

            this.nip = data.nip || '';
            this.currentGolongan = data.golonganPangkat || '';
            this.currentJabatanId = data.jabatanId || null;

            var jenis = data.jenisKepegawaian || '';
            if (jenis) {
                this.onJenisKepegawaianChange(jenis);
            }

            if (data.indukId) {
                this.loadJabatan(data.indukId, data.jabatanPilih);
            }
        },

        /** NIP hanya bisa diurai saat tepat 18 digit angka. */
        get nipSiapDiisi() {
            return /^\d{18}$/.test(String(this.nip).trim());
        },

        isiTanggalLahirDariNip() {
            if (!this.nipSiapDiisi) {
                return;
            }

            this.nipLoading = true;
            this.nipError = '';
            this.nipSuccess = false;

            fetch('/admin/pegawai/extract-tanggal-lahir?nip=' + encodeURIComponent(String(this.nip).trim()))
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) {
                        this.$refs.tanggal_lahir.value = d.tanggal_lahir;
                        this.nipSuccess = true;
                        setTimeout(() => { this.nipSuccess = false; }, 2000);
                    } else {
                        this.nipError = d.message || 'NIP tidak valid';
                    }
                    this.nipLoading = false;
                }.bind(this))
                .catch(function() {
                    this.nipError = 'Gagal memproses NIP';
                    this.nipLoading = false;
                }.bind(this));
        },

        onJenisKepegawaianChange(jenis) {
            var list = jenis === 'PPPK' ? golonganPPPK : golonganPNS;
            var selected = this.currentGolongan;
            var select = document.querySelector('[name="golongan_pangkat"]');
            select.innerHTML = '<option value="">-- Pilih Golongan/Pangkat --</option>';
            Object.entries(list).forEach(function(_a) {
                var val = _a[0], label = _a[1];
                var opt = document.createElement('option');
                opt.value = val;
                opt.textContent = label;
                if (selected && val === selected) opt.selected = true;
                select.appendChild(opt);
            });
        },

        loadJabatan(opdId, preSelectId) {
            this.opdSelected = !!opdId;
            this.jabatanGagal = false;
            var select = this.$refs.jabatanSelect;
            select.innerHTML = '<option value="">-- Pilih Jabatan --</option>';
            if (!opdId) {
                this.jabatanLoading = false;
                return;
            }
            this.jabatanLoading = true;
            var currentId = this.currentJabatanId;
            fetch('/admin/jabatan/by-opd?unor_id=' + opdId)
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    select.innerHTML = '<option value="">-- Pilih Jabatan --</option>';
                    if (d.success && d.data) {
                        d.data.forEach(function(j) {
                            var opt = document.createElement('option');
                            opt.value = j.id;
                            opt.setAttribute('data-jenjang', j.jenjang || '');
                            var label = j.nama;
                            if (j.jenjang) {
                                label += ' — ' + j.jenjang;
                            }
                            if (j.unor_jalur) {
                                label += ' (' + j.unor_jalur + ')';
                            }
                            if (currentId && j.id == currentId) {
                                // Jabatan yang sedang dipegang tetap boleh dipilih
                                // walau kursinya terisi — itu kursinya sendiri.
                            } else if (j.terisi) {
                                label += ' (Terisi)';
                                opt.disabled = true;
                                opt.style.color = '#ef4444';
                            }
                            opt.textContent = label;
                            select.appendChild(opt);
                        });
                    }
                    // Utamakan pilihan yang tadi dikirim; kalau tidak ada,
                    // kembali ke jabatan yang sedang dipegang pegawai ini.
                    if (preSelectId) select.value = String(preSelectId);
                    this.jabatanLoading = false;
                }.bind(this))
                .catch(function() {
                    select.innerHTML = '<option value="">-- Gagal memuat --</option>';
                    this.jabatanGagal = true;
                    this.jabatanLoading = false;
                }.bind(this));
            select.onchange = null;
        }
    }
}
</script>
