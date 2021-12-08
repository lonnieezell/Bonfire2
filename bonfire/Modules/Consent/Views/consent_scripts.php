<!-- Consent -->
<script>
    var ConsentForm = {

        cookieName: 'bf_consent',
        cookieLength: 180,  // days
        consentForm: null,
        cookie: null,

        init: function() {
            this.consentForm = document.getElementById('consent-popup');
            this.cookie = this.getCookie(this.cookieName);

            // Customize Consent Toggle
            document.getElementById('consent-customize').addEventListener('click', function() {
                ConsentForm.toggleCustomConsent();
            }, false);

            // Close consent form
            document.getElementById('consent-close').addEventListener('click', function() {
                ConsentForm.closeForm();
            }, false);

            // Reject All
            document.getElementById('consent-reject-all').addEventListener('click', function() {
                ConsentForm.rejectAll();
            }, false);

            // Accept All
            document.getElementById('consent-accept-all').addEventListener('click', function() {
                ConsentForm.acceptAll();
            }, false);

            // Accept Custom
            document.getElementById('consent-accept-custom').addEventListener('click', function() {
                ConsentForm.acceptCustom();
            }, false);
        },

        /**
         * Toggles display between simple (Accept All) and customized
         * consent form display.
         */
        toggleCustomConsent: function() {
            let state = this.consentForm.getAttribute('data-state');

            if (state === 'simple') {
                this.consentForm.classList.add('custom');
                this.consentForm.setAttribute('data-state', 'custom');
                return;
            }

            this.consentForm.classList.remove('custom');
            this.consentForm.setAttribute('data-state', 'simple');
        },

        /**
         * Close the Consent Form window.
         */
        closeForm: function() {
            this.consentForm.style.display = 'none';
        },

        /**
         * Retrieves a cookie by name
         */
        getCookie: function(name) {
            const cookies = document.cookie.split(';');

            for (let i = 0; i < cookies.length; i++) {
                let c = cookies[i].trim().split('=');
                if (c[0] === name) {
                    return c[1];
                }
            }
            return "";
        },

        /**
         * Sets a cookie.
         *
         * @param name
         * @param value
         * @param days
         * @param path
         * @param domain
         * @param secure
         */
        setCookie: function(name, value, days, path, domain, secure) {
            let cookie = `${name}=${encodeURIComponent(value)}`;

            // Add expiry date
            if (days) {
                const expiry = new Date();
                expiry.setDate(expiry.getDate() + days);
                cookie += `; expires=${expiry.toUTCString()}`;
            }

            // Add Path, Domain, and Secure
            if (path) cookie += `; path=${path}`;
            if (domain) cookie += `; domain=${domain}`;
            if (secure) cookie += `; secure`;

            // Set an HTTP cookie
            document.cookie = cookie;
        },

        /**
         * Rejects all non-essential cookies.
         */
        rejectAll: function() {
            let values = this.getCookieForm(0);

            this.setCookie('bf_consent', JSON.stringify(values), this.cookieLength);
            this.closeForm();
        },

        /**
         * Accept all non-essential cookies.
         */
        acceptAll: function() {
            let values = this.getCookieForm(1);

            this.setCookie('bf_consent', JSON.stringify(values), this.cookieLength);
            this.closeForm();
        },

        /**
         * Accept custom cookies.
         */
        acceptCustom: function() {
            let values = this.getCookieForm();

            this.setCookie('bf_consent', JSON.stringify(values), this.cookieLength);
            this.closeForm();

            console.log(values)
        },

        /**
         * Retrieves the basic cookie format
         */
        getCookieForm: function(defaultValue=null) {
            let values = {
                'consent': 1, // the user has answered
            };

            // Get the aliases of all consent items
            const checks = document.getElementsByClassName('consent-check');
            for(i=0; i < checks.length; i++) {
                let name = checks[i].name;
                values[name] = defaultValue !== null ? defaultValue : checks[i].checked | 0;
            }

            return values;
        },
    };

    ConsentForm.init();
</script>
