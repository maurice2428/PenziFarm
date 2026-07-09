<?php

namespace App\Support;

use Illuminate\Support\Str;

class CropStageAdvisor
{
    public static function analyze(
        ?string $cropName,
        ?string $stage,
        ?float $germinationPercent = null,
        ?string $healthStatus = null,
        ?int $daysSincePlanting = null,
        ?int $daysToHarvest = null,
    ): array {
        $cropName = $cropName ?: 'crop';
        $cropSlug = Str::of($cropName)->lower()->slug()->toString();

        $stage = $stage ?: 'planned';
        $healthStatus = $healthStatus ?: 'good';

        $visuals = static::resolveVisuals($cropSlug, $stage);

        $default = [
            'stage_label' => Str::of($stage)->replace('_', ' ')->title()->toString(),
            'crop_slug' => $cropSlug,
            'image_url' => $visuals['image_url'],
            'model_url' => $visuals['model_url'],
            'has_model' => $visuals['has_model'],
            'watering' => 'Monitor soil moisture and water according to crop demand.',
            'root_status' => 'Root development not yet assessed.',
            'shoot_status' => 'Shoot development not yet assessed.',
            'care_routine' => 'Inspect crop condition and follow the standard care routine.',
            'next_action' => 'Continue monitoring crop condition.',
            'urgency' => 'info',
            'urgency_label' => 'Monitor',
            'progress_note' => 'Crop progress is being monitored.',
        ];

        $map = [
            'planned' => [
                'watering' => 'Confirm water availability before planting. Avoid planting into very dry soil.',
                'root_status' => 'No root activity yet.',
                'shoot_status' => 'No shoot activity yet.',
                'care_routine' => 'Prepare land, confirm seed/input availability, and mark planting layout.',
                'next_action' => 'Proceed with planting or sowing.',
                'urgency' => 'warning',
                'urgency_label' => 'Preparation',
            ],
            'sown' => [
                'watering' => 'Keep the seedbed lightly moist. Avoid flooding because it can cause rotting.',
                'root_status' => 'Root initiation is expected below the soil.',
                'shoot_status' => 'Shoots are not yet visible.',
                'care_routine' => 'Protect seedbed, monitor moisture, and check for wash-off or seed exposure.',
                'next_action' => 'Watch for germination and emergence.',
                'urgency' => 'info',
                'urgency_label' => 'Sown',
            ],
            'planted' => [
                'watering' => 'Maintain enough moisture to support seed activation and root establishment.',
                'root_status' => 'Early root activity should begin soon.',
                'shoot_status' => 'Shoots may not yet be visible.',
                'care_routine' => 'Inspect spacing, planting depth, and early pest risk.',
                'next_action' => 'Monitor emergence and record germination.',
                'urgency' => 'info',
                'urgency_label' => 'Establishing',
            ],
            'germination' => [
                'watering' => 'Apply light and consistent watering. Do not waterlog the soil.',
                'root_status' => 'Roots are forming and anchoring the young plant.',
                'shoot_status' => 'Shoots are emerging above the soil surface.',
                'care_routine' => 'Check uniformity, patch gaps, and inspect for damping-off or pest damage.',
                'next_action' => 'Record germination percentage and inspect weak zones.',
                'urgency' => 'success',
                'urgency_label' => 'Germinating',
            ],
            'emerged' => [
                'watering' => 'Maintain steady moisture and protect seedlings from heat or dry stress.',
                'root_status' => 'Root system is establishing steadily.',
                'shoot_status' => 'Shoots are visible and strengthening.',
                'care_routine' => 'Scout for weak seedlings, insects, and early disease symptoms.',
                'next_action' => 'Thin, gap-fill, or support seedlings where necessary.',
                'urgency' => 'success',
                'urgency_label' => 'Emerged',
            ],
            'vegetative' => [
                'watering' => 'Water based on soil condition, rainfall, and crop demand.',
                'root_status' => 'Roots are expanding to support active growth.',
                'shoot_status' => 'Leaves, stems, and canopy are developing rapidly.',
                'care_routine' => 'Weed control, top dressing, pest scouting, and growth assessment are important.',
                'next_action' => 'Review nutrition, weeds, and pest pressure.',
                'urgency' => 'success',
                'urgency_label' => 'Growing',
            ],
            'flowering' => [
                'watering' => 'Avoid moisture stress during flowering. This stage is sensitive.',
                'root_status' => 'Established roots are supporting flowering.',
                'shoot_status' => 'Reproductive growth is active.',
                'care_routine' => 'Protect against stress, pests, disease, and nutrient imbalance.',
                'next_action' => 'Monitor flowering, pollination, and stress signs.',
                'urgency' => 'warning',
                'urgency_label' => 'Critical Stage',
            ],
            'fruiting' => [
                'watering' => 'Keep moisture consistent to support fruit set and development.',
                'root_status' => 'Root system should be stable and feeding the crop.',
                'shoot_status' => 'Canopy is supporting fruit development.',
                'care_routine' => 'Monitor pests, disease, fruit load, and nutrition.',
                'next_action' => 'Assess expected yield and fruit quality.',
                'urgency' => 'success',
                'urgency_label' => 'Fruiting',
            ],
            'maturity' => [
                'watering' => 'Reduce unnecessary watering depending on crop type and harvest target.',
                'root_status' => 'Root system is mature.',
                'shoot_status' => 'Crop is approaching harvest maturity.',
                'care_routine' => 'Prepare labour, storage, packaging, drying, and harvest logistics.',
                'next_action' => 'Confirm harvest readiness.',
                'urgency' => 'warning',
                'urgency_label' => 'Near Harvest',
            ],
            'harvesting' => [
                'watering' => 'Water only if absolutely necessary depending on crop type.',
                'root_status' => 'Root activity is no longer the main focus.',
                'shoot_status' => 'Harvest operations are active.',
                'care_routine' => 'Sort, grade, record, store, and manage post-harvest handling.',
                'next_action' => 'Complete harvest and record totals.',
                'urgency' => 'danger',
                'urgency_label' => 'Harvesting',
            ],
            'harvested' => [
                'watering' => 'No routine watering required for a completed season.',
                'root_status' => 'Season completed.',
                'shoot_status' => 'Season completed.',
                'care_routine' => 'Clean field, manage residues, review performance, and prepare next cycle.',
                'next_action' => 'Close season and plan next activity.',
                'urgency' => 'gray',
                'urgency_label' => 'Closed',
            ],
            'hardening' => [
                'watering' => 'Control watering while seedlings adjust before transplanting.',
                'root_status' => 'Roots are strengthening before transplant.',
                'shoot_status' => 'Seedlings are becoming firmer and more resilient.',
                'care_routine' => 'Reduce shade gradually, avoid shock, and prepare field destination.',
                'next_action' => 'Prepare transplanting plan.',
                'urgency' => 'warning',
                'urgency_label' => 'Hardening',
            ],
            'ready_to_transplant' => [
                'watering' => 'Water before transplanting, but avoid overwatering.',
                'root_status' => 'Root ball is ready for field transfer.',
                'shoot_status' => 'Seedlings are stable and transplant-ready.',
                'care_routine' => 'Prepare destination field, spacing, water, and labour.',
                'next_action' => 'Transplant seedlings.',
                'urgency' => 'success',
                'urgency_label' => 'Ready',
            ],
        ];

        $result = array_merge($default, $map[$stage] ?? []);

        if (($germinationPercent ?? 0) > 0 && in_array($stage, ['germination', 'emerged'], true)) {
            $result['progress_note'] = 'Germination recorded at ' . number_format((float) $germinationPercent, 2) . '%.';
        }

        if ($daysSincePlanting !== null) {
            $result['progress_note'] = 'Day ' . number_format($daysSincePlanting) . ' since planting.';
        }

        if ($daysToHarvest !== null) {
            if ($daysToHarvest < 0) {
                $result['urgency'] = 'danger';
                $result['urgency_label'] = 'Overdue';
                $result['next_action'] = 'Harvest review is overdue. Inspect the crop immediately.';
            } elseif ($daysToHarvest <= 14) {
                $result['urgency'] = 'warning';
                $result['urgency_label'] = 'Harvest Soon';
                $result['next_action'] = 'Prepare harvest labour, packaging, and storage.';
            }
        }

        if (in_array($healthStatus, ['poor', 'critical'], true)) {
            $result['urgency'] = 'danger';
            $result['urgency_label'] = 'Health Alert';
            $result['next_action'] = 'Immediate inspection required due to crop health concern.';
        } elseif ($healthStatus === 'fair' && $result['urgency'] !== 'danger') {
            $result['urgency'] = 'warning';
            $result['urgency_label'] = 'Watch';
        }

        return $result;
    }

    public static function resolveVisuals(string $cropSlug, string $stage): array
    {
        $stage = $stage ?: 'planned';

        $imageCandidates = [
            "images/crops/stages/{$cropSlug}/{$stage}.webp",
            "images/crops/stages/{$cropSlug}/{$stage}.png",
            "images/crops/stages/{$cropSlug}/default.webp",
            'images/crops/stages/placeholder.webp',
        ];

        $modelCandidates = [
            "models/crops/{$cropSlug}/{$stage}.glb",
            "models/crops/{$cropSlug}/default.glb",
        ];

        $imageUrl = asset('images/crops/stages/placeholder.webp');

        foreach ($imageCandidates as $path) {
            if (is_file(public_path($path))) {
                $imageUrl = asset($path);
                break;
            }
        }

        $modelUrl = null;

        foreach ($modelCandidates as $path) {
            if (is_file(public_path($path))) {
                $modelUrl = asset($path);
                break;
            }
        }

        return [
            'image_url' => $imageUrl,
            'model_url' => $modelUrl,
            'has_model' => filled($modelUrl),
        ];
    }
}
