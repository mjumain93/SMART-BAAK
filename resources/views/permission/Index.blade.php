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
                        <input type="hidden" id="id" name="id">
                        <div class="col-md-12">
                            <label for="permission" class="form-label">Permission Route</label>
                            <select id="permission" class="form-select" name="permission">
                                <option selected>Choose...</option>
                                <option>One</option>
                                <option>Two</option>
                                <option>Three</option>
                            </select>
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
            $('#id').val('');
            $('#permission').empty().append('<option value="">-- Pilih Permission --</option>');
            $.ajax({
                url: url,
                dataType: 'json',
                success: function(response) {
                    response.data.routes.forEach(permission => {
                        $('#permission').append(
                            `<option value="${permission.name }">${permission.name }</option>`
                        );
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

        $(document).on('click', '.edit', function(e) {
            const id = $(this).data('id');
            const url = `/${path}/${id}/edit`;
            $.ajax({
                url: url,
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    const menu = response.data.menu;
                    $('#dataTitle').text('EDIT MENU');
                    $('#menu_id').val(menu.id);
                    $('#name').val(menu.text);
                    $('#icon').val(menu.icon);

                    $('#route').empty().append('<option value="">-- Pilih Route --</option>');

                    response.data.routes.forEach(route => {
                        const selected = route.name === menu.route ? 'selected' : '';
                        $('#route').append(
                            `<option value="${route.name }" ${selected}>${route.name }</option>`
                        );
                    });

                    $('#permission').empty().append('<option value="">-- Pilih Permission --</option>');

                    response.data.permissions.forEach(permission => {
                        const selected = permission.name === menu.permission ? 'selected' : '';
                        $('#permission').append(
                            `<option value="${permission.name }" ${selected}>${permission.name }</option>`
                        );
                    });

                    $('#parent').empty().append('<option value="">-- Pilih Parent --</option>');

                    response.data.parents.forEach(parent => {
                        const selected = parent.id === menu.parent_id ? 'selected' : '';
                        if (parent.text !== menu.text) {
                            $('#parent').append(
                                `<option value="${parent.id }" ${selected}>${parent.text }</option>`
                            );
                        }
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

            const id = $('#id').val();
            const isEdit = id !== '';
            const url = isEdit ? `/${path}/${id}` : `/${path}`;
            const method = isEdit ? 'PUT' : 'POST';
            const formData = $(this).serialize() + (isEdit ? '&_method=PUT' : '');

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
