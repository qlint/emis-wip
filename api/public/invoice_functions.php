<?php
$app->get('/getInvoices/:startDate/:endDate(/:canceled/:status)', function ($startDate, $endDate, $canceled = false, $status = true) {
    // Get all students balances
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
       $sth = $db->prepare("SELECT 
								students.student_id,
								invoice_balances.inv_id,
								first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
								class_name, class_id, class_cat_id,
								inv_date,
								total_due,
								total_paid,
								balance,
								due_date,
								case when now()::date > due_date and balance < 0 then now()::date - due_date else 0 end as days_overdue,
								(select term_name from app.terms where due_date between start_date and end_date) as term_name
							FROM app.invoice_balances
							INNER JOIN app.students
								INNER JOIN app.classes
								ON students.current_class = classes.class_id
							ON invoice_balances.student_id = students.student_id
							WHERE due_date between :startDate and :endDate
							AND students.active = :status
							AND invoice_balances.canceled = :canceled");
		$sth->execute( array(':startDate' => $startDate, ':endDate' => $endDate, ':status' => $status, ':canceled' => $canceled) ); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/generateInvoices/:term(/:studentId)', function ($term, $studentId = null) {
    // Generate invoice(s) for given term
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
        $db = getDB();
		$params = array();
		$termStatement = (  $term == 'current'  ? 'app.current_term' : 'app.next_term' );
		$nextTermStatement = (  $term == 'current'  ? 'app.next_term' : 'app.term_after_next' );
		$query = "SELECT * FROM (
						SELECT
							student_id,  student_fee_item_id, student_name, fee_item, 
							coalesce((CASE 
								WHEN frequency = 'per term' and payment_method = 'Installments' THEN
									case when payment_plan_name = 'Per Month' then
										round(yearly_amount/9,2)
									else
										round(yearly_amount/num_payments,2)
									end
								ELSE
									round(yearly_amount,2)				
							END),0) AS invoice_amount,
							
							CASE
								 WHEN payment_method = 'Installments' THEN
								case when num_payments_this_term > 1 THEN
									generate_series(date_last_invoice, date_last_invoice + ((payment_interval*(num_payments_this_term-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date
								else
									term_start_date
								end 
								 ELSE
								term_start_date								
							END as due_date,
							
							coalesce(round((select sum(amount)  
								from app.invoices 
								inner join app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id 
								where invoices.canceled = false 
								and student_fee_item_id = q2.student_fee_item_id 
								and due_date between term_start_date AND start_next_term
							)/num_payments_this_term,2) ,0) as total_amount_invoiced,
							num_payments_this_term
							
						FROM (
							SELECT
								student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,
								fee_item, student_fee_item_id, payment_method, frequency,yearly_amount,num_payments,term_start_date,payment_plan_name,
								payment_interval,year_start_date,term_end_date,start_next_term,payment_interval2,
								CASE 
									 WHEN frequency = 'per term' and payment_method = 'Installments' THEN
										CASE WHEN payment_plan_name = '50/50 Installment' THEN
											-- if 50/50 and not paid in first term, invoice
											CASE WHEN (
												SELECT count(*) 
												FROM app.invoice_line_items
												INNER JOIN app.invoices ON invoice_line_items.inv_id = invoices.inv_id
												WHERE canceled = false
												AND student_fee_item_id = q.student_fee_item_id
											) = 0 THEN 
												(SELECT count(*) FROM (
													SELECT
													generate_series(term_start_date, term_start_date + ((payment_interval*(num_payments-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date  as due_date
													FROM (
														SELECT payment_interval,payment_interval2,num_payments
														FROM app.students
														INNER JOIN app.installment_options 
														ON installment_option_id = installment_options.installment_id
														WHERE student_id = q.student_id
													) q
												   )q2
												)

											 ELSE 0 END
										ELSE
											-- are there any installments due this term
											(SELECT count(*) FROM (
												SELECT
												generate_series(date_last_invoice, date_last_invoice + ((payment_interval*(num_per_pay_period-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date
												--generate_series(year_start_date, year_start_date + ((payment_interval*(num_payments-1)) || payment_interval2)::interval, (payment_interval::text || payment_interval2)::interval)::date  as due_date
												FROM (
														SELECT payment_interval,payment_interval2,num_payments,
														coalesce( (select max(due_date) from app.invoices where invoices.student_id = students.student_id), (select start_date from app.current_term)) as date_last_invoice
														FROM app.students
														INNER JOIN app.installment_options 
														ON installment_option_id = installment_options.installment_id
														WHERE student_id = 5
													) q
												)q2
											)
										END
									 ELSE
										-- otherwise we are paying annually, this is due in the first invoice
										CASE WHEN (
											SELECT count(*) 
											FROM app.invoice_line_items
											INNER JOIN app.invoices ON invoice_line_items.inv_id = invoices.inv_id
											WHERE canceled = false
											AND student_fee_item_id = q.student_fee_item_id
										) = 0 THEN 1 ELSE 0 END
								END::integer AS num_payments_this_term,
								date_last_invoice
							FROM (
								SELECT 
									students.student_id, first_name, middle_name, last_name, 
									fee_item, student_fee_items.student_fee_item_id, payment_interval,payment_interval2,
									student_fee_items.payment_method, frequency, coalesce(num_payments,1) as num_payments,payment_plan_name,
									round( CASE WHEN frequency = 'per term' THEN student_fee_items.amount*3 ELSE student_fee_items.amount END, 2) as yearly_amount,
									(select start_date from $termStatement) as term_start_date,
									(select end_date from $termStatement) as term_end_date,
									coalesce((select start_date from $nextTermStatement), (select end_date from $termStatement)) as start_next_term,
									( select min(start_date) from app.terms where date_part('year',start_date) = date_part('year', (select start_date from $termStatement)) ) as year_start_date,
									coalesce( (select max(due_date) from app.invoices where invoices.student_id = students.student_id), (select start_date from $termStatement)) as date_last_invoice,
									case when payment_plan_name = 'Per Month' then 4
										 when payment_plan_name = 'Per Term' then 1
									end as num_per_pay_period
								FROM app.students									
								INNER JOIN app.student_fee_items
									INNER JOIN app.fee_items
									ON student_fee_items.fee_item_id = fee_items.fee_item_id AND fee_items.active is true									
								ON students.student_id = student_fee_items.student_id AND student_fee_items.active = true
								LEFT JOIN app.installment_options ON students.installment_option_id = installment_options.installment_id
								WHERE students.active = true
								ORDER BY students.student_id
							) q
							
						) q2
						WHERE q2.num_payments_this_term > 0
					) q3
					WHERE total_amount_invoiced < invoice_amount
				";
		if( $studentId !== null )
		{
			$query .= "AND student_id = :studentId ";
			$params = array('studentId' => $studentId);
		}

		$query .= " ORDER BY student_id, due_date, fee_item";
		
        $sth = $db->prepare($query);
		$sth->execute( $params ); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->post('/createInvoice', function () use($app) {
    // create invoice	
	$allPostVars = json_decode($app->request()->getBody(),true);
	
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$invoices = ( isset($allPostVars['invoices']) ? $allPostVars['invoices']: null);
	
    try 
    {
        $db = getDB();
        $invoiceQry = $db->prepare("INSERT INTO app.invoices(student_id, inv_date, total_amount, due_date, created_by) 
									VALUES(:studentId, :invDate, :totalAmt, :dueDate, :userId)");
			
		$lineItems = $db->prepare("INSERT INTO app.invoice_line_items(inv_id, student_fee_item_id, amount, created_by)
									VALUES(currval('app.invoices_inv_id_seq'), :studentFeeItemId, :amount, :userId)");
 
 
		$db->beginTransaction();	
		foreach( $invoices as $invoice )
		{
			$studentId = ( isset($invoice['student_id']) ? $invoice['student_id']: null);
			$invDate = ( isset($invoice['inv_date']) ? $invoice['inv_date']: null);
			$totalAmt = ( isset($invoice['total_amount']) ? $invoice['total_amount']: null);
			$dueDate = ( isset($invoice['due_date']) ? $invoice['due_date']: null);
			
			$invoiceQry->execute( array(':studentId' => $studentId, ':invDate' => $invDate, ':totalAmt' => $totalAmt, ':dueDate' => $dueDate, ':userId' => $userId ) );
			
			foreach( $invoice['line_items'] as $lineItem )
			{
				$studentFeeItemId = ( isset($lineItem['student_fee_item_id']) ? $lineItem['student_fee_item_id']: null);
				$amount = ( isset($lineItem['amount']) ? $lineItem['amount']: null);
				
				$lineItems->execute( array(':studentFeeItemId' => $studentFeeItemId, ':amount' => $amount, ':userId' => $userId ) );
			}
		}
		$db->commit();
	
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->get('/getInvoiceDetails/:inv_id', function ($invId) {
    // Get all invoice details
	
	$app = \Slim\Slim::getInstance();
 
    try 
    {
       $db = getDB();
       $sth = $db->prepare("SELECT *, amount - total_paid as balance
							FROM (
							SELECT 
								invoices.inv_id,
								inv_date,
								invoice_line_items.amount,
								coalesce((select sum(payment_inv_items.amount) 
										from app.payment_inv_items 
										inner join app.payments on payment_inv_items.payment_id = payments.payment_id
										where payment_inv_items.inv_item_id = invoice_line_items.inv_item_id 
										AND reversed = false),0) as total_paid,
								due_date,
								inv_item_id,
								fee_item
							FROM app.invoices
							INNER JOIN app.invoice_line_items
								INNER JOIN app.student_fee_items
									INNER JOIN app.fee_items
									ON student_fee_items.fee_item_id = fee_items.fee_item_id
								ON invoice_line_items.student_fee_item_id = student_fee_items.student_fee_item_id
							ON invoices.inv_id = invoice_line_items.inv_id
							INNER JOIN app.students
								INNER JOIN app.classes
								ON students.current_class = classes.class_id
							ON invoices.student_id = students.student_id
							WHERE invoices.inv_id = :invId
							ORDER BY fee_item
							) q");
		$sth->execute( array(':invId' => $invId) ); 
        $results = $sth->fetchAll(PDO::FETCH_OBJ);
 
        if($results) {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'data' => $results ));
            $db = null;
        } else {
            $app->response->setStatus(200);
            $app->response()->headers->set('Content-Type', 'application/json');
            echo json_encode(array('response' => 'success', 'nodata' => 'No records found' ));
            $db = null;
        }
 
    } catch(PDOException $e) {
        $app->response()->setStatus(200);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/updateInvoice', function() use($app){
	 // Update invoice	
	$allPostVars = json_decode($app->request()->getBody(),true);

	$invId = 	 ( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$invDate = 	 ( isset($allPostVars['inv_date']) ? $allPostVars['inv_date']: null);
	$totalAmt =  ( isset($allPostVars['total_amount']) ? $allPostVars['total_amount']: null);
	$dueDate = 	 ( isset($allPostVars['due_date']) ? $allPostVars['due_date']: null);
	$userId = 	 ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$lineItems = ( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
	
	try 
    {
        $db = getDB();
		
		$updateInvoice = $db->prepare("UPDATE app.invoices	
										SET inv_date = :invDate,
											total_amount = :totalAmt,
											due_date = :dueDate,
											modified_date = now(),
											modified_by= :userId
										WHERE inv_id = :invId");
		
		// prepare the possible statements
		if( count($lineItems) > 0 )
		{
			$itemUpdate = $db->prepare("UPDATE app.invoice_line_items
										SET amount = :amount,
											modified_date = now(),
											modified_by = :userID
										WHERE inv_item_id = :invItemId");
			
			$itemInsert = $db->prepare("INSERT INTO app.invoice_line_items(inv_id, student_fee_item_id, amount, created_by) 
										VALUES(:invId,:studentFeeItemID,:amount,:userId);"); 
			

			$deleteLine = $db->prepare("DELETE FROM app.invoice_line_items WHERE inv_item_id = :invItemId");
		}
		else
		{
			$deleteAllLine = $db->prepare("DELETE FROM app.invoice_line_items WHERE inv_id = :invId");
		}
		
		// get what is already set of this invoice
		$query = $db->prepare("SELECT inv_item_id FROM app.invoice_line_items WHERE inv_id = :invId");
		$query->execute( array('invId' => $invId) );
		$currentLineItems = $query->fetchAll(PDO::FETCH_OBJ);
			
		
		$db->beginTransaction();
	
		$updateInvoice->execute( array(':invId' => $invId,
						':invDate' => $invDate,
						':totalAmt' => $totalAmt,
						':dueDate' => $dueDate,
						':userId' => $userId
		) );	
		
		if( count($lineItems) > 0 ) 
		{		
	
			// loop through and add or update
			foreach( $lineItems as $lineItem )
			{
				$amount = 			( isset($lineItem['amount']) ? $lineItem['amount']: null);
				$invItemId = 		( isset($lineItem['inv_item_id']) ? $lineItem['inv_item_id']: null);				
				$studentFeeItemID = ( isset($lineItem['student_fee_item_id']) ? $lineItem['student_fee_item_id']: null);
				
				// this item exists, update it, else insert
				if( $invItemId !== null && !empty($invItemId) )
				{
					// need to update all terms, current and future of this year
					// leaving previous terms as they were
					$itemUpdate->execute(array(':amount' => $amount, 
												':invItemId' => $invItemId,
												':userID' => $userId ));
				}
				else
				{
					// check if was previously added, if so, reactivate
					// else fee items is new, add it
					// needs to be added once term term if per term fee item

					$itemInsert->execute( array(
						':invId' => $invId,
						':studentFeeItemID' => $studentFeeItemID,
						':amount' => $amount,
						':userId' => $userId)
					);
					
				}
			}	
			
		
			// look for items to remove
			// compare to what was passed in			
			foreach( $currentLineItems as $currentLineItem )
			{	
				$deleteMe = true;
				// if found, do not delete
				foreach( $lineItems as $lineItem )
				{
					if(  isset($lineItem['inv_item_id']) && $lineItem['inv_item_id'] == $currentLineItem->inv_item_id )
					{
						$deleteMe = false;
					}
				}
				
				if( $deleteMe )
				{
					$deleteLine->execute(array(':invItemId' => $currentLineItem->inv_item_id));
				}
			}

		}
		else
		{
			$deleteAllLine->execute(array('invId' => $invId));
		}
		
		$db->commit();
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }

});

$app->put('/cancelInvoice', function () use($app) {
	// update invoice to cancelled
	$allPostVars = json_decode($app->request()->getBody(),true);
	$invId = ( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	try 
    {
        $db = getDB();
		
		$updateInvoice = $db->prepare("UPDATE app.invoices	
										SET canceled = true,
											modified_date = now(),
											modified_by= :userId
										WHERE inv_id = :invId");
		
	
		$updateInvoice->execute( array(':invId' => $invId,
						':userId' => $userId
		) );	
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
		$app->response()->headers->set('Content-Type', 'application/json');
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }
});

$app->put('/reactivateInvoice', function() use($app) {
	// update invoice to cancelled
	$allPostVars = json_decode($app->request()->getBody(),true);
	$invId = ( isset($allPostVars['inv_id']) ? $allPostVars['inv_id']: null);
	$userId = ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	try 
    {
        $db = getDB();
		
		$updateInvoice = $db->prepare("UPDATE app.invoices	
										SET canceled = false,
											modified_date = now(),
											modified_by= :userId
										WHERE inv_id = :invId");		
	
		$updateInvoice->execute( array(':invId' => $invId,
						':userId' => $userId
		) );	
 
		$app->response->setStatus(200);
        $app->response()->headers->set('Content-Type', 'application/json');
        echo json_encode(array("response" => "success", "code" => 1));
        $db = null;
 
 
    } catch(PDOException $e) {
		
		$db->rollBack();
        $app->response()->setStatus(404);
        echo  json_encode(array('response' => 'error', 'data' => $e->getMessage() ));
    }
});

?>
