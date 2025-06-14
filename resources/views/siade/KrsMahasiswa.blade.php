@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">Kartu Rencana Studi Mahasiswa</h6>
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
                        <label for="tahun_akademik" class="form-label">Tahun Akademik</label>
                        <select name="tahun_akademik" class="form-select" id="tahun_akademik"
                            data-placeholder="Pilih Tahun Akademik">
                            <option></option>
                            @foreach ($tahun_akademik as $item)
                                <option value="{{ $item }}">{{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NPM</th>
                            <th>NAMA MAHASISWA</th>
                            <th>KODE MK</th>
                            <th>NAMA MATA KULIAH</th>
                            <th>DOSEN PENGAMPU</th>
                            <th>PERSETUJUAN PA</th>
                            <th>NILAI ANGKA</th>
                            <th>NILAI HURUF</th>
                            <th>NILAI BOBOT</th>
                            <th>NILAI MUTU</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
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
        $('#program_studi, #tahun_akademik').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: function() {
                return $(this).data('placeholder');
            }
        });

        let table = $('#example').DataTable({
            processing: true,
            serverSide: true,
            bLengthChange: false,
            paging: false,
            searching: true,
            info: true,
            ajax: {
                url: "{{ url()->current() }}",
                data: function(d) {
                    d.program_studi = $('#program_studi').val();
                    d.tahun_akademik = $('#tahun_akademik').val();
                },
                error: function(xhr, error, thrown) {
                    let msg = 'Terjadi kesalahan saat memuat data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        msg = xhr.responseText;
                    }

                    Lobibox.notify('warning', {
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
                    name: 'DT_RowIndex'
                },
                {
                    data: 'nim',
                    name: 'nim'
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'MkKode',
                    name: 'MkKode'
                },
                {
                    data: 'NamaID',
                    name: 'NamaID'
                },
                {
                    data: 'NamaDosen',
                    name: 'NamaDosen'
                },
                {
                    data: 'status_pa',
                    name: 'status_pa'
                },
                {
                    data: 'nilai_angka',
                    name: 'nilai_angka'
                },
                {
                    data: 'nilai_huruf',
                    name: 'nilai_huruf'
                },
                {
                    data: 'nilai_bobot',
                    name: 'nilai_bobot'
                },
                {
                    data: 'nilai_mutu',
                    name: 'nilai_mutu'
                },
            ],
            rowCallback: function(row, data, index) {
                if (data.nilai_angka < 60.00) {
                    $(row).addClass('table-danger');
                }
            }
        });

        function loadKRS() {
            let programStudi = $('#program_studi').val();
            let tahunAkademik = $('#tahun_akademik').val();
            if (programStudi && tahunAkademik) {
                table.ajax.reload();
            }
        }

        $('#program_studi, #tahun_akademik').on('change', function() {
            loadKRS();
        });
    </script>
    <script src="{{ asset('') }}assets/plugins/notifications/js/lobibox.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/notifications/js/notifications.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/notifications/js/notification-custom-script.js"></script>
@endpush
