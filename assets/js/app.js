(() => {
    const shouldRefresh = document.body.classList.contains('tablet-display');
    if (!shouldRefresh) return;

    setInterval(() => {
        window.location.reload();
    }, 30000);
})();
