require('./styles/index.scss');

(function (document: Document) {
    const menu = document.querySelector<HTMLInputElement>('.menu');
    const hideBtn = document.querySelector<HTMLInputElement>('#js-mobile-hide-menu');
    const showBtn = document.querySelector<HTMLInputElement>('#js-mobile-show-menu');

    showBtn!.addEventListener('click', (e: MouseEvent) => {
        e.preventDefault();
        menu!.style.display = 'block';
        hideBtn!.classList.add('show');
        showBtn!.classList.remove('show');
    });

    hideBtn!.addEventListener('click', (e: MouseEvent) => {
        e.preventDefault();
        menu!.style.display = '';
        hideBtn!.classList.remove('show');
        showBtn!.classList.add('show');
    });
})(document);

(function (window: Window, document: Document) {
    const footer = document.getElementById('js-footer');

    const onWindowResize = () => {
        const distanceFromBottom =
            parseInt(footer!.style.marginTop || '0', 10) +
            window.innerHeight -
            footer!.clientHeight -
            footer!.offsetTop;
        if (distanceFromBottom > 0) footer!.style.marginTop = distanceFromBottom + 'px';
    };

    window.addEventListener('resize', onWindowResize);
    onWindowResize();
})(window, document);
