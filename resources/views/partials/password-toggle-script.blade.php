<script>
    (() => {
        if (window.appPasswordToggleInitialized) {
            return;
        }

        window.appPasswordToggleInitialized = true;

        const findPasswordInput = (button) => {
            const target = button.getAttribute('data-password-toggle');

            if (target) {
                return document.querySelector(target);
            }

            return button.closest('.input-group')?.querySelector('input');
        };

        const setToggleState = (button, isVisible) => {
            const showLabel = button.getAttribute('data-show-label') || 'Hiá»‡n máº­t kháº©u';
            const hideLabel = button.getAttribute('data-hide-label') || 'áº¨n máº­t kháº©u';
            const label = isVisible ? hideLabel : showLabel;
            const icon = button.querySelector('[data-password-toggle-icon]');

            button.setAttribute('title', label);
            button.setAttribute('aria-label', label);

            if (icon) {
                icon.classList.toggle('fi-rr-eye', ! isVisible);
                icon.classList.toggle('fi-rr-eye-crossed', isVisible);
            }
        };

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-password-toggle]');

            if (! button) {
                return;
            }

            const input = findPasswordInput(button);

            if (! input) {
                return;
            }

            event.preventDefault();

            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            setToggleState(button, ! isVisible);
        });

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-password-toggle]').forEach((button) => {
                const input = findPasswordInput(button);

                setToggleState(button, input?.type === 'text');
            });
        });
    })();
</script>

