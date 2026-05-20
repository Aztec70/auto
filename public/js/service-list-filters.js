document.addEventListener('DOMContentLoaded', function () {
    const filterWrapper = document.querySelector('[data-service-list-filters]');

    if (!filterWrapper) {
        return;
    }

    const categorySelect = document.getElementById(filterWrapper.dataset.categoryId);
    const serviceSelect = document.getElementById(filterWrapper.dataset.serviceId);

    if (!categorySelect || !serviceSelect) {
        return;
    }

    const categoryPlaceholderOption = categorySelect.querySelector('option[value=""]');
    const servicePlaceholderOption = serviceSelect.querySelector('option[value=""]');

    if (categoryPlaceholderOption) {
        categoryPlaceholderOption.hidden = true;
    }

    if (servicePlaceholderOption) {
        servicePlaceholderOption.hidden = true;
    }

    const categoryTomSelect = new TomSelect(categorySelect, {
        create: false,
        allowEmptyOption: false,
        plugins: ['clear_button'],
        placeholder: 'Visos kategorijos',
        maxOptions: 500,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });

    const serviceTomSelect = new TomSelect(serviceSelect, {
        create: false,
        allowEmptyOption: false,
        plugins: ['clear_button'],
        placeholder: 'Visi servisai',
        maxOptions: 500,
        sortField: {
            field: 'text',
            direction: 'asc'
        }
    });

    categoryTomSelect.removeOption('');
    serviceTomSelect.removeOption('');

    function refreshSelectOptions(tomSelectInstance, originalSelect, preserveValue = true) {
        const currentValue = preserveValue ? originalSelect.value : '';

        tomSelectInstance.clearOptions();

        originalSelect.querySelectorAll('option').forEach(function (option) {
            if (option.hidden) {
                return;
            }

            tomSelectInstance.addOption({
                value: option.value,
                text: option.textContent.trim()
            });
        });

        tomSelectInstance.refreshOptions(false);

        if (preserveValue && currentValue) {
            const stillVisibleOption = originalSelect.querySelector('option[value="' + currentValue + '"]');

            if (stillVisibleOption && !stillVisibleOption.hidden) {
                tomSelectInstance.setValue(currentValue, true);
            } else {
                tomSelectInstance.clear(true);
                originalSelect.value = '';
            }
        }
    }

    function filterServicesByCategory(selectedCategory = categorySelect.value) {
        serviceSelect.querySelectorAll('option').forEach(function (option) {
            if (option.value === '') {
                option.hidden = true;
                return;
            }

            const categories = option.getAttribute('data-categories');

            if (!selectedCategory) {
                option.hidden = false;
                return;
            }

            option.hidden = !categories || !categories.split(',').includes(selectedCategory);
        });

        refreshSelectOptions(serviceTomSelect, serviceSelect);
    }

    function filterCategoriesByService(selectedService = serviceSelect.value) {
        categorySelect.querySelectorAll('option').forEach(function (option) {
            if (option.value === '') {
                option.hidden = true;
                return;
            }

            const services = option.getAttribute('data-services');

            if (!selectedService) {
                option.hidden = false;
                return;
            }

            option.hidden = !services || !services.split(',').includes(selectedService);
        });

        refreshSelectOptions(categoryTomSelect, categorySelect);
    }

    filterServicesByCategory();
    filterCategoriesByService();

    if (categorySelect.value) {
        categoryTomSelect.setValue(categorySelect.value, true);
    }

    if (serviceSelect.value) {
        serviceTomSelect.setValue(serviceSelect.value, true);
    }

    categoryTomSelect.on('change', function (value) {
        categorySelect.value = value || '';
        filterServicesByCategory(value || '');
    });

    serviceTomSelect.on('change', function (value) {
        serviceSelect.value = value || '';
        filterCategoriesByService(value || '');
    });
});