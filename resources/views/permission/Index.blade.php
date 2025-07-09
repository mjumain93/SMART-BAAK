@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">Data Permission Pengguna</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="ms-auto">
                <button id="add" type="button" class="btn btn-primary px-3"><i
                        class='bx bx-plus mr-0'></i>Tambah</button>
            </div>
            <hr>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%; vertical-align: middle">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAME</th>
                            <th>GUARD NAME</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalMenu" aria-labelledby="dataLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dataTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="row g-3" id="formMenu">
                        <div class="col-md-12 mb-2">
                            <div id="permission"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="save" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ asset('') }}assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('') }}assets/plugins/notifications/css/lobibox.min.css" />
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script>
        const table = $('#example').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ url()->current() }}',
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    width: '30px'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'guard_name',
                    name: 'guard_name'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        let path = 'permissions';

        $(document).on('click', '#add', function(e) {
            const url = `/${path}/create`;
            $('#dataTitle').text('TAMBAH PERMISSION');
            $.ajax({
                url: url,
                dataType: 'json',
                success: function(response) {
                    const existing = response.data.existing_permissions;
                    response.data.routes.forEach(permission => {
                        const isChecked = existing.includes(permission.name) ? 'checked' : '';
                        $('#permission').append(`
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="${permission.name}" id="perm-${permission.name}" ${isChecked}>
                                <label class="form-check-label" for="perm-${permission.name}">
                                    ${permission.name}
                                </label>
                            </div>
                        `);
                    });

                    const myModal = new bootstrap.Modal(document.getElementById('modalMenu'));
                    myModal.show();
                },
                error: function(xhr, status, error) {
                    document.getElementById('dataBody').innerHTML =
                        'Gagal mengambil data. Silakan coba lagi.';
                    console.error('AJAX Error:', error);
                }
            });
        });

        $(document).on('click', '.delete', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            if (confirm('Yakin ingin menghapus menu ini?')) {
                $.ajax({
                    url: `/${path}/${id}`,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'DELETE'
                    },
                    success: function(response) {
                        table.ajax.reload(null, false);
                        Lobibox.notify('success', {
                            sound: false,
                            pauseDelayOnHover: true,
                            position: 'top right',
                            icon: 'bx bx-check-circle',
                            msg: response.message
                        });
                    },
                    error: function(xhr) {
                        Lobibox.notify('error', {
                            sound: false,
                            pauseDelayOnHover: true,
                            position: 'top right',
                            icon: 'bx bx-error',
                            msg: 'Gagal menghapus data.'
                        });
                    }
                });
            }
        });

        $('#save').on('click', function() {
            $('#formMenu').submit();
        });

        $('#formMenu').on('submit', function(e) {
            e.preventDefault();
            const url = `/${path}`;;
            const formData = $(this).serialize();

            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    table.ajax.reload(null, false);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalMenu'));
                    modal.hide();
                    Lobibox.notify('success', {
                        sound: false,
                        pauseDelayOnHover: true,
                        position: 'top right',
                        icon: 'bx bx-check-circle',
                        msg: response.message
                    });
                },
                error: function() {
                    Lobibox.notify('error', {
                        sound: false,
                        pauseDelayOnHover: true,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: 'Data gagal disimpan.'
                    });
                }
            });
        });
    </script>

    <script src="{{ asset('') }}assets/plugins/notifications/js/lobibox.min.js"></script>
@endpush
