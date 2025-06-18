@extends('layouts.app')

@section('content')
    <h6 class="mb-0 text-uppercase">Data Menu</h6>
    <hr />
    <div class="card">
        <div class="card-body">
            <div class="ms-auto">
                <button id="add" type="button" class="btn btn-primary px-3"><i
                        class='bx bx-plus mr-0'></i>Tambah</button>
                <button id="sortMenu" type="button" class="btn btn-warning px-3"><i
                        class='bx bx-sort mr-0'></i>Urutkan</button>
            </div>
            <hr>
            <div class="table-responsive">
                <table id="example" class="table table-striped table-bordered" style="width:100%; vertical-align: middle">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA</th>
                            <th>ROUTE</th>
                            <th>ICON</th>
                            <th>PERMISSION</th>
                            <th>PARENT</th>
                            <th>ORDER</th>
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
                        <input type="hidden" id="menu_id" name="menu_id">
                        <div class="col-md-12">
                            <label for="name" class="form-label">Nama Menu</label>
                            <input type="text" class="form-control" name="name" id="name"
                                placeholder="Nama Menu">
                        </div>
                        <div class="col-md-12">
                            <label for="icon" class="form-label">Icon Menu</label>
                            <input type="text" class="form-control" name="icon" id="icon"
                                placeholder="Icon Menu">
                        </div>
                        <div class="col-md-12">
                            <label for="route" class="form-label">Route</label>
                            <select id="route" class="form-select" name="route">
                                <option selected>Choose...</option>
                                <option>One</option>
                                <option>Two</option>
                                <option>Three</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="permission" class="form-label">Permission</label>
                            <select id="permission" class="form-select" name="permission">
                                <option selected>Choose...</option>
                                <option>One</option>
                                <option>Two</option>
                                <option>Three</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="parent" class="form-label">Parent Menu</label>
                            <select id="parent" class="form-select" name="parent">
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
    <div class="modal fade" id="modalSort" aria-labelledby="sortLabel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">URUTAN MENU</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul id="menu-list" class="list-group"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" id="saveMenu" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('css')
    <link href="{{ asset('') }}assets/plugins/datatable/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('') }}assets/plugins/notifications/css/lobibox.min.css" />
    <style>
        ul.list-group {
            padding-left: 0;
            list-style: none;
        }

        ul.list-group ul {
            margin-left: 1.5rem;
            margin-top: 0.5rem;
        }

        .list-group-item {
            cursor: move;
            background-color: #f8f9fa;
            margin-bottom: 6px;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem 1rem;
            transition: background-color 0.2s ease;
        }

        .list-group-item:hover {
            background-color: #e9ecef;
        }

        .sortable-ghost {
            opacity: 0.4;
        }

        .sortable-chosen {
            background: #d0ebff;
        }
    </style>
@endpush
@push('js')
    <script src="{{ asset('') }}assets/plugins/datatable/js/jquery.dataTables.min.js"></script>
    <script src="{{ asset('') }}assets/plugins/datatable/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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
                    data: 'text',
                    name: 'text'
                },
                {
                    data: 'route',
                    name: 'route'
                },
                {
                    data: 'icon',
                    name: 'icon'
                },
                {
                    data: 'permission',
                    name: 'permission'
                },
                {
                    data: 'parent_text',
                    name: 'parent_text'
                },
                {
                    data: 'order',
                    name: 'order'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $(document).on('click', '#add', function(e) {
            const url = `/menus/create`;
            $('#dataTitle').text('TAMBAH MENU');
            $('#menu_id').val('');
            $('#name').val('');
            $('#icon').val('');

            $('#route').empty().append('<option value="">-- Pilih Route --</option>');
            $('#permission').empty().append('<option value="">-- Pilih Permission --</option>');
            $('#parent').empty().append('<option value="">-- Pilih Parent --</option>');
            $.ajax({
                url: url,
                dataType: 'json',
                success: function(response) {
                    response.data.routes.forEach(route => {
                        $('#route').append(
                            `<option value="${route.name }">${route.name }</option>`
                        );
                    });
                    response.data.permissions.forEach(permission => {
                        $('#permission').append(
                            `<option value="${permission.name }">${permission.name }</option>`
                        );
                    });
                    response.data.parents.forEach(parent => {
                        if (parent.text != menu.text) {
                            $('#parent').append(
                                `<option value="${parent.id }">${parent.text }</option>`
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

        $(document).on('click', '.edit', function(e) {
            const menuId = $(this).data('id');
            const url = `/menus/${menuId}/edit`;
            $.ajax({
                url: url,
                data: {
                    id: menuId
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
            const menuId = $(this).data('id');

            if (confirm('Yakin ingin menghapus menu ini?')) {
                $.ajax({
                    url: `/menus/${menuId}`,
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

            const menuId = $('#menu_id').val();
            const isEdit = menuId !== '';
            const url = isEdit ? `/menus/${menuId}` : `/menus`;
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
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: response.message
                    });
                },
                error: function() {
                    alert('Gagal menyimpan perubahan.');
                }
            });
        });

        function renderMenu(menus) {
            const ul = $('<ul class="list-group mb-2"></ul>');

            menus.forEach(menu => {
                const li = $('<li class="list-group-item"></li>').text(menu.text).attr('data-id', menu.id);
                if (menu.children && menu.children.length) {
                    const childrenUl = renderMenu(menu.children);
                    li.append(childrenUl);
                }

                ul.append(li);
            });
            return ul;
        }

        function makeSortableRecursively(el) {
            Sortable.create(el[0], {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
            });

            el.find('ul').each(function() {
                makeSortableRecursively($(this));
            });
        }

        $('#sortMenu').on('click', function() {
            $.ajax({
                url: '{{ route('menus.json') }}',
                type: 'GET',
                dataType: 'json',
                success: function(menus) {
                    const menuList = $('#menu-list');
                    const rendered = renderMenu(menus);
                    menuList.replaceWith(rendered);
                    rendered.attr('id', 'menu-list');
                    makeSortableRecursively(rendered);

                    const modal1 = new bootstrap.Modal(document.getElementById('modalSort'));
                    modal1.show();
                }
            });
        });


        function getNestedOrder(list) {
            const order = [];
            list.children().each(function(index) {
                const li = $(this);
                const obj = {
                    id: li.data('id'),
                    order: index + 1,
                    parent_id: null,
                };
                const sub = li.children('ul');
                if (sub.length) {
                    obj.children = getNestedOrder(sub);
                    obj.children.forEach(child => child.parent_id = obj.id);
                }
                order.push(obj);
            });

            return order;
        }

        $('#saveMenu').on('click', function() {
            const structured = getNestedOrder($('#menu-list'));

            const flatOrder = [];

            function flatten(items) {
                items.forEach(item => {
                    flatOrder.push({
                        id: item.id,
                        order: item.order,
                        parent_id: item.parent_id
                    });
                    if (item.children) flatten(item.children);
                });
            }
            flatten(structured);

            $.ajax({
                url: '{{ route('menus.updateOrder') }}',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    menus: flatOrder
                }),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalSort'));
                    modal.hide();
                    Lobibox.notify('success', {
                        sound: false,
                        pauseDelayOnHover: true,
                        continueDelayOnInactiveTab: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: response.message
                    });

                    location.reload()
                },
                error: function() {
                    alert('Gagal menyimpan urutan!');
                }
            });
        });
    </script>
    <script src="{{ asset('') }}assets/plugins/notifications/js/lobibox.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush
