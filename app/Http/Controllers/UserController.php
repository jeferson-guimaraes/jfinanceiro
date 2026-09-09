<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Enums\StatusEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', Rule::in(array_column(RoleEnum::cases(), 'value'))],
            'status' => ['nullable', 'string', Rule::in(array_column(StatusEnum::cases(), 'value'))],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 30, 50, 100])],
        ]);

        $search = $validated['search'] ?? null;
        $role = $validated['role'] ?? null;
        $status = $validated['status'] ?? null;
        $perPage = $validated['per_page'] ?? 30;

        $usuarios = User::query()
            ->select(['id', 'name', 'email', 'role', 'status', 'created_at'])
            ->when($search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role, fn ($query, string $role) => $query->where('role', $role))
            ->when($status, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('usuarios/Index', [
            'usuarios' => $usuarios,
            'filters' => [
                'search' => $search ?? '',
                'role' => $role ?? '',
                'status' => $status ?? '',
                'per_page' => $perPage,
            ],
        ]);
    }
}
