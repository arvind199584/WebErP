<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../core/Database.php';

use App\Core\Database;

try {
    $db = Database::getInstance()->getConnection();
    echo "Updating calculate_aa_es_amounts trigger function to support DSR justified amount...\n";

    $sql = <<<SQL
CREATE OR REPLACE FUNCTION calculate_aa_es_amounts()
RETURNS TRIGGER AS $$
DECLARE
    item JSONB;
    total_est DECIMAL(15, 2) := 0;
    total_justified DECIMAL(15, 2) := 0;
    total_aa_es DECIMAL(15, 2) := 0;
    period INT;
    qty DECIMAL;
    effective_qty DECIMAL;
    rate DECIMAL;
    justified_rate DECIMAL;
    esic_total DECIMAL(15, 2) := 0;
    epf_total DECIMAL(15, 2) := 0;
    bonus_total DECIMAL(15, 2) := 0;
    gst_amount DECIMAL(15, 2) := 0;
    is_7_day BOOLEAN := FALSE;
BEGIN
    IF TG_OP = 'UPDATE' AND OLD.status = 'Approved' THEN
        IF current_setting('app.current_user_role', true) <> 'superuser' THEN
             RAISE EXCEPTION 'Cannot edit an Approved AA & ES. Contact Superuser.';
        END IF;
    END IF;

    IF NEW.type = 'Market' THEN
        FOR item IN SELECT * FROM jsonb_array_elements(NEW.boq)
        LOOP
            qty := (item->>'qty')::DECIMAL;
            rate := (item->>'rate')::DECIMAL;
            gst_amount := gst_amount + (qty * rate * (REPLACE(item->>'gst', '%', '')::DECIMAL / 100));
            total_est := total_est + (qty * rate);
        END LOOP;
        NEW.estimated_cost := ROUND(total_est);
        NEW.justified_amount := ROUND(total_est);
        NEW.aa_es_amount := ROUND(total_est + gst_amount);

    ELSIF NEW.type = 'DSR' THEN
        total_est := 0;
        total_justified := 0;
        FOR item IN SELECT * FROM jsonb_array_elements(NEW.boq)
        LOOP
            qty := (item->>'qty')::DECIMAL;
            rate := (item->>'rate')::DECIMAL;
            total_est := total_est + (qty * rate);

            IF item ? 'justified_rate' AND (item->>'justified_rate') IS NOT NULL AND (item->>'justified_rate')::DECIMAL > 0 THEN
                justified_rate := (item->>'justified_rate')::DECIMAL;
                total_justified := total_justified + (qty * justified_rate);
            ELSIF item ? 'justified_amount' AND (item->>'justified_amount') IS NOT NULL AND (item->>'justified_amount')::DECIMAL > 0 THEN
                total_justified := total_justified + (item->>'justified_amount')::DECIMAL;
            ELSE
                total_justified := total_justified + (qty * rate);
            END IF;
        END LOOP;

        NEW.estimated_cost := ROUND(total_est, 2);
        
        -- If NEW.justified_amount was explicitly set to a custom total greater than 0, respect it;
        -- otherwise use the calculated total_justified from the BoQ line items
        IF NEW.justified_amount IS NOT NULL AND NEW.justified_amount > 0 AND total_justified = total_est THEN
            NEW.justified_amount := ROUND(NEW.justified_amount, 2);
        ELSE
            NEW.justified_amount := ROUND(total_justified, 2);
        END IF;
        
        NEW.aa_es_amount := ROUND(total_est, 2);

    ELSIF NEW.type = 'Manpower' THEN
        period := (NEW.boq->>'Period')::INT;
        is_7_day := COALESCE((NEW.boq->>'is_7_day')::BOOLEAN, FALSE);

        FOR item IN SELECT * FROM jsonb_array_elements(NEW.boq->'Items')
        LOOP
            qty := (item->>'qty')::DECIMAL;
            rate := (item->>'rate')::DECIMAL;
            effective_qty := CASE WHEN is_7_day THEN qty * (7.0 / 6.0) ELSE qty END;

            total_est := total_est + (effective_qty * rate * period);

            IF (NEW.boq->>'ESIC')::BOOLEAN THEN esic_total := esic_total + (effective_qty * period * 0.0325 * LEAST(rate, 21000)); END IF;
            IF (NEW.boq->>'EPF')::BOOLEAN THEN epf_total := epf_total + (effective_qty * period * 0.13 * LEAST(rate, 15000)); END IF;
            IF (NEW.boq->>'Bonus')::BOOLEAN THEN bonus_total := bonus_total + (effective_qty * period * 0.0833 * LEAST(rate, 21000)); END IF;
        END LOOP;

        -- 1. ROUND Estimated Cost
        NEW.estimated_cost := ROUND(total_est);

        -- 2. ROUND Justified Amount (EC + 15% CPOH)
        NEW.justified_amount := ROUND(NEW.estimated_cost * 1.15);

        -- 3. GST (18% of Justified) - NO ROUNDING
        gst_amount := NEW.justified_amount * 0.18;

        -- 4. ROUND Grand Total
        NEW.aa_es_amount := ROUND(NEW.justified_amount + gst_amount + esic_total + epf_total + bonus_total);
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
SQL;

    $db->exec($sql);
    echo "SUCCESS: Trigger function calculate_aa_es_amounts() updated in PostgreSQL.\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
