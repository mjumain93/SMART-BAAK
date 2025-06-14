@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">Data Mahasiswa</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="program_studi" class="form-label">Program Studi</label>
                        <select name="program_studi" class="form-select" id="program_studi"
                            data-placeholder="Pilih Program Studi">
                            <option></option>
                            @foreach ($prodi as $item)
                                <option value="{{ $item['kode_program_studi'] }}">
                                    {{ $item['nama_jenjang_pendidikan'] . ' - ' . $item['nama_program_studi'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2">
                        <label for="tahun_angkatan" class="form-label">Tahun Angkatan</label>
                        <select name="tahun_angkatan" class="form-select" id="tahun_angkatan"
                            data-placeholder="Pilih Tahun Angkatan">
                            <option></option>
                            @foreach ($tahun_angkatan as $item)
                                <option value="{{ $item }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="ms-auto">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Masukan Nomor Pokok Mahasiswa (NPM)"
                        aria-label="Masukan Nomor Pokok Mahasiswa" aria-describedby="npm" id="npm" name="npm">
                    <button class="btn btn-outline-secondary" type="button" id="npmCari">Cari</button>
                </div>
            </div>
            <hr>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%; vertical-align: middle">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NPM</th>
                            <th>NAMA MAHASISWA</th>
                            <th>KELAS PERKULIAHAN</th>
                            <th>PROGRAM STUDI</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="showData" aria-labelledby="dataLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="dataBody">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary">Cetak</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ asset('') }}assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link rel="stylesheet" href="{{ asset('') }}assets/plugins/notifications/css/lobibox.min.css" />
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $('#program_studi, #tahun_angkatan').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder');
            }
        });

        let activeFilter = null;

        const getFilterKRS = () => ({
            program_studi: $('#program_studi').val(),
            tahun_angkatan: $('#tahun_angkatan').val(),
            activeFilter: activeFilter
        });

        const getFilterByNpm = () => ({
            npm: $('#npm').val(),
            activeFilter: activeFilter
        });

        let table = $('#example').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            info: true,
            ajax: {
                url: "{{ url()->current() }}",
                data: function(d) {
                    if (activeFilter === 'krs') {
                        Object.assign(d, getFilterKRS());
                    } else if (activeFilter === 'npm') {
                        Object.assign(d, getFilterByNpm());
                    }
                },
                error: function(xhr, error, thrown) {
                    let msg = 'Terjadi kesalahan saat memuat data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        msg = xhr.responseText;
                    }

                    Lobibox.notify('error', {
                        sound: false,
                        pauseDelayOnHover: true,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: msg
                    });
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    width: '30px',
                },
                {
                    data: 'nim',
                    name: 'nim'
                },
                {
                    data: 'nama_lengkap',
                    name: 'nama_lengkap',
                    className: 'text-uppercase'
                },
                {
                    data: 'KelasID',
                    name: 'KelasID',
                    className: 'text-uppercase'
                },
                {
                    data: 'ProdiID',
                    name: 'ProdiID',
                    className: 'text-uppercase'
                },

                {
                    data: 'aksi',
                    name: 'aksi'
                },
            ],
            rowCallback: function(row, data, index) {
                if (data.nilai_angka < 60.00) {
                    $(row).addClass('table-danger');
                }
            }
        });

        $('#program_studi, #tahun_angkatan').on('change', function() {
            let f = getFilterKRS();
            if (f.program_studi && f.tahun_angkatan) {
                activeFilter = 'krs';
                table.ajax.reload();
            }
        });

        $('#npmCari').on('click', function() {
            let f = getFilterByNpm();
            if (f.npm) {
                activeFilter = 'npm';
                table.ajax.reload();
            } else {
                Lobibox.notify('error', {
                    sound: false,
                    pauseDelayOnHover: true,
                    continueDelayOnInactiveTab: false,
                    position: 'top right',
                    icon: 'bx bx-error',
                    msg: 'Nomor Pokok Mahasiswa (NPM) harus diisi'
                });
            }
        });
    </script>

    <script>
        $(document).on('click', '.getKrs', function(e) {
            lastFocusedElement = e.currentTarget;
            const nim = lastFocusedElement.getAttribute('data-nim');
            $.ajax({
                url: '{{ url()->current() }}',
                data: {
                    nim: nim
                },
                dataType: 'json',
                success: function(response) {
                    document.getElementById('dataTitle').innerHTML = 'KARTU RENCANA STUDI ' + response
                        .nama;
                    document.getElementById('dataBody').innerHTML = response.krs;
                    const myModal = new bootstrap.Modal(document.getElementById('showData'));
                    myModal.show();
                },
                error: function(xhr, status, error) {
                    document.getElementById('dataBody').innerHTML =
                        'Gagal mengambil data. Silakan coba lagi.';
                    console.error('AJAX Error:', error);
                }
            });
        });

        $(document).on('click', '.getKhs', function(e) {
            lastFocusedElement = e.currentTarget;
            const nim = lastFocusedElement.getAttribute('data-nim');
            document.getElementById('dataTitle').innerHTML = 'KARTU HASIL STUDI';
            document.getElementById('dataBody').innerHTML = 'Mahasiswa dengan NIM ' + nim;
            const myModal = new bootstrap.Modal(document.getElementById('showData'));
            myModal.show();
        });
    </script>

    <script src="{{ asset('') }}assets/plugins/notifications/js/lobibox.min.js"></script>
@endpush
