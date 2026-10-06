<script>
    document.querySelectorAll('form[data-loading-form="true"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                return;
            }

            setTimeout(function () {
                if (event.defaultPrevented) {
                    return;
                }

                const button = form.querySelector('button[type="submit"]');
                if (!button || button.disabled) {
                    return;
                }

                button.dataset.originalText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Procesando...';
            }, 0);
        });
    });
</script>
