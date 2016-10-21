<?php
$app->get('/getInvoices/:startDate/:endDate(/:canceled/:status)', function ($startDate, $endDate, $canceled = false, $status = true) {
	// Get all students balances
	
	$app = \Slim\Slim::getInstance();
 
	try 
	{
		$db = getDB();
		 $sth = $db->prepare("SELECT 
								students.student_id,
								invoice_balances2.inv_id,
								first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name AS student_name,
								class_name, class_id, class_cat_id,
								inv_date,
								total_due,
								total_paid,
								balance,
								due_date,
								case when now()::date > due_date and balance < 0 then now()::date - due_date else 0 end as days_overdue,
								term_name,
								invoice_balances2.term_id,
								date_part('year', terms.start_date) as year
							FROM app.invoice_balances2
							INNER JOIN app.students
								INNER JOIN app.classes
								ON students.current_class = classes.class_id
							ON invoice_balances2.student_id = students.student_id
							INNER JOIN app.terms ON invoice_balances2.term_id = terms.term_id
							WHERE due_date between :startDate and :endDate
							AND students.active = :status
							AND invoice_balances2.canceled = :canceled");
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

$app->get('/generateInvoices/:termId(/:studentId)', function ($termId, $studentId = null) {
	// Generate invoice(s) for given term
	
	$app = \Slim\Slim::getInstance();

	try 
	{
		$db = getDB();
		$params = array(':termId' => $termId);
		$query = "SELECT * FROM (
								SELECT 
									student_id, student_fee_item_id, student_name, fee_item,
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
									
									CASE WHEN payment_method = 'Installments' THEN
										case when num_payments_this_term > 1 THEN
											generate_series(term_start_date, 
																			term_start_date + ((payment_interval*(num_payments_this_term-1)) || payment_interval2)::interval, 
																			(payment_interval::text || payment_interval2)::interval)::date
										else
											term_start_date
										end 
										 ELSE
										term_start_date
									END as inv_date,
									
									coalesce(round((select sum(amount)  
											from app.invoices 
											inner join app.invoice_line_items ON invoices.inv_id = invoice_line_items.inv_id 
											where invoices.canceled = false 
											and student_fee_item_id = q2.student_fee_item_id 
											and term_id = :termId
										)/num_payments_this_term,2) ,0)
									 as total_amount_invoiced,
									
									num_payments_this_term,
									payment_plan_name
						
							FROM (
								SELECT
									student_id, first_name || ' ' || coalesce(middle_name,'') || ' ' || last_name as student_name,
									fee_item, student_fee_item_id, 
									payment_method, frequency, yearly_amount, num_payments, 
									payment_plan_name, payment_interval, payment_interval2,
									year_start_date, term_start_date, term_end_date, start_next_term,
									CASE 
									WHEN frequency = 'per term' and payment_method = 'Installments' THEN
										CASE WHEN payment_plan_name = '50/50 Installment' THEN
											-- if 50/50 and not paid in first term, invoice
											CASE WHEN num_invoices = 0 THEN 
												(SELECT count(*) FROM (
													SELECT
													generate_series(term_start_date, 
																					term_start_date + ((payment_interval*(num_payments-1)) || payment_interval2)::interval, 
																					(payment_interval::text || payment_interval2)::interval)::date  as inv_date
													 )q2
													 WHERE inv_date >= term_start_date and inv_date < start_next_term
												)
													ELSE 0 
											 END
										ELSE
											-- are there any installments due this term
											(SELECT count(*) FROM (
												SELECT
												generate_series(term_start_date, 
																				term_start_date + ((payment_interval*(num_per_pay_period-1)) || payment_interval2)::interval, 
																				(payment_interval::text || payment_interval2)::interval)::date as inv_date
												)q2
												WHERE inv_date >= term_start_date and inv_date < start_next_term
											)
										END
									ELSE
										-- otherwise we are paying annually, this is due in the first invoice
										CASE WHEN num_invoices = 0 
										THEN 1 
										ELSE 0 
										END
									END::integer AS num_payments_this_term,
									num_invoices,
									num_per_pay_period
								FROM (
									SELECT 
										students.student_id, first_name, middle_name, last_name, 
										fee_item, student_fee_items.student_fee_item_id, 
										payment_interval, payment_interval2, frequency, 
										student_fee_items.payment_method, payment_plan_name,
										coalesce(num_payments,1) as num_payments,
										round( CASE WHEN frequency = 'per term' THEN student_fee_items.amount*3 ELSE student_fee_items.amount END, 2) as yearly_amount,
										case when payment_plan_name = 'Per Month' then 3
												 when payment_plan_name = 'Per Term' then 1
										end as num_per_pay_period,

										(select start_date from app.terms where term_id = :termId) as term_start_date,
										(select end_date   from app.terms where term_id = :termId) as term_end_date,

										coalesce(
											(select start_date from app.terms where start_date > (select start_date from app.terms where term_id = :termId) order by start_date asc limit 1),
											(select end_date from app.terms where term_id = :termId)
										) as start_next_term,

										(select min(start_date) from app.terms where date_part('year',start_date) = date_part('year', (select start_date from app.terms where term_id = :termId))) as year_start_date,
										
										(SELECT count(*) FROM app.invoice_line_items 
											INNER JOIN app.invoices ON invoice_line_items.inv_id = invoices.inv_id 
											WHERE canceled = false 
											AND student_fee_item_id = student_fee_items.student_fee_item_id
											AND date_part('year',inv_date) = date_part('year', (select start_date from app.terms where term_id = :termId) )
										) as num_invoices
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
					WHERE total_amount_invoiced < invoice_amount ";
		if( $studentId !== null )
		{
			$query .= "AND student_id = :studentId ";
			$params['studentId'] = $studentId;
		}

		$query .= " ORDER BY student_name, student_id, inv_date, fee_item";
		
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
		$invoiceQry = $db->prepare("INSERT INTO app.invoices(student_id, inv_date, total_amount, due_date, created_by, term_id) 
																VALUES(:studentId, :invDate, :totalAmt, :dueDate, :userId, :termId)");
			
		$lineItems = $db->prepare("INSERT INTO app.invoice_line_items(inv_id, student_fee_item_id, amount, created_by)
									VALUES(currval('app.invoices_inv_id_seq'), :studentFeeItemId, :amount, :userId)");
 
 
		$db->beginTransaction();
		foreach( $invoices as $invoice )
		{
			$studentId = ( isset($invoice['student_id']) ? $invoice['student_id']: null);
			$invDate = ( isset($invoice['inv_date']) ? $invoice['inv_date']: null);
			$totalAmt = ( isset($invoice['total_amount']) ? $invoice['total_amount']: null);
			$dueDate = ( isset($invoice['due_date']) ? $invoice['due_date']: null);
			$termId = ( isset($invoice['term_id']) ? $invoice['term_id']: null);
			
			$invoiceQry->execute( array(':studentId' => $studentId, 
																	':invDate' => $invDate, 
																	':totalAmt' => $totalAmt, 
																	':dueDate' => $dueDate, 
																	':userId' => $userId, 
																	':termId' => $termId ) );
			
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
	$termId = 	 ( isset($allPostVars['term_id']) ? $allPostVars['term_id']: null);
	$userId = 	 ( isset($allPostVars['user_id']) ? $allPostVars['user_id']: null);
	$lineItems = ( isset($allPostVars['line_items']) ? $allPostVars['line_items']: null);
	
	try 
	{
		$db = getDB();
		
		$updateInvoice = $db->prepare("UPDATE app.invoices
										SET inv_date = :invDate,
											total_amount = :totalAmt,
											due_date = :dueDate,
											term_id = :termId,
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
						':termId' => $termId,
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
