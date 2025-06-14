<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Role::with('permissions');;
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('permissions', function ($role) {
                    return $role->permissions->map(function ($permission) {
                        return '<span class="badge bg-primary rounded-pill me-1">' . $permission->name . '</span>';
                    })->implode('');
                })
                ->addColumn('action', function ($role) {
                    $btn = '<button type="button" data-id="' . $role->id . '" class="edit btn btn-primary btn-sm"><i class="bx bx-edit me-0"></i></button>';
                    $btn .= ' <button type="button" data-id="' . $role->id . '" class="delete btn btn-danger btn-sm"><i class="bx bx-trash me-0"></i></button>';
                    return $btn;
                })
                ->rawColumns(['action', 'permissions'])
                ->make(true);
        }

        return view('role.Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $data['permissions'] = Permission::select('id', 'name', 'guard_name')->get();
            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'data' => $data
            ], 200);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->ajax()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'permission' => 'array',
            ]);

            $role = Role::create(['name' => $validated['name']]);
            if ($request->has('permission')) {
                $role->syncPermissions($request->permission);
            } else {
                $role->syncPermissions([]);
            }

            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'message' => 'Data berhasil disimpan',
                'data' => []
            ], 200);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        if ($request->ajax()) {
            $data['permissions'] = Permission::select('id', 'name', 'guard_name')->get();
            $data['role'] = Role::with('permissions')->findOrFail($request->id);
            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'data' => $data
            ], 200);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if ($request->ajax()) {
            $validated = $request->validate([
                'id' => 'required|exists:roles,id',
                'name' => 'required|string|max:255',
                'permission' => 'array',
            ]);

            $role = Role::findOrFail($validated['id']);
            $role->name = $validated['name'];
            $role->save();


            if ($request->has('permission')) {
                $role->syncPermissions($request->permission);
            } else {
                $role->syncPermissions([]);
            }

            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'message' => 'Data berhasil disimpan',
                'data' => []
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'message' => 'Data berhasil dihapus',
            'data' => []
        ], 200);
    }
}
