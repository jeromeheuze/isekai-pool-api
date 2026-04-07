<script>
(() => {
    const endpoint = 'https://api.isekai-pool.com/a.gif';
    const path = window.location.pathname || '/';
    const host = window.location.hostname || 'unknown';
    const qs = '?p=' + encodeURIComponent(path) + '&h=' + encodeURIComponent(host);
    const img = new Image(1, 1);
    img.referrerPolicy = 'no-referrer';
    img.src = endpoint + qs;
})();
</script>
