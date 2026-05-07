<div class="position-fixed top-0 right-0 p-3" style="z-index: 1080; right: 1rem;">
    @if (session('success'))
        <div class="toast bg-success text-white" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
            <div class="toast-header bg-success text-white border-0">
                <strong class="mr-auto"><i class="fas fa-check-circle mr-1"></i> Succès</strong>
                <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="toast bg-danger text-white" role="alert" aria-live="assertive" aria-atomic="true" data-delay="5000">
            <div class="toast-header bg-danger text-white border-0">
                <strong class="mr-auto"><i class="fas fa-exclamation-triangle mr-1"></i> Erreur</strong>
                <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                {{ session('error') }}
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="toast bg-warning text-dark" role="alert" aria-live="assertive" aria-atomic="true" data-delay="7000">
            <div class="toast-header bg-warning border-0">
                <strong class="mr-auto"><i class="fas fa-info-circle mr-1"></i> Validation</strong>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                Merci de vérifier les champs du formulaire.
            </div>
        </div>
    @endif
</div>

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var toastElements = document.querySelectorAll('.toast');
        toastElements.forEach(function (toastEl) {
            var toast = new bootstrap.Toast(toastEl);
            toast.show();
        });
    });
</script>
@endpush
