<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Menu::with('parent');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('parent_text', function ($menu) {
                    return $menu->parent ? $menu->parent->text : '-';
                })
                ->addColumn('action', function ($menu) {
                    $btn = '<button type="button" data-id="' . $menu->id . '" class="edit btn btn-primary btn-sm"><i class="bx bx-edit me-0"></i></button>';
                    $btn .= ' <button type="button" data-id="' . $menu->id . '" class="delete btn btn-danger btn-sm"><i class="bx bx-trash me-0"></i></button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('menu.Index');
    }
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
            $data['permissions'] = Permission::select('name', 'guard_name')->get();
            $data['parents'] = Menu::select('id', 'text')->get();
            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'data' => $data
            ], 200);
        }
    }
    public function store(Request $request)
    {
        if ($request->ajax()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'icon' => 'nullable|string|max:255',
                'route' => 'nullable|string|max:255',
                'permission' => 'nullable|string|max:255',
                'parent' => 'nullable|string|max:255'
            ]);

            $menu = Menu::create([
                'text' => $validated['name'],
                'icon' => $validated['icon'] ?? 'bx bx-category',
                'route' => $validated['route'] ?? null,
                'permission' => $validated['permission'] ?? null,
                'parent_id' => $validated['parent'] ?? null,
            ]);

            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'message' => 'Data berhasil disimpan',
                'data' => $menu
            ], 200);
        }
    }
    public function edit(Request $request)
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
            $data['permissions'] = Permission::select('name', 'guard_name')->get();
            $data['parents'] = Menu::select('id', 'text')->get();
            $data['menu'] = Menu::find($request->id);
            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'data' => $data
            ], 200);
        }
    }
    public function update(Request $request, $id)
    {
        if ($request->ajax()) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'icon' => 'nullable|string|max:255',
                'route' => 'nullable|string|max:255',
                'permission' => 'nullable|string|max:255',
                'parent' => 'nullable|string|max:255'
            ]);

            $menu = Menu::findOrFail($id);

            // Update data menu
            $menu->text = $validated['name'];
            $menu->icon = $validated['icon'] ?? null;
            $menu->route = $validated['route'] ?? null;
            $menu->permission = $validated['permission'] ?? null;
            $menu->parent_id = $validated['parent'] ?? null;

            $menu->save();

            return response()->json([
                'error_code' => 0,
                'error_desc' => '',
                'message' => 'Data berhasil disimpan',
                'data' => $menu
            ], 200);
        }
    }
    public function destroy(Menu $menu)
    {
        $menu->delete();
        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'message' => 'Data berhasil dihapus',
            'data' => $menu
        ], 200);
    }
    public function getMenuJson()
    {
        $menus = Menu::whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        return response()->json($menus);
    }
    public function updateOrder(Request $request)
    {
        $menus = $request->input('menus');

        if (!$menus || !is_array($menus)) {
            return response()->json([
                'error_code' => 1,
                'error_desc' => '',
                'message' => 'Data tidak valid',
                'data' => []
            ], 400);
        }

        foreach ($menus as $menu) {
            Menu::where('id', $menu['id'])->update([
                'order' => $menu['order'],
                'parent_id' => $menu['parent_id']
            ]);
        }
        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'message' => 'Urutan berhasil disimpan',
            'data' => []
        ], 200);
    }
}
