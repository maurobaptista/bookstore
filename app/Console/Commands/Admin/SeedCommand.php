<?php

namespace App\Console\Commands\Admin;

use App\Models\Book;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedCommand extends Command
{
    protected $signature = 'admin:seed';

    protected $description = 'Seed database';

    private string $userToken = '';
    private string $managerToken = '';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Cannot seed in production');

            return Command::FAILURE;
        }

        $this->components->info('Taks');

        $this->components->task('Migrating Database', fn () => Artisan::call('migrate:fresh'));
        $this->components->task('Seeding Permissions', fn () => Artisan::call('db:seed PermissionsSeeder'));
        $this->components->task('Seeding Users', $this->seedUsers(...));
        $this->components->task('Seeding Books', fn () => Book::factory()->times(10)->create());
        $this->components->task('Seeding Borrowings', $this->seedBorrowings(...));

        $this->newLine();
        $this->components->info('Users');

        $users = User::with('tokens', 'roles')->get()->map(fn (User $user) => [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->join(','),
            'token' => $user->tokens->first()?->token,
        ]);

        $this->table(['Name', 'Email', 'Roles', 'Token'], $users);

        $this->newLine();
        $this->components->info('Completed');

        return Command::SUCCESS;
    }

    private function seedUsers(): void
    {
        [$user, $manager] = User::factory()->times(2)->create();

        $user->createToken('token');

        $manager->assignRole('manager');
        $manager->CreateToken('token');
    }

    private function seedBorrowings(): void
    {
        User::find(1)->books()->sync([1, 2, 3]);
        User::find(2)->books()->sync([4, 5]);
    }
}


