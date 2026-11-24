{{-- Nova's own partial is empty. The theme is inlined here, not registered with Nova::style(), because Nova only serves those to signed-in users and the login page needs it too. --}}
<style{{ \Laravel\Nova\Nova::cspNonceAttribute() }}>
{!! file_get_contents(resource_path('css/nova-theme.css')) !!}
</style>

@if (app()->isLocal() && filled(config('nova.demo_login.email')))
    {{-- Local demos only: fills the login form with NOVA_DEMO_EMAIL / NOVA_DEMO_PASSWORD (config/nova.php "demo_login"). --}}
    <script{{ \Laravel\Nova\Nova::cspNonceAttribute() }}>
        if (location.pathname.endsWith('/login')) {
            const demoLogin = @json(config('nova.demo_login'));

            // Nova draws the form with Vue after the page loads, so wait for the fields to appear.
            const observer = new MutationObserver(() => {
                const email = document.querySelector('input[name="email"]');
                const password = document.querySelector('input[name="password"]');

                if (! email || ! password) {
                    return;
                }

                observer.disconnect();

                // Setting .value alone doesn't reach Vue; the input event updates the form's v-model.
                for (const [input, value] of [[email, demoLogin.email], [password, demoLogin.password ?? '']]) {
                    input.value = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            observer.observe(document.documentElement, { childList: true, subtree: true });
        }
    </script>
@endif
