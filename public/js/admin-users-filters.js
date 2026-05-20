document.addEventListener('DOMContentLoaded', function () {
    const serviceSelect = document.getElementById('serviceFilter');

    if (!serviceSelect) {
        return;
    }

    new TomSelect(serviceSelect, {
        create: false,
        maxOptions: 500,
        placeholder: 'Pasirinkite servisą',
        allowEmptyOption: true
    });
});