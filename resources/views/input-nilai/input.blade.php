@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">Input Nilai Semester</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <table>
                <tr>
                    <td>Nama Dosen </td>
                    <td>: {{ $jadwal->NamaDosen ?? '' }}</td>
                </tr>
                <tr>
                    <td>Program Studi </td>
                    <td>: {{ $jadwal->ProdiNama ?? '' }}</td>
                </tr>
                <tr>
                    <td>Nama Kelas </td>
                    <td>: {{ $jadwal->NamaKelas ?? '' }}</td>
                </tr>
                <tr>
                    <td>Nama Ruang </td>
                    <td>: {{ $jadwal->NamaRuang ?? '' }}</td>
                </tr>
                <tr>
                    <td>Hari, Jam </td>
                    <td>: {{ $jadwal->HariNama ?? '' }},
                        {{ $jadwal->jam_mulai ?? '00:00:00' }}-{{ $jadwal->jam_selesai ?? '00:00:00' }} WIB
                    </td>
                </tr>
            </table>
            <form id="form-kirim-pesan">
                @csrf
                <input type="hidden" name="id" value="{{ Crypt::encrypt($jadwal->id) }}">
                <a class="btn btn-sm btn-primary mb-1" href="{{ url()->current() . '/download-template' }}">Download
                    Template</a>
                <button id="NilaiPreview" class="btn btn-sm btn-success mb-1">Import Nilai</button>
                <a class="btn btn-sm btn-warning mb-1" href="{{ url()->current() . '/export' }}">Export Nilai</a>
                <button class="btn btn-sm btn-danger mb-1" onclick="window.close()">Selesai</button>
            </form>
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
                            <th>NILAI ANGKA</th>
                            <th>NILAI HURUF</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadModal" aria-labelledby="uploadLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formPreview" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" required>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="preview" class="btn btn-primary btn-sm">Preview</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="previewModal" aria-labelledby="previewLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="excel-preview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="save" class="btn btn-primary btn-sm">Import</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('css')
    <link href="{{ asset('') }}assets/plugins/fancy-file-uploader/fancy_fileupload.css" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/datatable/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/notifications/css/lobibox.min.css') }}" />
@endpush

@push('js')
    <script src="{{ asset('') }}assets/plugins/fancy-file-uploader/jquery.ui.widget.js"></script>
    <script src="{{ asset('') }}assets/plugins/fancy-file-uploader/jquery.fileupload.js"></script>
    <script src="{{ asset('') }}assets/plugins/fancy-file-uploader/jquery.iframe-transport.js"></script>
    <script src="{{ asset('') }}assets/plugins/fancy-file-uploader/jquery.fancy-fileupload.js"></script>
    <script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/notifications/js/lobibox.min.js') }}"></script>

    <script></script>
    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const fullUrl = window.location.href;
        const parts = fullUrl.split('/');
        const idJadwal = parts[parts.length - 1];

        let table = $('#example').DataTable({
            processing: true,
            serverSide: false,
            searching: true,
            paging: false,
            info: true,
            lengthChange: false,
            rowId: 'nim',
            ajax: {
                url: "{{ url()->current() }}",
                data: function(d) {
                    d.id_jadwal = idJadwal;
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    width: '30px'
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
                    data: 'nilai_angka',
                    name: 'nilai_angka',
                    className: 'editable',
                    render: function(data, type, row) {
                        return `<span data-field="nilai_angka">${data}</span>`;
                    }
                },
                {
                    data: 'nilai_huruf',
                    name: 'nilai_huruf'
                },
            ],
            rowCallback: function(row, data, index) {
                if (parseFloat(data.nilai_angka) < 55) {
                    $(row).addClass('table-danger');
                } else {
                    $(row).removeClass('table-danger');
                }
            }
        });

        $(document).on('click', '#example td.editable', function() {
            let td = $(this);
            if (td.find('input').length > 0) return;

            let original = td.text().trim();
            let columnIndex = td.index();
            let tr = td.closest('tr');
            let rowIndex = table.row(tr).index();
            let trElement = tr[0];

            let nim = tr.find('td').eq(1).text().trim();

            td.html(`<input type="number" class="form-control" min="0" max="100" value="${original}">`);
            let input = td.find('input');
            input.focus();

            input.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    let value = $(this).val().trim();
                    let number = parseFloat(value);

                    if (isNaN(number) || number < 0 || number > 100) {
                        alert("Nilai harus antara 0 sampai 100.");
                        td.text(original);
                        return;
                    }

                    let urlUpdate = "{{ url()->current() }}";
                    $.ajax({
                        url: urlUpdate,
                        method: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            _method: 'PUT',
                            nim: nim,
                            nilai_angka: number
                        },
                        success: function(response) {
                            Lobibox.notify('success', {
                                sound: false,
                                pauseDelayOnHover: true,
                                continueDelayOnInactiveTab: false,
                                position: 'top right',
                                icon: 'bx bx-check-circle',
                                msg: response.message
                            });

                            $.ajax({
                                url: "{{ url()->current() }}",
                                type: 'GET',
                                data: {
                                    draw: 1,
                                    start: rowIndex,
                                    length: 1,
                                    id_jadwal: idJadwal
                                },
                                success: function(res) {
                                    if (res.data && res.data.length > 0) {
                                        table.row(trElement).data(res.data[0]).draw(
                                            false);

                                        let nextRowIndex = rowIndex + 1;
                                        let nextTr = $('#example tbody tr').eq(
                                            nextRowIndex);
                                        let nextTd = nextTr.find('td').eq(
                                            columnIndex);
                                        if (nextTd.hasClass('editable')) {
                                            setTimeout(() => {
                                                nextTd.click();
                                                setTimeout(() => {
                                                    let input =
                                                        nextTd.find(
                                                            'input'
                                                        );
                                                    if (input
                                                        .length) {
                                                        input
                                                            .focus();
                                                        input
                                                            .select();
                                                    }
                                                }, 50);
                                            }, 100);
                                        }
                                    }
                                }
                            });
                        },
                        error: function(xhr) {
                            Lobibox.notify('error', {
                                sound: false,
                                pauseDelayOnHover: true,
                                continueDelayOnInactiveTab: false,
                                position: 'top right',
                                icon: 'bx bx-error',
                                msg: xhr.responseJSON.error_desc
                            });

                            td.text(original);
                        }
                    });
                } else if (e.key === 'Escape') {
                    td.text(original); // batal
                }
            });
        });
    </script>

    <script>
        $(document).on('click', '#NilaiPreview', function(e) {
            document.getElementById('formPreview').reset();
            $('#uploadTitle').text('UPLOAD NILAI');
            const myModal = new bootstrap.Modal(document.getElementById('uploadModal'));
            myModal.show();
        });

        $('#preview').on('click', function() {
            $('#formPreview').submit();
        });

        $('#formPreview').on('submit', function(e) {
            e.preventDefault();
            const url = `{{ url()->current() }}/preview/`;
            const formData = new FormData(this);

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        $('#previewTitle').text('PREVIEW NILAI SIAP IMPORT');
                        const myModal = new bootstrap.Modal(document.getElementById('previewModal'));
                        myModal.show();
                        $('#excel-preview').html(response.data);
                    } else {
                        alert('Data tidak valid!');
                    }
                },
                error: function() {
                    console.error('Failed', data);
                    alert('Gagal menyimpan perubahan.');
                }
            });

        });

        $('#form-kirim-pesan').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{ route('kirim-pesan') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    console.log(response);

                    if (response.error_code > 0) {
                        Lobibox.notify('error', {
                            sound: false,
                            pauseDelayOnHover: true,
                            continueDelayOnInactiveTab: false,
                            position: 'top right',
                            icon: 'bx bx-error',
                            msg: response.error_desc
                        });
                    } else {
                        Lobibox.notify('success', {
                            sound: false,
                            pauseDelayOnHover: true,
                            continueDelayOnInactiveTab: false,
                            position: 'top right',
                            icon: 'bx bx-check-circle',
                            msg: response.message
                        });
                    }
                },
                error: function(xhr) {
                    Lobibox.notify('error', {
                        sound: false,
                        pauseDelayOnHover: true,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: xhr.responseJSON.message
                    });
                }
            });
        });
    </script>
@endpush
