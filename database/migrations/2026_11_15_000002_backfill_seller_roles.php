<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sellerIds = DB::table('organization_user')
            ->join('organizations', 'organizations.id', '=', 'organization_user.organization_id')
            ->where('organizations.kind', 'supplier')
            ->where('organizations.status', 'approved')
            ->select('organization_user.user_id');

        DB::table('users')->where('role', 'enthusiast')->whereIn('id', $sellerIds)->update(['role' => 'seller']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'seller')->update(['role' => 'enthusiast']);
    }
};
