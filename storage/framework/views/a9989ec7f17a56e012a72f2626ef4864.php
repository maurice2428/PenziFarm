<?php
    $primary = trim(setting('theme.primary', '#14532d'));
    $success = trim(setting('theme.success', '#16a34a'));
    $danger = trim(setting('theme.danger', '#dc2626'));
?>

<style>
    .breeding-command-wrap {
        border: 1px solid color-mix(in srgb, <?php echo e($primary); ?> 15%, #e5e7eb);
        background:
            radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primary); ?> 8%, transparent), transparent 30%),
            linear-gradient(180deg, rgba(255,255,255,.99), rgba(249,250,251,.95));
        box-shadow: 0 14px 36px rgba(2,6,23,.05);
        padding: .85rem;
        overflow: hidden;
    }

    .dark .breeding-command-wrap {
        background:
            radial-gradient(circle at top right, color-mix(in srgb, <?php echo e($primary); ?> 17%, transparent), transparent 32%),
            linear-gradient(180deg, rgba(17,24,39,.97), rgba(15,23,42,.95));
        border-color: rgba(148,163,184,.14);
    }

    .breeding-command-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .8rem;
        flex-wrap: wrap;
        margin-bottom: .75rem;
    }

    .breeding-command-kicker {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: <?php echo e($primary); ?>;
        font-size: .66rem;
        font-weight: 950;
        letter-spacing: .05em;
    }

    .breeding-command-title {
        margin-top: .2rem;
        color: #111827;
        font-size: .95rem;
        font-weight: 950;
    }

    .dark .breeding-command-title { color: #f9fafb; }

    .breeding-command-note {
        margin-top: .16rem;
        max-width: 760px;
        color: #6b7280;
        font-size: .69rem;
        line-height: 1.45;
    }

    .dark .breeding-command-note { color: #9ca3af; }

    .breeding-command-link {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .42rem .62rem;
        color: #fff;
        background: linear-gradient(135deg, <?php echo e($primary); ?>, #166534);
        font-size: .62rem;
        font-weight: 950;
        text-decoration: none;
    }

    .breeding-command-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: .65rem;
    }

    @media (min-width: 1100px) {
        .breeding-command-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
    }

    .breeding-rank-block {
        border: 1px solid #e5e7eb;
        background: rgba(255,255,255,.96);
        padding: .65rem;
    }

    .dark .breeding-rank-block {
        background: rgba(31,41,55,.92);
        border-color: rgba(148,163,184,.14);
    }

    .breeding-rank-title {
        color: #374151;
        font-size: .64rem;
        font-weight: 950;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .dark .breeding-rank-title { color: #d1d5db; }

    .breeding-rank-list {
        display: grid;
        grid-template-columns: repeat(2,minmax(0,1fr));
        gap: .45rem;
        margin-top: .55rem;
    }

    .breeding-rank-card {
        min-width: 0;
        display: grid;
        grid-template-columns: minmax(0,1fr) auto;
        gap: .5rem;
        align-items: center;
        padding: .55rem;
        border: 1px solid #e5e7eb;
        border-left: 3px solid var(--rank-color);
        background: #fff;
        text-decoration: none;
        color: inherit;
    }

    .dark .breeding-rank-card {
        background: #111827;
        border-color: rgba(148,163,184,.16);
    }

    .breeding-rank-tag {
        color: #111827;
        font-size: .65rem;
        font-weight: 950;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dark .breeding-rank-tag { color: #f9fafb; }

    .breeding-rank-meta {
        margin-top: .12rem;
        color: #6b7280;
        font-size: .49rem;
        line-height: 1.35;
    }

    .breeding-rank-score {
        color: var(--rank-color);
        font-size: 1rem;
        font-weight: 950;
    }

    .breeding-alert-row {
        display: grid;
        grid-template-columns: repeat(2,minmax(0,1fr));
        gap: .55rem;
        margin-top: .65rem;
    }

    .breeding-alert-card {
        padding: .6rem;
        border: 1px solid color-mix(in srgb, var(--alert-color) 24%, #e5e7eb);
        border-left: 3px solid var(--alert-color);
        background: color-mix(in srgb, var(--alert-color) 6%, white);
    }

    .dark .breeding-alert-card {
        background: color-mix(in srgb, var(--alert-color) 12%, #111827);
    }

    .breeding-alert-title {
        color: var(--alert-color);
        font-size: .6rem;
        font-weight: 950;
        text-transform: uppercase;
    }

    .breeding-alert-value {
        margin-top: .25rem;
        color: #111827;
        font-size: 1.15rem;
        font-weight: 950;
    }

    .dark .breeding-alert-value { color: #f9fafb; }

    .breeding-alert-note {
        margin-top: .12rem;
        color: #6b7280;
        font-size: .5rem;
    }

    @media (max-width: 640px) {
        .breeding-rank-list,
        .breeding-alert-row { grid-template-columns: 1fr; }
    }
</style>

<div class="breeding-command-wrap">
    <div class="breeding-command-head">
        <div>
            <div class="breeding-command-kicker">
                <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-heart'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
                Breeding intelligence
            </div>
            <div class="breeding-command-title">Most Productive Breeding Animals</div>
            <div class="breeding-command-note">
                Ranked sire progeny performance, dam maternal performance, and authorised sell or cull review flags.
            </div>
        </div>

        <a href="<?php echo e(\App\Filament\Pages\ProgenyExplorer::getUrl()); ?>" class="breeding-command-link">
            Open progeny explorer
            <?php if (isset($component)) { $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c = $attributes; } ?>
<?php $component = BladeUI\Icons\Components\Svg::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('heroicon-o-arrow-right'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\BladeUI\Icons\Components\Svg::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-3 w-3']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $attributes = $__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__attributesOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c)): ?>
<?php $component = $__componentOriginal643fe1b47aec0b76658e1a0200b34b2c; ?>
<?php unset($__componentOriginal643fe1b47aec0b76658e1a0200b34b2c); ?>
<?php endif; ?>
        </a>
    </div>

    <div class="breeding-command-grid">
        <div class="breeding-rank-block">
            <div class="breeding-rank-title">Top Sires</div>
            <div class="breeding-rank-list">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $topSires; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a
                        href="<?php echo e(\App\Filament\Pages\ProgenyExplorer::getUrl(['animal' => $entry['animal']->id])); ?>"
                        class="breeding-rank-card"
                        style="--rank-color: #2563eb;"
                    >
                        <div>
                            <div class="breeding-rank-tag"><?php echo e($entry['animal']->tag_number); ?></div>
                            <div class="breeding-rank-meta">
                                <?php echo e($entry['animal']->breed?->breed_name ?? 'Unknown breed'); ?> ·
                                <?php echo e($entry['metrics']['direct_offspring']); ?> offspring ·
                                <?php echo e(number_format($entry['metrics']['survival_rate'], 1)); ?>% survival
                            </div>
                        </div>
                        <div class="breeding-rank-score"><?php echo e(number_format($entry['metrics']['score'], 1)); ?></div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="breeding-command-note">No sire progeny data available.</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="breeding-rank-block">
            <div class="breeding-rank-title">Top Dams</div>
            <div class="breeding-rank-list">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $topDams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a
                        href="<?php echo e(\App\Filament\Pages\ProgenyExplorer::getUrl(['animal' => $entry['animal']->id])); ?>"
                        class="breeding-rank-card"
                        style="--rank-color: #db2777;"
                    >
                        <div>
                            <div class="breeding-rank-tag"><?php echo e($entry['animal']->tag_number); ?></div>
                            <div class="breeding-rank-meta">
                                <?php echo e($entry['animal']->breed?->breed_name ?? 'Unknown breed'); ?> ·
                                <?php echo e($entry['metrics']['deliveries']); ?> deliveries ·
                                <?php echo e(number_format($entry['metrics']['mothering_score'], 1)); ?>/5 mothering
                            </div>
                        </div>
                        <div class="breeding-rank-score"><?php echo e(number_format($entry['metrics']['score'], 1)); ?></div>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="breeding-command-note">No dam evaluations available.</div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="breeding-alert-row">
        <div class="breeding-alert-card" style="--alert-color: #ea580c;">
            <div class="breeding-alert-title">Sale Reviews</div>
            <div class="breeding-alert-value"><?php echo e(number_format($sellRecommendations->count())); ?></div>
            <div class="breeding-alert-note">Latest authorised recommendations currently marked for sale.</div>
        </div>

        <div class="breeding-alert-card" style="--alert-color: <?php echo e($danger); ?>;">
            <div class="breeding-alert-title">Cull Reviews</div>
            <div class="breeding-alert-value"><?php echo e(number_format($cullRecommendations->count())); ?></div>
            <div class="breeding-alert-note">Latest authorised recommendations requiring management and veterinary review.</div>
        </div>
    </div>
</div>
<?php /**PATH /home/maurice/LocalDev/Penzi/resources/views/components/breeding-performance-dashboard.blade.php ENDPATH**/ ?>