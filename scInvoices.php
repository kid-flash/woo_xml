
<?php
/************************************************************
 * Remember that to get term_taxonomy_id you must find the status in
 * wp_terms table, then take the term_id to the wp_term_taxonomy table
 * and find the matching term_taxonomy_id.  * In this case I was looking for orders with a certain status (next invoice)  * and then updating the status to Complete after I outputted the invoice.
 * ******************************************************/
 
// ...Connect to WP database
$dbc = mysql_connect('localhost','db_username','db_password'); 
if ( !$dbc ) {
    die( 'Not Connected: ' . mysql_error());
}
// Select the database

$db = mysql_select_db('db_name'); 
if (!$db) {
    echo "There is no database: " . $db;
}

    //check to see if anything has been posted or don't do anything but post the HTML
    if (!empty($_POST['accountNum'])){
      
 print_r($_POST); 
	 
    //declare variabels
     $accountNum = $_POST['accountNum'];
     //$todayDate = date("m/d/y"); // current date
	// $userID = ucwords($_SESSION['username']);
   
     //SQL query to get orders for client that are set to 'next invoice'
      $invoiceQurey = "SELECT wp_posts.ID, wp_posts.post_title,ot.meta_value as total,od.meta_value as order_desc, cu.meta_value as customer, fn.meta_value as fname, ln.meta_value as lname 
                
                FROM wp_posts
                LEFT JOIN wp_postmeta AS ot ON (wp_posts.ID = ot.post_id AND ot.meta_key='_order_total')
                LEFT JOIN wp_postmeta AS od ON (wp_posts.ID = od.post_id  AND od.meta_key='order_description')
                LEFT JOIN wp_postmeta AS cu ON (wp_posts.ID =cu.post_id  AND cu.meta_key='_customer_user')
                 LEFT JOIN wp_usermeta AS fn ON (cu.meta_value = fn.user_id AND fn.meta_key='first_name') 
                LEFT JOIN wp_usermeta AS ln ON (cu.meta_value = ln.user_id AND ln.meta_key='last_name')
                LEFT JOIN wp_term_relationships ON wp_posts.ID = wp_term_relationships.object_id
                WHERE wp_posts.post_type = 'shop_order'
                AND wp_posts.post_status = 'publish'
                AND wp_term_relationships.term_taxonomy_id = '190'
                AND cu.meta_value = ".$accountNum."
                ORDER BY wp_posts.ID";
      
      
       $result = mysql_query($invoiceQurey);
      $count = mysql_num_rows($result);
   
      
     if ($count!=0){  //check to make sure the qurey returns an account
  

              
  //initialize xml code
	//$xml = new SimpleXMLElement('<xml/>');
		$xml = new SimpleXMLElement('<Client/>');
			
			$order = $xml ->addChild('Order');  
				$orderedItems = $order -> addChild('Ordered_Items');
	
  
    
    while($rows=mysql_fetch_array($result)){
    
        //variables for loop
		
		$ordNum = $rows['ID'];
        $serviceName = "website order";
        $jobName = "Inv# ".$ordNum.", ".$rows['order_desc'];
        $qty = "1";
		$price = $rows['total']; 
        
            
          //calculate the line item total amount
           $amount = $qty * $price;

 //insert the totals from each line item into an array to calculate total invoice amount later
           $totalsAr[] = $amount;           


		$orderedItem = $orderedItems->addChild('Ordered_Item');
		$orderedItem->addChild ('Description',$serviceName);	
		 $orderedItem->addChild ('Price',$price);
		 $orderedItem->addChild ('Quantity',$qty);
		 $orderedItem->addchild ('Item_Notes',$jobName);

                    
         mysql_query("UPDATE wp_term_relationships SET term_taxonomy_id='167' WHERE object_id ='$ordNum' ")or die (mysql_error()); 
		 
       } //end qurey while
   } 
   else
   {
       echo'This Client does not have any billable invoices';
   }
       
     
     //SQL qurey to get account information
     $nameQurey = mysql_query("SELECT DISTINCT wp_posts.ID, cu.meta_value as customer, fn.meta_value as fname, ln.meta_value as lname                
                FROM wp_posts
                LEFT JOIN wp_postmeta AS cu ON (wp_posts.ID = cu.post_id  AND cu.meta_key='_customer_user')
                LEFT JOIN wp_usermeta AS fn ON (cu.meta_value = fn.user_id AND fn.meta_key='first_name') 
                LEFT JOIN wp_usermeta AS ln ON (cu.meta_value = ln.user_id AND ln.meta_key='last_name')
                LEFT JOIN wp_term_relationships ON wp_posts.ID = wp_term_relationships.object_id
                WHERE cu.meta_value = ".$accountNum."
                GROUP BY cu.meta_value    
                ");
     $numrows=  mysql_num_rows($nameQurey);
     
     if($numrows!=0){
        $row =  mysql_fetch_assoc($nameQurey);     
                $lname= $row['lname'];
		$fname= $row['fname'];
		$xml->addChild('First_Name',$fname);
		$xml->addChild('Last_Name',$lname);  
     }
     else {
         echo'That AccountNumber Does Not Seem to Exist';
         }
         
   //calculate invoice total amount from the totalsArray
       $totalforinvoice = array_sum($totalsAr);
	   
        require_once ("invoicecounter.php"); //counts to next invoice number
     

 
       // create iif file named by calculated invoice number
       $qbFile = fopen("invoices/qbImport".$Hits.".xml","wb");
       
       //write the file
       if (is_writable("invoices/qbImport".$Hits.".xml")){
           if (fwrite($qbFile,$xml->asXML())){
              //present link to download iif file 
             echo'<a href="invoices/qbImport'.$Hits.'.xml">Download Your File</a><br/>';

             
           }
                 else {
                echo "<p>Cannot add your entry</p>";
            }
        }
        else {
            echo "<p>The file is not writeable</p>";
        }  
        fclose($qbFile);
     
         
    }
     
?>      
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Send Invoice</title>
    </head>
    <body>
        <div id="mainContent">
        <h1>Send an Invoice to Quickbooks</h1>
        <p>Enter info below.</p>
      
        <form name="invoiceQuery" action="" method="POST">
        <!-- Account# <input type="text" size="25" name="accountNum"/> <br/> -->
        
        <label for="accountNum">Account Number: </label>
                
                 <?
                          $sqlQB="SELECT DISTINCT wp_posts.ID, cu.meta_value as customer, fn.meta_value as fname, ln.meta_value as lname                
                FROM wp_posts
                LEFT JOIN wp_postmeta AS cu ON (wp_posts.ID = cu.post_id  AND cu.meta_key='_customer_user')
                LEFT JOIN wp_usermeta AS fn ON (cu.meta_value = fn.user_id AND fn.meta_key='first_name') 
                LEFT JOIN wp_usermeta AS ln ON (cu.meta_value = ln.user_id AND ln.meta_key='last_name')
                LEFT JOIN wp_term_relationships ON wp_posts.ID = wp_term_relationships.object_id
                WHERE wp_posts.post_type = 'shop_order'
                AND wp_posts.post_status = 'publish'
                AND wp_term_relationships.term_taxonomy_id = '190'
                GROUP BY cu.meta_value ORDER BY ln.meta_value DESC";
            $resultQB=mysql_query($sqlQB);

            $options="";

            while ($row=mysql_fetch_array($resultQB)) {

                $acctQB=$row["customer"];
                $lname=$row['lname'];
                $fname = $row['fname'];
                $name = $lname.", ".$fname;
                $options.="<OPTION VALUE=\"$acctQB\">".$acctQB . "- " .$name.'</option>';
            }
            ?>
            <SELECT NAME="accountNum" id="accountNum">
            <OPTION VALUE=0>Account Number
           <?=$options?> 
            </SELECT>
                     
         <input type="submit" value="Submit"/>   
            
            
        </form>
        </div>
    </body>
</html>
