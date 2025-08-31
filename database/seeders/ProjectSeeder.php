<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\User;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (or create one if none exists)
        $user = User::first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create sample projects
        $projects = [
            [
                'name' => 'Website Redesign',
                'description' => 'Complete redesign of the company website with modern UI/UX',
                'key' => 'WEB',
                'color' => '#3B82F6',
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Build a new mobile application for iOS and Android',
                'key' => 'MOB',
                'color' => '#10B981',
            ],
            [
                'name' => 'Database Migration',
                'description' => 'Migrate legacy database to new cloud infrastructure',
                'key' => 'DB',
                'color' => '#F59E0B',
            ],
            [
                'name' => 'API Integration',
                'description' => 'Integrate third-party APIs for payment processing',
                'key' => 'API',
                'color' => '#8B5CF6',
            ],
            [
                'name' => 'Security Audit',
                'description' => 'Comprehensive security review and vulnerability assessment',
                'key' => 'SEC',
                'color' => '#EF4444',
            ],
        ];

        foreach ($projects as $projectData) {
            Project::create([
                ...$projectData,
                'owner_id' => $user->id,
                'is_active' => true,
            ]);
        }
    }
}
