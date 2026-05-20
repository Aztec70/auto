document.addEventListener('DOMContentLoaded', function () {
    const toggleCheckboxes = document.querySelectorAll('[data-password-toggle]');

    toggleCheckboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            const targetIds = checkbox.dataset.passwordToggle
                .split(',')
                .map(function (id) {
                    return id.trim();
                })
                .filter(Boolean);

            targetIds.forEach(function (targetId) {
                const input = document.getElementById(targetId);

                if (!input) {
                    return;
                }

                input.type = checkbox.checked ? 'text' : 'password';
            });
        });
    });
});