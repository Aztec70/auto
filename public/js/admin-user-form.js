document.addEventListener('DOMContentLoaded', function () {
    const adminUserForm = document.querySelector('[data-admin-user-form]');

    if (!adminUserForm) {
        return;
    }

    const roleSelect = document.getElementById(adminUserForm.dataset.roleId);
    const serviceSelect = document.getElementById(adminUserForm.dataset.serviceId);
    const serviceWrapper = document.getElementById(adminUserForm.dataset.serviceWrapperId);

    function toggleServiceField() {
        if (!roleSelect || !serviceSelect || !serviceWrapper) {
            return;
        }

        if (roleSelect.value === 'ROLE_EMPLOYEE') {
            serviceWrapper.style.display = 'block';
            serviceSelect.disabled = false;
        } else {
            serviceWrapper.style.display = 'none';
            serviceSelect.disabled = true;
            serviceSelect.value = '';
        }
    }

    toggleServiceField();
    roleSelect.addEventListener('change', toggleServiceField);
});