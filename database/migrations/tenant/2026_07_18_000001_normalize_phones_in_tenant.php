<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update members
        if (Schema::hasTable('members')) {
            $members = DB::table('members')->get();

            foreach ($members as $m) {
                $updates = [];

                if ($m->phone_number) {
                    $normPhone = $this->normalizeToSriLankan($m->phone_number);

                    if ($normPhone !== $m->phone_number) {
                        $updates['phone_number'] = $normPhone;
                    }
                }

                if ($m->whatsapp_number) {
                    $normWa = $this->normalizeToSriLankan($m->whatsapp_number);

                    if ($normWa !== $m->whatsapp_number) {
                        $updates['whatsapp_number'] = $normWa;
                    }
                }

                if (!empty($updates)) {
                    DB::table('members')->where('id', $m->id)->update($updates);
                }
            }
        }

        // Update employees
        if (Schema::hasTable('employees')) {
            $employees = DB::table('employees')->get();

            foreach ($employees as $e) {
                $updates = [];

                if ($e->phone) {
                    $normPhone = $this->normalizeToSriLankan($e->phone);

                    if ($normPhone !== $e->phone) {
                        $updates['phone'] = $normPhone;
                    }
                }

                if ($e->emergency_contact_phone) {
                    $normEmerg = $this->normalizeToSriLankan($e->emergency_contact_phone);

                    if ($normEmerg !== $e->emergency_contact_phone) {
                        $updates['emergency_contact_phone'] = $normEmerg;
                    }
                }

                if (!empty($updates)) {
                    DB::table('employees')->where('id', $e->id)->update($updates);
                }
            }
        }

        // Update event_registrations
        if (Schema::hasTable('event_registrations')) {
            $registrations = DB::table('event_registrations')->get();

            foreach ($registrations as $r) {
                if ($r->phone) {
                    $normPhone = $this->normalizeToSriLankan($r->phone);

                    if ($normPhone !== $r->phone) {
                        DB::table('event_registrations')->where('id', $r->id)->update(['phone' => $normPhone]);
                    }
                }
            }
        }

        // Update tenants
        if (Schema::hasTable('tenants')) {
            $tenants = DB::table('tenants')->get();

            foreach ($tenants as $t) {
                if ($t->phone) {
                    $normPhone = $this->normalizeToSriLankan($t->phone);

                    if ($normPhone !== $t->phone) {
                        DB::table('tenants')->where('id', $t->id)->update(['phone' => $normPhone]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration: cannot reliably revert normalized numbers back to local format
    }

    /**
     * Normalize a phone number to standard +94XXXXXXXXX Sri Lankan format.
     */
    private function normalizeToSriLankan(?string $number): ?string
    {
        if ($number === null || trim($number) === '') {
            return $number;
        }

        $clean = preg_replace('/\D/', '', $number);

        if ($clean === '') {
            return $number;
        }

        if (str_starts_with($clean, '0')) {
            return '+94' . substr($clean, 1);
        }

        if (str_starts_with($clean, '94') && strlen($clean) === 11) {
            return '+' . $clean;
        }

        if (str_starts_with($clean, '7') && strlen($clean) === 9) {
            return '+94' . $clean;
        }

        if (strlen($clean) === 9) {
            return '+94' . $clean;
        }

        return '+' . $clean;
    }
};
