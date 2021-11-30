(function (document: Document) {
    const onAttendanceChange = function (e: any) {
        const id = e.target.name.replace('[attending]', '');
        [].forEach.call(document.querySelectorAll('[name*="' + id + '"]'), function (el: HTMLInputElement) {
            if (el === e.target) return;
            el.disabled = e.target.value !== '1';
            if (e.target.value !== '1') el.value = '';
        });
    };

    [].forEach.call(document.getElementsByClassName('js-attending'), function (el: HTMLInputElement) {
        el.addEventListener('change', onAttendanceChange);
        onAttendanceChange({ target: el });
    });
})(document);
