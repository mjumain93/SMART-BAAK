@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">data Mahasiswa di neo feeder</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <input type="search" class="form-control" id="search"
                    placeholder="Masukan Nama Mahasiswa/Nomor Pokok Mahasiswa (NPM)">
            </div>
            <hr>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NOMOR POKOK MAHASISWA</th>
                            <th>NAMA MAHASISWA</th>
                            <th>PROGRAM STUDI</th>
                            <th>PERIODE MASUK</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
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
        $('#search').on('input', function() {
            let keyword = $(this).val().trim();

            if (keyword.length >= 3 || keyword.length === 0) {
                $('#example').DataTable().ajax.reload();
            }
        });
        $(document).ready(function() {
            $('#search').val('').focus();
            var table = $('#example').DataTable({
                processing: true,
                serverSide: true,
                lengthChange: false,
                paging: false,
                searching: false,
                info: true,
                dom: "<'row mb-3'<'col-md-6'B><'col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-md-5'i><'col-md-7'p>>",
                buttons: [{
                    extend: 'copy',
                    title: '',
                    header: false,
                    text: '<i class="bx bx-copy-alt" title="Salin ke clipboard"></i>',
                    exportOptions: {
                        columns: ':visible',
                    },
                    action: function(e, dt, node, config) {
                        $.fn.dataTable.ext.buttons.copyHtml5.action.call(this, e, dt, node,
                            config);
                        Lobibox.notify('success', {
                            pauseDelayOnHover: true,
                            continueDelayOnInactiveTab: false,
                            position: 'top right',
                            icon: 'bx bx-check-circle',
                            msg: 'Data berhasil disalin ke clipboard!'
                        });
                    }
                }],
                ajax: {
                    url: "{{ url()->current() }}",
                    data: function(d) {
                        d.search = $('#search').val();
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
                        orderable: false,
                        searchable: false,
                        width: '30px',
                    },
                    {
                        data: 'nim',
                        name: 'nim'
                    },
                    {
                        data: 'nama_mahasiswa',
                        name: 'nama_mahasiswa'
                    },
                    {
                        data: 'nama_program_studi',
                        name: 'nama_program_studi'
                    },
                    {
                        data: 'nama_periode_masuk',
                        name: 'nama_periode_masuk'
                    },
                    {
                        data: 'nama_status_mahasiswa',
                        name: 'nama_status_mahasiswa'
                    },
                ],
            });

            function loadKRS() {
                if (programStudi && tahunAkademik && tahunAngkatan && kelasPerkuliahan) {
                    table.ajax.reload();
                }
            }

            $('#program_studi, #tahun_akademik').on('change', function() {
                loadKelasPerkuliahan();
            });

            table.buttons().container()
                .appendTo('#example2_wrapper .col-md-6:eq(0)');
        });
    </script>

    <script src="{{ asset('') }}assets/plugins/notifications/js/lobibox.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/notifications/js/notifications.min.js"></script>
@endpush
