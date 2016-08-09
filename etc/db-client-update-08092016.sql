CREATE OR REPLACE VIEW app.invoice_balances2 AS 
 SELECT invoices.student_id, invoices.inv_id, invoices.inv_date, max(invoices.total_amount) AS total_due, COALESCE(sum(payment_inv_items.amount), 0::numeric) AS total_paid, COALESCE(sum(payment_inv_items.amount), 0::numeric) - max(invoices.total_amount) AS balance, invoices.due_date, 
        CASE
            WHEN invoices.due_date < now()::date AND (COALESCE(sum(payment_inv_items.amount), 0::numeric) - max(invoices.total_amount)) < 0::numeric THEN true
            ELSE false
        END AS past_due, invoices.canceled
   FROM app.invoices
   JOIN (app.invoice_line_items
   LEFT JOIN (app.payment_inv_items
   JOIN app.payments ON payment_inv_items.payment_id = payments.payment_id AND payments.reversed IS FALSE) ON invoice_line_items.inv_item_id = payment_inv_items.inv_item_id) ON invoices.inv_id = invoice_line_items.inv_id
  GROUP BY invoices.student_id, invoices.inv_id;

ALTER TABLE app.invoice_balances2
  OWNER TO postgres;