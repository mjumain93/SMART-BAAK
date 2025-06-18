@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">Data Pengguna</h6>
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
                            <th>NAMA LENGKAP</th>
                            <th>EMAIL</th>
                            <th>ROLE PENGGUNA</th>
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
                            <label for="name" class="form-label">Nama Pengguna</label>
                            <input type="text" class="form-control" name="name" id="name"
                                placeholder="Nama Pengguna">
                        </div>
                        <div class="col-md-12">
                            <label for="email" class="form-label">Email</label>
                            <input type="text" class="form-control" name="email" id="email"
                                placeholder="Alamat Email">
                        </div>
                        <div class="col-md-12">
                            <label for="password" class="form-label">Password</label>
                            <input type="text" class="form-control" name="password" id="password"
                                placeholder="Password">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Role Pengguna</label>
                            <div id="role"></div>
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
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'roles',
                    name: 'roles'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        let path = 'users';

        $(document).on('click', '#add', function(e) {
            const url = `/${path}/create`;
            $('#dataTitle').text('TAMBAH ROLE PENGGUNA');
            $('#id').val('');
            $('#name').val('');
            $('#role').empty();
            $.ajax({
                url: url,
                dataType: 'json',
                success: function(response) {
                    response.data.roles.forEach(role => {
                        $('#role').append(
                            `<div class="form-check">
                    <input class="form-check-input" type="checkbox" name="role[]" 
                           value="${role.name}" id="role_${role.id}">
                    <label class="form-check-label" for="role_${role.id}">
                        ${role.name}
                    </label>
                </div>`
                        );
                    });

                    const myModal = new bootstrap.Modal(document.getElementById('modalMenu'));
                    myModal.show();
                },
                error: function(xhr, status, error) {
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
                    $('#dataTitle').text('EDIT PENGGUNA');
                    $('#id').val(response.data.user.id);
                    $('#name').val(response.data.user.name);
                    $('#email').val(response.data.user.email);
                    $('#password').val(response.data.user.password);
                    $('#role').empty();

                    response.data.roles.forEach(role => {
                        const checked = response.data.user.roles.map(p => p.name).includes(
                            role
                            .name) ? 'checked' : '';
                        $('#role').append(
                            `<div class="form-check">
                    <input class="form-check-input" type="checkbox" name="role[]" 
                           value="${role.name}" id="perm_${role.id}" ${checked}>
                    <label class="form-check-label" for="perm_${role.id}">
                        ${role.name}
                    </label>
                </div>`
                        );
                    });

                    const myModal = new bootstrap.Modal(document.getElementById('modalMenu'));
                    myModal.show();
                },
                error: function(xhr, status, error) {
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
                            msg: xhr.responseJSON.error_desc
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
