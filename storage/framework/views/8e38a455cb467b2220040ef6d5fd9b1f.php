<!-- =========================
     Topbar Search Component
========================= -->
<div class="farm-topbar-search-inline">
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('filament.topbar-search');

$__key = null;

$__key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-658173402-0', $__key);

$__html = app('livewire')->mount($__name, $__params, $__key);

echo $__html;

unset($__html);
unset($__key);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
</div>

<style>
    /*
.farm-topbar-search-inline {
    flex: 1 1 auto;
    display: flex;
    align-items: center;
    min-width: 0;
    padding-left: 0.75rem;
}

.farm-topbar-search-inline > div {
    width: 100%;
    max-width: 480px;
    min-width: 0;
}

.farm-topbar-search-inline input {
    width: 100%;
    height: 2.4rem;
    font-size: 0.85rem;
    padding: 0 0.85rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(229, 231, 235, 0.8);
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: all 0.2s ease;
}

.dark .farm-topbar-search-inline input {
    background: rgba(255, 255, 255, 0.08);
    color: #fff;
    border-color: rgba(255, 255, 255, 0.12);
}

.farm-topbar-search-inline input:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.15);
    outline: none;
}


@media (max-width: 768px) {
    .fi-topbar nav {
        display: flex !important;
        align-items: center !important;
        gap: 0.45rem !important;
    }

    .farm-topbar-search-inline {
        position: static !important;
        transform: none !important;

        flex: 1 1 auto !important;
        width: auto !important;
        max-width: none !important;
        min-width: 0 !important;

        padding-left: 0 !important;
        z-index: 1 !important;
    }

    .farm-topbar-search-inline > div {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
    }

    .farm-topbar-search-inline input {
        height: 2.15rem !important;
        font-size: 0.75rem !important;
        padding-inline: 0.55rem !important;
        border-radius: 0.65rem !important;
    }

    .fi-topbar nav > div:last-child {
        flex-shrink: 0 !important;
        display: flex !important;
        align-items: center !important;
        gap: 0.35rem !important;
    }

    .topbar-greeting,
    .farm-topbar-greeting {
        display: none !important;
    }
}


@media (max-width: 430px) {
    .farm-topbar-search-inline input {
        font-size: 0.7rem !important;
        padding-inline: 0.45rem !important;
    }

    .farm-topbar-search-inline {
        max-width: 160px !important;
    }
}
    */
</style>
<?php /**PATH /home/maurice/LocalDev/Penzi/resources/views/filament/admin/partials/topbar-search.blade.php ENDPATH**/ ?>