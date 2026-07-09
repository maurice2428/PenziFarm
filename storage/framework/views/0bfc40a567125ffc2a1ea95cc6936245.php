<div class="w-full max-w-2xl">
    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="query"
            placeholder="Search Here... (Ctrl + K)"
            class="w-full rounded-xl border px-4 py-2 text-sm shadow-sm"
        >

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($this->query !== ''): ?>
            <div class="absolute z-50 mt-2 w-full bg-white border rounded-xl shadow-lg">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $this->results; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $result): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <button
                        wire:click="openResult('<?php echo e($result['url']); ?>', '<?php echo e(addslashes($result['title'])); ?>')"
                        class="w-full text-left px-4 py-2 hover:bg-gray-100"
                    >
                        <div class="font-semibold"><?php echo e($result['title']); ?></div>
                        <div class="text-xs text-gray-500"><?php echo e($result['subtitle']); ?></div>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="px-4 py-2 text-sm text-gray-500">
                        No results found
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH /home/maurice/LocalDev/Penzi/resources/views/livewire/filament/topbar-search.blade.php ENDPATH**/ ?>