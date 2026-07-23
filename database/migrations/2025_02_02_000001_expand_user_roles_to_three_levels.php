<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->changeRoleValues(['staff', 'admin', 'branch_admin', 'super_admin']);

        DB::table('users')
            ->where('role', 'admin')
            ->update([
                'role' => 'super_admin',
                'branch_id' => null,
            ]);

        $this->changeRoleValues(['staff', 'branch_admin', 'super_admin']);
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', 'branch_admin')->exists()) {
            throw new RuntimeException(
                'Không thể rollback role khi còn branch_admin; hãy chuyển các tài khoản này về staff trước.'
            );
        }

        $this->changeRoleValues(['staff', 'branch_admin', 'super_admin', 'admin']);

        DB::table('users')
            ->where('role', 'super_admin')
            ->update([
                'role' => 'admin',
                'branch_id' => null,
            ]);

        $this->changeRoleValues(['staff', 'admin']);
    }

    /**
     * Expand before backfill, then contract only after no row uses the removed value.
     *
     * @param  list<string>  $roles
     */
    private function changeRoleValues(array $roles): void
    {
        Schema::table('users', function (Blueprint $table) use ($roles): void {
            $table->enum('role', $roles)->change();
        });
    }
};
