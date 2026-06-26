<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('name')->unique();
        });

        $used = [];

        DB::table('users')
            ->select(['id', 'email', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function (object $user) use (&$used): void {
                $username = $this->uniqueUsername($this->usernameCandidate($user), $used);
                $used[] = $username;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }

    private function usernameCandidate(object $user): string
    {
        $source = (string) ($user->email ?: $user->name ?: 'user-'.$user->id);
        $source = str_contains($source, '@') ? strstr($source, '@', true) : $source;
        $source = strtolower(trim((string) $source));
        $source = (string) preg_replace('/[^a-z0-9._-]+/', '-', $source);
        $source = trim($source, '._-');

        return $source !== '' ? $source : 'user-'.$user->id;
    }

    /**
     * @param  list<string>  $used
     */
    private function uniqueUsername(string $candidate, array $used): string
    {
        $username = $candidate;
        $suffix = 2;

        while (in_array($username, $used, true)) {
            $username = $candidate.'-'.$suffix;
            $suffix++;
        }

        return $username;
    }
};
