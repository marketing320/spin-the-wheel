{{-- Lucide icons via CDN. Renders <i data-lucide="name"> elements as SVGs and
     re-renders after Livewire navigations and component morphs. --}}
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script>
    (function () {
        function render() { if (window.lucide) window.lucide.createIcons(); }
        var queued = false;
        function schedule() {
            if (queued) return;
            queued = true;
            requestAnimationFrame(function () { queued = false; render(); });
        }
        document.addEventListener('DOMContentLoaded', function () {
            render();
            if (document.body) new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
        });
        document.addEventListener('livewire:navigated', schedule);
        if (document.readyState !== 'loading') { render(); }
    })();
</script>
