<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'productivity' => 'Productivity & Notes',
            'project-management' => 'Project Management',
            'dev-tools' => 'Developer Tools',
            'hosting' => 'Hosting & Deploy',
            'database' => 'Database & Backend',
            'ai-assistant' => 'AI Assistants',
        ];

        foreach ($categories as $slug => $name) {
            Category::updateOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $products = [
            [
                'slug' => 'notion',
                'name' => 'Notion',
                'vendor' => 'Notion Labs',
                'category' => 'productivity',
                'homepage_url' => 'https://www.notion.so',
                'pricing_url' => 'https://www.notion.so/pricing',
                'tagline' => 'All-in-one workspace for notes, docs, databases, and wikis.',
            ],
            [
                'slug' => 'linear',
                'name' => 'Linear',
                'vendor' => 'Linear',
                'category' => 'project-management',
                'homepage_url' => 'https://linear.app',
                'pricing_url' => 'https://linear.app/pricing',
                'tagline' => 'Issue tracking built for modern software teams.',
            ],
            [
                'slug' => 'cursor',
                'name' => 'Cursor',
                'vendor' => 'Anysphere',
                'category' => 'dev-tools',
                'homepage_url' => 'https://cursor.com',
                'pricing_url' => 'https://cursor.com/pricing',
                'tagline' => 'AI-native code editor forked from VS Code.',
            ],
            [
                'slug' => 'vercel',
                'name' => 'Vercel',
                'vendor' => 'Vercel',
                'category' => 'hosting',
                'homepage_url' => 'https://vercel.com',
                'pricing_url' => 'https://vercel.com/pricing',
                'tagline' => 'Frontend cloud platform for Next.js and other frameworks.',
            ],
            [
                'slug' => 'supabase',
                'name' => 'Supabase',
                'vendor' => 'Supabase',
                'category' => 'database',
                'homepage_url' => 'https://supabase.com',
                'pricing_url' => 'https://supabase.com/pricing',
                'tagline' => 'Open source Firebase alternative, Postgres-based.',
            ],
            [
                'slug' => 'neon',
                'name' => 'Neon',
                'vendor' => 'Neon',
                'category' => 'database',
                'homepage_url' => 'https://neon.tech',
                'pricing_url' => 'https://neon.tech/pricing',
                'tagline' => 'Serverless Postgres with scale-to-zero and branching.',
            ],
            [
                'slug' => 'netlify',
                'name' => 'Netlify',
                'vendor' => 'Netlify',
                'category' => 'hosting',
                'homepage_url' => 'https://www.netlify.com',
                'pricing_url' => 'https://www.netlify.com/pricing',
                'tagline' => 'Frontend platform with built-in CI/CD and edge functions.',
            ],
            [
                'slug' => 'github-copilot',
                'name' => 'GitHub Copilot',
                'vendor' => 'GitHub',
                'category' => 'dev-tools',
                'homepage_url' => 'https://github.com/features/copilot',
                'pricing_url' => 'https://github.com/features/copilot/plans',
                'tagline' => 'AI pair programmer integrated into VS Code, JetBrains, and the GitHub web UI.',
            ],
            [
                'slug' => 'clickup',
                'name' => 'ClickUp',
                'vendor' => 'ClickUp',
                'category' => 'project-management',
                'homepage_url' => 'https://clickup.com',
                'pricing_url' => 'https://clickup.com/pricing',
                'tagline' => 'Task-first project management with docs, chat, and goals bundled.',
            ],
            [
                'slug' => 'airtable',
                'name' => 'Airtable',
                'vendor' => 'Airtable',
                'category' => 'productivity',
                'homepage_url' => 'https://airtable.com',
                'pricing_url' => 'https://airtable.com/pricing',
                'tagline' => 'Relational spreadsheet-database hybrid with app-building layer.',
            ],
        ];

        foreach ($products as $p) {
            $cat = Category::where('slug', $p['category'])->first();
            Product::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'category_id' => $cat?->id,
                    'name' => $p['name'],
                    'vendor' => $p['vendor'],
                    'homepage_url' => $p['homepage_url'],
                    'pricing_url' => $p['pricing_url'],
                    'tagline' => $p['tagline'],
                    'is_active' => true,
                ]
            );
        }
    }
}
