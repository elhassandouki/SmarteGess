@csrf
@if(isset($role))
    @method('PUT')
@endif

<div class="form-group">
    <label for="name">Nom du role</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $role->name ?? '') }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>Permissions</label>
    <div class="row">
        @foreach($permissions as $permission)
            <div class="col-md-4">
                <div class="custom-control custom-checkbox">
                    <input
                        type="checkbox"
                        class="custom-control-input"
                        id="perm_{{ md5($permission->name) }}"
                        name="permissions[]"
                        value="{{ $permission->name }}"
                        {{ in_array($permission->name, old('permissions', $selectedPermissions ?? []), true) ? 'checked' : '' }}
                    >
                    <label class="custom-control-label" for="perm_{{ md5($permission->name) }}">{{ $permission->name }}</label>
                </div>
            </div>
        @endforeach
    </div>
</div>

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('access.roles.index') }}" class="btn btn-default">Annuler</a>

