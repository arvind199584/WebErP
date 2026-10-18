<?php
declare(strict_types=1);

require_once __DIR__ . '/../../core/Database.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();

    // 1. Add 'DSR' to aa_es_type enum
    $db->exec("ALTER TYPE aa_es_type ADD VALUE IF NOT EXISTS 'DSR';");
    echo "1. Added 'DSR' to aa_es_type enum successfully.\n";

    // 2. Update calculate_aa_es_amounts() trigger function to handle DSR type
    $sqlFunction = <<<'SQL'
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
        -- DSR BOQ items calculation: Sum of (qty * rate) across all items
        FOR item IN SELECT * FROM jsonb_array_elements(NEW.boq)
        LOOP
            qty := (item->>'qty')::DECIMAL;
            rate := (item->>'rate')::DECIMAL;
            total_est := total_est + (qty * rate);
        END LOOP;
        NEW.estimated_cost := ROUND(total_est, 2);
        NEW.justified_amount := ROUND(total_est, 2);
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

    $db->exec($sqlFunction);
    echo "2. Updated calculate_aa_es_amounts() trigger function for DSR type.\n";

    echo "\nAll database migrations for DSR AA_ES applied successfully!\n";

} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
