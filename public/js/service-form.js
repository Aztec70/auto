document.addEventListener('DOMContentLoaded', function () {
    const serviceForm = document.querySelector('[data-service-form]');

    if (!serviceForm) {
        return;
    }

    const categoriesId = serviceForm.dataset.categoriesId;
    const categorySelect = document.getElementById(categoriesId);
    const timeInputs = serviceForm.querySelectorAll('.js-timepicker');

    if (categorySelect) {
        new TomSelect(categorySelect, {
            plugins: ['remove_button'],
            create: false,
            maxItems: null,
            hideSelected: true,
            closeAfterSelect: false,
            placeholder: 'Pasirinkite kategorijas'
        });
    }

    timeInputs.forEach(function (input) {
        flatpickr(input, {
            locale: 'lt',
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            minuteIncrement: 30,
            locale: 'lt'
        });
    });
});