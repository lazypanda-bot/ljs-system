<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'timezone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('timezone', 64)->default('Asia/Manila');
            });
        }

        if (! Schema::hasColumn('blocked_dates', 'timezone')) {
            Schema::table('blocked_dates', function (Blueprint $table): void {
                $table->string('timezone', 64)->default('Asia/Manila');
            });
        }

        $indexes = collect(Schema::getIndexes('appointments'))->pluck('name');
        if (! $indexes->contains('appointments_assign_capacity_idx')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->index('assign_id', 'appointments_assign_capacity_idx');
            });
        }

        if ($indexes->contains('appointments_assign_id_preferred_date_preferred_time_unique')) {
            Schema::table('appointments', function (Blueprint $table): void {
            $table->dropUnique('appointments_assign_id_preferred_date_preferred_time_unique');
            });
        }

        if (! Schema::hasColumn('appointments', 'client_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreignId('client_id')->nullable()->after('assign_id')->constrained('users', 'user_id')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('appointments', 'starts_at_utc')) {
            Schema::table('appointments', function (Blueprint $table): void {
            $table->dateTime('starts_at_utc')->nullable()->after('preferred_time');
            });
        }

        if (! collect(Schema::getIndexes('appointments'))->pluck('name')->contains('appointments_starts_at_utc_index')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->index('starts_at_utc');
            });
        }

        if (! Schema::hasColumn('appointments', 'client_timezone')) {
            Schema::table('appointments', function (Blueprint $table): void {
            $table->string('client_timezone', 64)->nullable()->after('starts_at_utc');
            });
        }

        DB::table('appointments')->whereNull('starts_at_utc')->orderBy('appointment_id')->chunkById(100, function ($appointments): void {
            foreach ($appointments as $appointment) {
                $localStart = CarbonImmutable::parse(
                    $appointment->preferred_date . ' ' . $appointment->preferred_time,
                    'Asia/Manila'
                );
                $clientId = DB::table('users')
                    ->where('email', $appointment->contact_email)
                    ->where('role_name', 'Client')
                    ->value('user_id');

                DB::table('appointments')->where('appointment_id', $appointment->appointment_id)->update([
                    'client_id' => $clientId,
                    'starts_at_utc' => $localStart->utc()->format('Y-m-d H:i:s'),
                    'client_timezone' => 'Asia/Manila',
                ]);
            }
        }, 'appointment_id');

        if (! Schema::hasTable('appointment_time_proposals')) {
            Schema::create('appointment_time_proposals', function (Blueprint $table): void {
            $table->id('proposal_id');
            $table->foreignId('appointment_id')->constrained('appointments', 'appointment_id')->cascadeOnDelete();
            $table->foreignId('proposed_by_user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->dateTime('starts_at_utc');
            $table->date('preferred_date');
            $table->time('preferred_time');
            $table->string('timezone', 64);
            $table->text('message')->nullable();
            $table->string('status', 16)->default('Pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['appointment_id', 'status']);
            });
        }

        if (! Schema::hasTable('appointment_reminders')) {
            Schema::create('appointment_reminders', function (Blueprint $table): void {
            $table->id('reminder_id');
            $table->foreignId('appointment_id')->constrained('appointments', 'appointment_id')->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->unsignedSmallInteger('minutes_before');
            $table->timestamp('sent_at');
            $table->unique(['appointment_id', 'recipient_user_id', 'minutes_before'], 'appointment_reminder_delivery_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
        Schema::dropIfExists('appointment_time_proposals');

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropIndex(['starts_at_utc']);
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['starts_at_utc', 'client_timezone']);
            $table->unique(['assign_id', 'preferred_date', 'preferred_time']);
        });

        Schema::table('blocked_dates', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('timezone');
        });
    }
};