<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        // Create 1 Admin User
        $admin = User::create([
            'name' => 'Admin Utama',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        $users = [$admin];

        // Create 6 Regular Users (Total 7 Users)
        for ($i = 1; $i <= 6; $i++) {
            $users[] = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'role' => 'user',
            ]);
        }

        // Create 7 Posts
        $posts = [];
        for ($i = 1; $i <= 7; $i++) {
            $randomUser = $users[array_rand($users)];
            $posts[] = Post::create([
                'title' => $faker->sentence(6),
                'status' => $faker->randomElement(['draft', 'published']),
                'content' => $faker->paragraphs(3, true),
                'user_id' => $randomUser->id,
            ]);
        }

        // Create 7 Comments
        for ($i = 1; $i <= 7; $i++) {
            $randomUser = $users[array_rand($users)];
            $randomPost = $posts[array_rand($posts)];
            
            Comment::create([
                'comment' => $faker->sentence(8),
                'post_id' => $randomPost->id,
                'user_id' => $randomUser->id,
            ]);
        }
    }
}
