<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Permission::query();
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('action', function ($menu) {
                    $btn = '';
                    $btn .= ' <button type="button" data-id="' . $menu->id . '" class="delete btn btn-danger btn-sm"><i class="bx bx-trash me-0"></i></button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('permission.Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $data['routes'] = collect(Route::getRoutes())
                ->filter(function ($route) {
                    return in_array('web', $route->middleware()) && $route->getName() !== null;;
                })
                ->map(function ($route) {
                    return [
                        'name' => $route->getName(),
                    ];
                })
                ->values();
            $data['existing_permissions'] = Permission::pluck('name')->toArray();
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
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|max:255',
            ]);

            $existingPermissions = Permission::pluck('name')->toArray();
            $inputPermissions = $validated['permissions'] ?? [];

            Permission::whereNotIn('name', $inputPermissions)->delete();

            $toInsert = [];

            foreach (array_diff($inputPermissions, $existingPermissions) as $name) {
                $toInsert[] = [
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($toInsert)) {
                Permission::insert($toInsert);
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'message' => 'Data berhasil dihapus',
            'data' => $permission
        ], 200);
    }
}
