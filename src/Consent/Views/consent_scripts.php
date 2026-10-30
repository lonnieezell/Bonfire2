<!-- Consent -->
<script>
    (function () {
        const form = document.getElementById('consent-popup');

        if (! form) {
            return;
        }

        // Toggles between the simple (Accept All) and customized form.
        document.getElementById('consent-customize').addEventListener('click', function () {
            const custom = form.getAttribute('data-state') === 'simple';

            form.classList.toggle('custom', custom);
            form.setAttribute('data-state', custom ? 'custom' : 'simple');
        });

        // consent_settings_link() reopens the form.
        document.querySelectorAll('[data-consent-open]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                form.hidden = false;
            });
        });
    })();
</script>
