{{-- Nova's own partial is empty. The theme is inlined here, not registered with Nova::style(), because Nova only serves those to signed-in users and the login page needs it too. --}}
<style{{ \Laravel\Nova\Nova::cspNonceAttribute() }}>
{!! file_get_contents(resource_path('css/nova-theme.css')) !!}
</style>

@if (request()->routeIs('nova.pages.login') && ($demoLogin = \App\Nova\DemoLogin::credentials()))
    {{-- Fills the login form with the demo account; App\Nova\DemoLogin decides when that is safe. --}}
    <script{{ \Laravel\Nova\Nova::cspNonceAttribute() }}>
        (() => {
            const demoLogin = @json($demoLogin);

            // Nova draws the form with Vue after the page loads, so wait for the fields to appear.
            const observer = new MutationObserver(() => {
                const email = document.querySelector('input[name="email"]');
                const password = document.querySelector('input[name="password"]');

                if (! email || ! password) {
                    return;
                }

                observer.disconnect();

                // Setting .value alone doesn't reach Vue; the input event updates the form's v-model.
                for (const [input, value] of [[email, demoLogin.email], [password, demoLogin.password]]) {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            observer.observe(document.documentElement, { childList: true, subtree: true });
        })();
    </script>
@endif
