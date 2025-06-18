<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::with('roles')->orderBy('name')->get();
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('roles', function ($user) {
                    return $user->roles->map(function ($role) {
                        return '<span class="badge bg-primary rounded-pill me-1">' . $role->name . '</span></br>';
                    })->implode('');
                })
                ->addColumn('action', function ($user) {
                    $btn = '<button type="button" data-id="' . $user->id . '" class="edit btn btn-primary btn-sm"><i class="bx bx-edit me-0"></i></button>';
                    $btn .= ' <button type="button" data-id="' . $user->id . '" class="delete btn btn-danger btn-sm"><i class="bx bx-trash me-0"></i></button>';
                    return $btn;
                })
                ->rawColumns(['action', 'roles'])
                ->make(true);
        }

        return view('user.Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $data['roles'] = Role::select('id', 'name', 'guard_name')->get();
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
                'email' => 'required|email|unique:users',
                'password' => 'required|min:6',
                'role' => 'array',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            if ($request->has('role')) {
                $user->syncRoles($validated['role']);
            } else {
                $user->syncRoles([]);
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
            $data['roles'] = Role::select('id', 'name', 'guard_name')->get();
            $data['user'] = User::with('roles')->findOrFail($request->id);
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
            $user = User::findOrFail($request->id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|min:6',
                'role' => 'array',
            ]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }
            $user->save();


            if ($request->has('role')) {
                $user->syncRoles($request->role);
            } else {
                $user->syncRoles([]);
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
    public function destroy(User $user)
    {
        if (Auth::user()->id  == $user->id) {
            return response()->json([
                'error_code' => 1,
                'error_desc' => 'Anda tidak dapat menghapus akun Anda sendiri.',
                'message'    => 'Gagal menghapus pengguna',
            ], 403);
        }

        $user->delete();
        return response()->json([
            'error_code' => 0,
            'error_desc' => '',
            'message' => 'Data berhasil dihapus',
            'data' => []
        ], 200);
    }
}
