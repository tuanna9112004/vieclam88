<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-admin {--name=} {--email=} {--password=} {--force}')]
#[Description('Tạo tài khoản Admin đầu tiên trên production (ADR-050) — không phải seeder.')]
class CreateAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::query()->where('role', 'admin')->exists() && ! $this->option('force')) {
            $this->error('Đã tồn tại tài khoản Admin. Dùng --force nếu chắc chắn muốn tạo thêm.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Tên Admin');
        $email = $this->option('email') ?: $this->ask('Email Admin');
        $password = $this->option('password') ?: $this->secret('Mật khẩu tạm');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:150'],
                'email' => ['required', 'email', 'max:191', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'role' => 'admin',
            'branch_id' => null,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'active',
            'password_changed_at' => null,
        ]);

        $this->info("Đã tạo Admin: {$email}. Bắt buộc đổi mật khẩu ở lần đăng nhập đầu tiên.");

        return self::SUCCESS;
    }
}
