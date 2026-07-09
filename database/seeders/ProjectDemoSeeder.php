<?php

namespace Database\Seeders;

use App\Models\Projects\FarmProject;
use App\Models\Projects\ProjectBudgetLine;
use App\Models\Projects\ProjectCategory;
use App\Models\Projects\ProjectExpense;
use App\Models\Projects\ProjectMilestone;
use App\Models\Projects\ProjectProgressUpdate;
use App\Models\Projects\ProjectTask;
use App\Models\User;
use App\Services\Projects\ProjectFinancialService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProjectDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('farm_projects')) {
            $this->command?->warn('Projects tables are missing. Run migrations first.');

            return;
        }

        $user = User::query()->first();

        $userId = $user?->id;

        $categories = $this->seedCategories($userId);

        $projects = [
            [
                'category' => 'Buildings & Structures',
                'project_number' => 'PRJ-2026-001',
                'name' => 'Main Farm Store Construction',
                'project_type' => 'building',
                'priority' => 'high',
                'status' => 'in_progress',
                'location' => 'Main farm compound',
                'land_area' => 0.25,
                'description' => 'Construction of a secure farm store for feeds, tools, veterinary products and farm materials.',
                'objectives' => 'Improve storage, reduce wastage, centralise farm supplies and secure expensive materials.',
                'scope_of_work' => 'Foundation, walling, roofing, doors, ventilation, plastering, electrical fittings and final finishing.',
                'start_date' => now('Africa/Nairobi')->subDays(28)->toDateString(),
                'expected_end_date' => now('Africa/Nairobi')->addDays(21)->toDateString(),
                'progress_percent' => 55,
                'contractor_name' => 'Kamau Builders',
                'contractor_phone' => '0712345678',
                'budget_lines' => [
                    ['materials', 'Foundation materials', 1, 'lot', 185000, 185000, 185000, 'approved'],
                    ['materials', 'Walling blocks and cement', 1, 'lot', 260000, 260000, 260000, 'committed'],
                    ['labour', 'Masonry labour', 24, 'days', 3500, 84000, 84000, 'approved'],
                    ['transport', 'Material transport', 6, 'trips', 8500, 51000, 51000, 'approved'],
                    ['equipment', 'Doors and grills', 1, 'lot', 95000, 95000, 95000, 'planned'],
                ],
                'expenses' => [
                    ['2026-06-01', 'materials', 'Cement and ballast purchase', 'Hardware Supplier', 'mpesa', 96000, 'paid'],
                    ['2026-06-03', 'labour', 'Masonry labour week 1', 'Kamau Builders', 'cash', 42000, 'approved'],
                    ['2026-06-05', 'transport', 'Truck transport for blocks', 'Albert Transport', 'cash', 17000, 'paid'],
                ],
                'milestones' => [
                    ['Foundation completed', 'completed', 100, -20, 185000],
                    ['Walling works', 'in_progress', 65, 7, 260000],
                    ['Roofing and doors', 'pending', 0, 18, 150000],
                ],
                'tasks' => [
                    ['Confirm final store measurements', 'completed', 'high', 100, -25],
                    ['Complete walling', 'in_progress', 'high', 65, 5],
                    ['Source roofing sheets', 'pending', 'medium', 0, 10],
                ],
                'updates' => [
                    ['Store foundation done', 35, 'Foundation works completed and materials delivered to site.', 'No major blocker.'],
                    ['Walling progressing well', 55, 'Blocks are being laid and the structure is taking shape.', 'Need to confirm roofing material prices.'],
                ],
            ],
            [
                'category' => 'Fencing & Paddocking',
                'project_number' => 'PRJ-2026-002',
                'name' => 'Lower Paddock Fencing',
                'project_type' => 'fencing',
                'priority' => 'urgent',
                'status' => 'approved',
                'location' => 'Lower grazing paddock',
                'land_area' => 8.5,
                'description' => 'Fence the lower paddock to improve controlled grazing and reduce animal movement risks.',
                'objectives' => 'Improve grazing control, improve security and separate animal groups properly.',
                'scope_of_work' => 'Posts, droppers, wire, gates, corner supports and labour for installation.',
                'start_date' => now('Africa/Nairobi')->addDays(3)->toDateString(),
                'expected_end_date' => now('Africa/Nairobi')->addDays(24)->toDateString(),
                'progress_percent' => 10,
                'contractor_name' => 'Local fencing team',
                'contractor_phone' => '0722000000',
                'budget_lines' => [
                    ['materials', 'Fencing posts', 320, 'pieces', 450, 144000, 144000, 'approved'],
                    ['materials', 'Droppers', 850, 'pieces', 120, 102000, 102000, 'approved'],
                    ['materials', 'Barbed wire rolls', 35, 'rolls', 3600, 126000, 126000, 'approved'],
                    ['labour', 'Fencing labour', 12, 'days', 6000, 72000, 72000, 'planned'],
                    ['transport', 'Pole transport', 4, 'trips', 9500, 38000, 38000, 'planned'],
                ],
                'expenses' => [
                    ['2026-06-06', 'materials', 'Advance for fencing posts', 'Pole Supplier', 'mpesa', 60000, 'approved'],
                ],
                'milestones' => [
                    ['Materials purchased', 'in_progress', 25, 5, 260000],
                    ['Posts installed', 'pending', 0, 14, 120000],
                    ['Wire and gates fixed', 'pending', 0, 22, 102000],
                ],
                'tasks' => [
                    ['Confirm fencing line', 'completed', 'urgent', 100, -2],
                    ['Purchase posts and droppers', 'in_progress', 'urgent', 30, 4],
                    ['Mobilise fencing team', 'pending', 'high', 0, 6],
                ],
                'updates' => [
                    ['Fencing line confirmed', 10, 'The fencing line was reviewed and confirmed with farm team.', 'Waiting for remaining materials.'],
                ],
            ],
            [
                'category' => 'Dams & Water Works',
                'project_number' => 'PRJ-2026-003',
                'name' => 'Water Pan Rehabilitation',
                'project_type' => 'dam',
                'priority' => 'high',
                'status' => 'planned',
                'location' => 'Water pan area',
                'land_area' => 1.2,
                'description' => 'Rehabilitate the existing water pan to improve water retention before the dry season.',
                'objectives' => 'Increase water storage, reduce water stress and support livestock watering.',
                'scope_of_work' => 'Desilting, embankment repair, inlet clearing, spillway shaping and access road improvement.',
                'start_date' => now('Africa/Nairobi')->addDays(14)->toDateString(),
                'expected_end_date' => now('Africa/Nairobi')->addDays(45)->toDateString(),
                'progress_percent' => 0,
                'contractor_name' => 'Earthworks Contractor',
                'contractor_phone' => '0700111222',
                'budget_lines' => [
                    ['equipment', 'Excavator hire', 5, 'days', 45000, 225000, 225000, 'planned'],
                    ['fuel', 'Diesel for machine support', 300, 'litres', 245, 73500, 73500, 'planned'],
                    ['labour', 'Casual labour support', 10, 'days', 2500, 25000, 25000, 'planned'],
                    ['transport', 'Machine mobilisation', 1, 'trip', 65000, 65000, 65000, 'planned'],
                ],
                'expenses' => [],
                'milestones' => [
                    ['Site inspection and levels', 'pending', 0, 10, 15000],
                    ['Desilting and excavation', 'pending', 0, 25, 275000],
                    ['Final shaping and access', 'pending', 0, 40, 98500],
                ],
                'tasks' => [
                    ['Get machine quotation', 'pending', 'high', 0, 5],
                    ['Confirm access route', 'pending', 'medium', 0, 8],
                    ['Prepare site for excavator', 'pending', 'medium', 0, 12],
                ],
                'updates' => [],
            ],
            [
                'category' => 'Road Works',
                'project_number' => 'PRJ-2026-004',
                'name' => 'Farm Access Road Hardcore Improvement',
                'project_type' => 'road',
                'priority' => 'high',
                'status' => 'in_progress',
                'location' => 'Main access road',
                'land_area' => 0.8,
                'land_area_unit' => 'km',
                'description' => 'Improve muddy farm access road using hardcore and grading.',
                'objectives' => 'Improve lorry access during rainy periods and reduce transport delays.',
                'scope_of_work' => 'Hardcore supply, spreading, compacting, drainage shaping and rough grading.',
                'start_date' => now('Africa/Nairobi')->subDays(10)->toDateString(),
                'expected_end_date' => now('Africa/Nairobi')->addDays(12)->toDateString(),
                'progress_percent' => 40,
                'contractor_name' => 'Alfred Truck Services',
                'contractor_phone' => '0799000000',
                'budget_lines' => [
                    ['materials', 'Hardcore supply', 18, 'trips', 11500, 207000, 207000, 'approved'],
                    ['transport', 'Truck transport', 18, 'trips', 7000, 126000, 126000, 'approved'],
                    ['equipment', 'Grader hire', 1, 'day', 55000, 55000, 55000, 'planned'],
                    ['labour', 'Road casuals', 5, 'days', 2500, 12500, 12500, 'planned'],
                ],
                'expenses' => [
                    ['2026-06-04', 'transport', 'First hardcore trips', 'Alfred Truck Services', 'cash', 42000, 'paid'],
                    ['2026-06-05', 'materials', 'Hardcore supply deposit', 'Quarry Supplier', 'mpesa', 65000, 'approved'],
                ],
                'milestones' => [
                    ['First hardcore delivery', 'completed', 100, -3, 80000],
                    ['Road spreading', 'in_progress', 45, 7, 160000],
                    ['Final grading', 'pending', 0, 12, 160500],
                ],
                'tasks' => [
                    ['Identify worst sections', 'completed', 'high', 100, -8],
                    ['Deliver hardcore', 'in_progress', 'high', 45, 6],
                    ['Book grader', 'pending', 'medium', 0, 10],
                ],
                'updates' => [
                    ['First hardcore delivered', 30, 'Hardcore delivery has started on the worst muddy sections.', 'More trips are needed.'],
                    ['Road still needs grading', 40, 'The road is passable but still needs proper spreading and grading.', 'Grader not yet confirmed.'],
                ],
            ],
            [
                'category' => 'Security & CCTV',
                'project_number' => 'PRJ-2026-005',
                'name' => 'CCTV and Network Installation',
                'project_type' => 'security',
                'priority' => 'medium',
                'status' => 'completed',
                'location' => 'Farm office and livestock sections',
                'description' => 'Install CCTV cameras, NVR, router, and network cabling for farm monitoring.',
                'objectives' => 'Improve security, remote monitoring and staff accountability.',
                'scope_of_work' => 'Camera installation, cabling, NVR configuration, router setup and remote viewing.',
                'start_date' => now('Africa/Nairobi')->subDays(40)->toDateString(),
                'expected_end_date' => now('Africa/Nairobi')->subDays(25)->toDateString(),
                'actual_end_date' => now('Africa/Nairobi')->subDays(22)->toDateString(),
                'progress_percent' => 100,
                'contractor_name' => 'CCTV Technician',
                'contractor_phone' => '0711111111',
                'budget_lines' => [
                    ['equipment', 'Cameras and NVR', 1, 'set', 185000, 185000, 185000, 'used'],
                    ['materials', 'Cables and connectors', 1, 'lot', 38000, 38000, 38000, 'used'],
                    ['labour', 'Installation labour', 1, 'job', 25000, 25000, 25000, 'used'],
                ],
                'expenses' => [
                    ['2026-05-01', 'equipment', 'CCTV equipment purchase', 'Security Supplier', 'bank', 185000, 'paid'],
                    ['2026-05-02', 'labour', 'CCTV installation labour', 'CCTV Technician', 'cash', 25000, 'paid'],
                    ['2026-05-02', 'materials', 'Cables and connectors', 'Security Supplier', 'mpesa', 38000, 'paid'],
                ],
                'milestones' => [
                    ['Equipment purchased', 'completed', 100, -38, 185000],
                    ['Installation completed', 'completed', 100, -26, 63000],
                    ['Remote viewing confirmed', 'completed', 100, -22, 0],
                ],
                'tasks' => [
                    ['Install cameras', 'completed', 'high', 100, -27],
                    ['Configure NVR', 'completed', 'high', 100, -26],
                    ['Test remote access', 'completed', 'medium', 100, -22],
                ],
                'updates' => [
                    ['CCTV installation completed', 100, 'Cameras, NVR and remote access have been configured successfully.', 'No blocker.'],
                ],
            ],
        ];

        foreach ($projects as $projectData) {
            $project = $this->createProject($projectData, $categories, $userId);

            $this->seedBudgetLines($project, $projectData['budget_lines'] ?? [], $userId);
            $this->seedExpenses($project, $projectData['expenses'] ?? [], $userId);
            $this->seedMilestones($project, $projectData['milestones'] ?? [], $userId);
            $this->seedTasks($project, $projectData['tasks'] ?? [], $userId);
            $this->seedUpdates($project, $projectData['updates'] ?? [], $userId);

            app(ProjectFinancialService::class)->recalculate($project);
            app(ProjectFinancialService::class)->recalculateProgress($project);
        }

        $this->command?->info('Project demo data seeded successfully.');
    }

    protected function seedCategories(?int $userId): array
    {
        $items = [
            [
                'name' => 'Buildings & Structures',
                'code' => 'BUILD',
                'type' => 'building',
                'icon' => 'heroicon-o-building-office-2',
                'color' => '#166534',
                'description' => 'Stores, offices, houses, livestock structures and farm buildings.',
            ],
            [
                'name' => 'Fencing & Paddocking',
                'code' => 'FENCE',
                'type' => 'fencing',
                'icon' => 'heroicon-o-square-3-stack-3d',
                'color' => '#92400e',
                'description' => 'Fences, paddocks, gates, posts, droppers and animal separation works.',
            ],
            [
                'name' => 'Dams & Water Works',
                'code' => 'WATER',
                'type' => 'dam',
                'icon' => 'heroicon-o-beaker',
                'color' => '#0369a1',
                'description' => 'Dams, water pans, boreholes, tanks, pipes and water distribution systems.',
            ],
            [
                'name' => 'Road Works',
                'code' => 'ROAD',
                'type' => 'road',
                'icon' => 'heroicon-o-map',
                'color' => '#475569',
                'description' => 'Farm roads, drainage, hardcore works and access improvements.',
            ],
            [
                'name' => 'Security & CCTV',
                'code' => 'SEC',
                'type' => 'security',
                'icon' => 'heroicon-o-video-camera',
                'color' => '#7f1d1d',
                'description' => 'CCTV, security systems, lighting, gates and monitoring infrastructure.',
            ],
            [
                'name' => 'Electrical & Power',
                'code' => 'POWER',
                'type' => 'electrical',
                'icon' => 'heroicon-o-bolt',
                'color' => '#ca8a04',
                'description' => 'Electricity, conductors, stabilisers, power lines and electrical repairs.',
            ],
        ];

        $categories = [];

        foreach ($items as $item) {
            $category = ProjectCategory::query()->updateOrCreate(
                ['code' => $item['code']],
                [
                    ...$item,
                    'is_active' => true,
                    'created_by' => $userId,
                ]
            );

            $categories[$category->name] = $category;
        }

        return $categories;
    }

    protected function createProject(array $data, array $categories, ?int $userId): FarmProject
    {
        $category = $categories[$data['category']] ?? null;

        return FarmProject::query()->updateOrCreate(
            ['project_number' => $data['project_number']],
            [
                'project_category_id' => $category?->id,
                'name' => $data['name'],
                'project_type' => $data['project_type'],
                'priority' => $data['priority'],
                'status' => $data['status'],
                'location' => $data['location'] ?? null,
                'land_area' => $data['land_area'] ?? null,
                'land_area_unit' => $data['land_area_unit'] ?? 'acres',
                'description' => $data['description'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'scope_of_work' => $data['scope_of_work'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'expected_end_date' => $data['expected_end_date'] ?? null,
                'actual_end_date' => $data['actual_end_date'] ?? null,
                'progress_percent' => $data['progress_percent'] ?? 0,
                'contractor_name' => $data['contractor_name'] ?? null,
                'contractor_phone' => $data['contractor_phone'] ?? null,
                'contractor_email' => $data['contractor_email'] ?? null,
                'manager_id' => $userId,
                'approved_by' => in_array($data['status'], ['approved', 'in_progress', 'completed', 'closed'], true) ? $userId : null,
                'approved_at' => in_array($data['status'], ['approved', 'in_progress', 'completed', 'closed'], true) ? now('Africa/Nairobi') : null,
                'created_by' => $userId,
                'notes' => 'Demo project generated for testing the Projects & Works module.',
            ]
        );
    }

    protected function seedBudgetLines(FarmProject $project, array $lines, ?int $userId): void
    {
        foreach ($lines as $line) {
            [$category, $item, $qty, $unit, $unitCost, $estimated, $approved, $status] = $line;

            ProjectBudgetLine::query()->updateOrCreate(
                [
                    'farm_project_id' => $project->id,
                    'item_name' => $item,
                ],
                [
                    'cost_category' => $category,
                    'description' => 'Demo budget line for ' . $project->name,
                    'quantity' => $qty,
                    'unit' => $unit,
                    'unit_cost' => $unitCost,
                    'estimated_amount' => $estimated,
                    'approved_amount' => $approved,
                    'actual_amount' => 0,
                    'variance_amount' => $approved,
                    'status' => $status,
                    'created_by' => $userId,
                ]
            );
        }
    }

    protected function seedExpenses(FarmProject $project, array $expenses, ?int $userId): void
    {
        foreach ($expenses as $expense) {
            [$date, $type, $description, $payee, $method, $amount, $status] = $expense;

            ProjectExpense::query()->updateOrCreate(
                [
                    'farm_project_id' => $project->id,
                    'expense_date' => $date,
                    'description' => $description,
                ],
                [
                    'expense_type' => $type,
                    'reference_no' => 'EXP-' . $project->id . '-' . str_replace('-', '', $date),
                    'payee' => $payee,
                    'payment_method' => $method,
                    'quantity' => 1,
                    'unit' => 'lot',
                    'unit_cost' => $amount,
                    'amount' => $amount,
                    'tax_amount' => 0,
                    'total_amount' => $amount,
                    'status' => $status,
                    'approved_by' => in_array($status, ['approved', 'paid'], true) ? $userId : null,
                    'approved_at' => in_array($status, ['approved', 'paid'], true) ? now('Africa/Nairobi') : null,
                    'created_by' => $userId,
                ]
            );
        }
    }

    protected function seedMilestones(FarmProject $project, array $milestones, ?int $userId): void
    {
        foreach ($milestones as $milestone) {
            [$title, $status, $progress, $daysFromNow, $budget] = $milestone;

            ProjectMilestone::query()->updateOrCreate(
                [
                    'farm_project_id' => $project->id,
                    'title' => $title,
                ],
                [
                    'description' => 'Demo milestone for ' . $project->name,
                    'status' => $status,
                    'progress_percent' => $progress,
                    'target_date' => now('Africa/Nairobi')->addDays($daysFromNow)->toDateString(),
                    'completed_at' => $status === 'completed'
                        ? now('Africa/Nairobi')->addDays($daysFromNow)->toDateString()
                        : null,
                    'budget_amount' => $budget,
                    'spent_amount' => 0,
                    'created_by' => $userId,
                ]
            );
        }
    }

    protected function seedTasks(FarmProject $project, array $tasks, ?int $userId): void
    {
        foreach ($tasks as $task) {
            [$title, $status, $priority, $progress, $dueInDays] = $task;

            ProjectTask::query()->updateOrCreate(
                [
                    'farm_project_id' => $project->id,
                    'title' => $title,
                ],
                [
                    'description' => 'Demo task for ' . $project->name,
                    'status' => $status,
                    'priority' => $priority,
                    'start_date' => now('Africa/Nairobi')->subDays(5)->toDateString(),
                    'due_date' => now('Africa/Nairobi')->addDays($dueInDays)->toDateString(),
                    'completed_at' => $status === 'completed'
                        ? now('Africa/Nairobi')->addDays($dueInDays)->toDateString()
                        : null,
                    'progress_percent' => $progress,
                    'assigned_to' => $userId,
                    'created_by' => $userId,
                ]
            );
        }
    }

    protected function seedUpdates(FarmProject $project, array $updates, ?int $userId): void
    {
        foreach ($updates as $index => $update) {
            [$title, $progress, $workDone, $blockers] = $update;

            ProjectProgressUpdate::query()->updateOrCreate(
                [
                    'farm_project_id' => $project->id,
                    'title' => $title,
                ],
                [
                    'update_date' => now('Africa/Nairobi')->subDays(7 - $index)->toDateString(),
                    'narrative' => 'Demo progress update.',
                    'progress_percent' => $progress,
                    'weather_condition' => 'normal',
                    'work_done' => $workDone,
                    'blockers' => $blockers,
                    'next_steps' => 'Continue monitoring and update the project plan where necessary.',
                    'created_by' => $userId,
                ]
            );
        }
    }
}
