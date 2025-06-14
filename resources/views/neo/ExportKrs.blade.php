@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">Export Kartu Rencana Studi</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="row">
                <!-- Filter -->
                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="program_studi" class="form-label">Program Studi</label>
                        <select name="program_studi" class="form-select" id="program_studi"
                            data-placeholder="Pilih Program Studi">
                            <option></option>
                            @foreach ($prodi as $item)
                                <option value="{{ $item['kode_program_studi'] }}">
                                    {{ $item['nama_jenjang_pendidikan'] . ' - ' . $item['nama_program_studi'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="tahun_akademik" class="form-label">Tahun Akademik</label>
                        <select name="tahun_akademik" class="form-select" id="tahun_akademik"
                            data-placeholder="Pilih Tahun Akademik">
                            <option></option>
                            @foreach ($tahun_akademik as $item)
                                <option value="{{ $item['periode_pelaporan'] }}">{{ $item['periode_pelaporan'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
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
                <div class="col-md-3">
                    <div class="mb-2">
                        <label for="kelas_perkuliahan" class="form-label">Kelas Perkuliahan</label>
                        <select name="kelas_perkuliahan" class="form-select" id="kelas_perkuliahan"
                            data-placeholder="Pilih Kelas Perkuliahan">
                            <option></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NIM</th>
                            <th>NAMA MAHASISWA</th>
                            <th>SEMESTER</th>
                            <th>KODE MK</th>
                            <th>NAMA MATA KULIAH</th>
                            <th>NAMA KELAS</th>
                            <th>KODE PRODI</th>
                            <th>NAMA PRODI</th>
                            <th>KETERANGAN</th>
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
        $(document).ready(function() {
            $('#program_studi, #tahun_akademik, #tahun_angkatan, #kelas_perkuliahan').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: function() {
                    return $(this).data('placeholder');
                }
            });

            var table = $('#example').DataTable({
                processing: true,
                serverSide: true,
                lengthChange: false,
                paging: false,
                searching: true,
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
                        $.fn.dataTable.ext.buttons.copyHtml5.action.call(this, e, dt, node,config);
                        Lobibox.notify('success', {
                            pauseDelayOnHover: true,
                            continueDelayOnInactiveTab: false,
                            position: 'top right',
                            icon: 'bx bx-check-circle',
                            msg: 'Data berhasil disalin ke clipboard!'
                        });
                    }
                }, {
                    extend: 'pdf',
                    title: '',
                    text: '<i class="bx bx-file" title="Export ke PDF"></i>',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {
                        columns: ':visible'
                    },
                    customize: function(doc) {
                        let tableIndex = doc.content.findIndex(c => c.table !== undefined);
                        if (tableIndex === -1) return;

                        let tableBody = doc.content[tableIndex].table.body;

                        for (let i = 0; i < tableBody.length; i++) {
                            for (let j = 0; j < tableBody[i].length; j++) {
                                if (i === 0) {
                                    tableBody[i][j].alignment = 'center';
                                } else {
                                    if (j === 2 || j === 5) {
                                        tableBody[i][j].alignment = 'left';
                                    } else {
                                        tableBody[i][j].alignment = 'center';
                                    }
                                }
                            }
                        }

                        if (doc.styles && doc.styles.tableHeader) {
                            doc.styles.tableHeader.alignment = 'center';
                        }

                        if (doc.defaultStyle) {
                            doc.defaultStyle.fontSize = 10;
                        }
                    }
                }],
                ajax: {
                    url: "{{ url()->current() }}",
                    data: function(d) {
                        d.program_studi = $('#program_studi').val();
                        d.tahun_akademik = $('#tahun_akademik').val();
                        d.tahun_angkatan = $('#tahun_angkatan').val();
                        d.kelas_perkuliahan = $('#kelas_perkuliahan').val();
                    },
                    error: function(xhr, error, thrown) {
                        let msg = 'Terjadi kesalahan saat memuat data.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            msg = xhr.responseText;
                        }

                        Lobibox.notify('error', {
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
                        data: 'Semester',
                        name: 'Semester'
                    },
                    {
                        data: 'KodeMK',
                        name: 'KodeMK'
                    },
                    {
                        data: 'NamaMK',
                        name: 'NamaMK'
                    },
                    {
                        data: 'NamaKelas',
                        name: 'NamaKelas'
                    },
                    {
                        data: 'ProdiID',
                        name: 'ProdiID'
                    },
                    {
                        data: 'NamaProdi',
                        name: 'NamaProdi'
                    },
                    {
                        data: 'Keterangan',
                        name: 'Keterangan'
                    },
                ],
                rowCallback: function(row, data, index) {
                    if (data.Keterangan === 'Dari Akademik') {
                        $(row).addClass('table-danger');
                    }
                }
            });

            function loadKRS() {
                let programStudi = $('#program_studi').val();
                let tahunAkademik = $('#tahun_akademik').val();
                let tahunAngkatan = $('#tahun_angkatan').val();
                let kelasPerkuliahan = $('#kelas_perkuliahan').val();
                if (programStudi && tahunAkademik && tahunAngkatan && kelasPerkuliahan) {
                    table.ajax.reload();
                }
            }

            function loadKelasPerkuliahan() {
                let programStudi = $('#program_studi').val();
                let tahunAkademik = $('#tahun_akademik').val();
                if (programStudi && tahunAkademik) {
                    $.ajax({
                        url: "{{ route('neofeeder.getkelasperkuliahan') }}",
                        dataType: "json",
                        data: {
                            program_studi: $('#program_studi').val(),
                            tahun_akademik: $('#tahun_akademik').val(),
                        },
                        success: function(response) {
                            if (response.error_code === 0) {
                                const kelasSelect = $('#kelas_perkuliahan');
                                kelasSelect.empty();
                                kelasSelect.append(`<option value="">Pilih Kelas Perkuliahan</option>`);
                                response.data.forEach(function(namaKelas) {
                                    kelasSelect.append(
                                        `<option value="${namaKelas}">${namaKelas}</option>`
                                    );
                                });
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading kelas perkuliahan:', xhr.responseText);
                        }
                    });
                }
            }

            $('#program_studi, #tahun_akademik').on('change', function() {
                loadKelasPerkuliahan();
            });

            $('#program_studi, #tahun_akademik, #tahun_angkatan, #kelas_perkuliahan').on('change', function() {
                loadKRS();
            });

            table.buttons().container()
                .appendTo('#example2_wrapper .col-md-6:eq(0)');
        });
    </script>

    <script src="{{ asset('') }}assets/plugins/notifications/js/lobibox.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/notifications/js/notifications.min.js"></script>
@endpush
