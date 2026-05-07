@push('js')
<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function showToast(message, theme = 'success') {
        const holder = document.getElementById('erp-toast-holder') || (() => {
            const node = document.createElement('div');
            node.id = 'erp-toast-holder';
            node.className = 'position-fixed';
            node.style.cssText = 'top:1rem;right:1rem;z-index:1080;';
            document.body.appendChild(node);
            return node;
        })();

        const toast = document.createElement('div');
        toast.className = `toast bg-${theme} text-white`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.setAttribute('data-delay', '3500');
        toast.innerHTML = `
            <div class="toast-header bg-${theme} text-white border-0">
                <strong class="mr-auto">Notification</strong>
                <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">${message}</div>
        `;

        holder.appendChild(toast);

        if (window.bootstrap?.Toast) {
            const instance = new bootstrap.Toast(toast);
            instance.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
            return;
        }

        if (window.jQuery) {
            window.jQuery(toast).toast('show');
            window.jQuery(toast).on('hidden.bs.toast', function () { toast.remove(); });
        }
    }

    async function submitAjaxForm(form, options = {}) {
        const formData = new FormData(form);
        const method = (form.querySelector('input[name="_method"]')?.value || form.method || 'POST').toUpperCase();
        const response = await fetch(form.action, {
            method: method === 'GET' ? 'POST' : method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const firstError = payload?.errors ? Object.values(payload.errors)[0]?.[0] : null;
            throw new Error(firstError || payload?.message || 'Operation echouee.');
        }

        showToast(payload.message || options.successMessage || 'Operation terminee.', 'success');
        if (typeof options.onSuccess === 'function') {
            options.onSuccess(payload);
        }

        return payload;
    }

    document.querySelectorAll('form[data-ajax="true"]').forEach((form) => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            const reload = form.dataset.reload !== 'false';
            const modalId = form.dataset.modalId;

            try {
                await submitAjaxForm(form, {
                    onSuccess: () => {
                        if (modalId) {
                            const modalEl = document.getElementById(modalId);
                            if (modalEl && window.jQuery) {
                                window.jQuery(modalEl).modal('hide');
                            }
                        }

                        if (reload) {
                            setTimeout(() => window.location.reload(), 300);
                        }
                    }
                });
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
    });

    document.querySelectorAll('form[data-ajax-delete="true"]').forEach((form) => {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (!confirm(form.dataset.confirm || 'Confirmer cette action ?')) {
                return;
            }

            try {
                await submitAjaxForm(form, {
                    onSuccess: () => setTimeout(() => window.location.reload(), 300)
                });
            } catch (error) {
                showToast(error.message, 'danger');
            }
        });
    });
})();
</script>
@endpush
