<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLedgerBalanceGuardrail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::unprepared('
                CREATE TRIGGER prevent_negative_wallet_balance
                BEFORE INSERT ON ledger_entries
                FOR EACH ROW
                BEGIN
                    DECLARE current_balance BIGINT;
                    DECLARE new_balance BIGINT;

                    SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) -
                           COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
                    INTO current_balance
                    FROM ledger_entries
                    WHERE wallet_id = NEW.wallet_id;

                    IF NEW.type = "debit" THEN
                        SET new_balance = current_balance - NEW.amount;
                    ELSE
                        SET new_balance = current_balance + NEW.amount;
                    END IF;

                    IF new_balance < 0 THEN
                        SIGNAL SQLSTATE "45000"
                        SET MESSAGE_TEXT = "Insufficient wallet balance";
                    END IF;
                END
            ');
        } elseif ($driver === 'sqlite') {
            DB::unprepared('
                CREATE TRIGGER prevent_negative_wallet_balance
                BEFORE INSERT ON ledger_entries
                BEGIN
                    SELECT CASE
                        WHEN (
                            SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END), 0) -
                                   COALESCE(SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END), 0)
                            FROM ledger_entries
                            WHERE wallet_id = NEW.wallet_id
                        ) + CASE WHEN NEW.type = "credit" THEN NEW.amount ELSE -NEW.amount END < 0
                        THEN RAISE(ABORT, "Insufficient wallet balance")
                    END;
                END
            ');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_negative_wallet_balance');
    }
}
