<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Services\Observability\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function index(): View
    {
        $permissions = Permission::orderBy('name')->get();

        return view('access.permissions.index', compact('permissions'));
    }

    public function create(): View
    {
        return view('access.permissions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('permissions', 'name')],
        ]);

        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);
        $this->auditLogService->log('access.permission.created', 'permission', $permission->id, [
            'name' => $permission->name,
        ]);

        return redirect()->route('access.permissions.index')->with('success', 'Permission creee avec succes.');
    }

    public function edit(Permission $permission): View
    {
        return view('access.permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('permissions', 'name')->ignore($permission->id)],
        ]);

        $permission->update(['name' => $data['name']]);
        $this->auditLogService->log('access.permission.updated', 'permission', $permission->id, [
            'name' => $permission->name,
        ]);

        return redirect()->route('access.permissions.index')->with('success', 'Permission mise a jour avec succes.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();
        $this->auditLogService->log('access.permission.deleted', 'permission', $permission->id, [
            'name' => $permission->name,
        ]);

        return redirect()->route('access.permissions.index')->with('success', 'Permission supprimee avec succes.');
    }
}
