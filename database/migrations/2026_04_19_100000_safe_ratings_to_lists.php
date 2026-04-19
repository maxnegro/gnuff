<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ProductList;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Aggiungi product_list_id nullable
        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('product_list_id')->nullable()->after('id')->constrained('product_lists')->onDelete('cascade');
        });

        // 2. Per ogni utente, crea una lista di default
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $listId = DB::table('product_lists')->insertGetId([
                'name' => 'I miei prodotti',
                'owner_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // 3. Aggiorna i ratings di quell'utente per puntare alla lista
            DB::table('ratings')->where('user_id', $user->id)->update(['product_list_id' => $listId]);
        }

        // 4. Rendi product_list_id NOT NULL
        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('product_list_id')->nullable(false)->change();
        });

        // 5. Elimina user_id
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table) {
            $table->foreignId('user_id')->after('id')->constrained('users')->onDelete('cascade');
        });
        // NOTA: non si ripristinano i dati user_id originali
        Schema::table('ratings', function (Blueprint $table) {
            $table->dropForeign(['product_list_id']);
            $table->dropColumn('product_list_id');
        });
    }
};
