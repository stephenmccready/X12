<?php
/*

This script is intended to be used as a template for processing an 
	ANSI ASC X12.999 Functional Acknowledgement ( version: 005010X231A1 )

	Each loop, segment and data element has been documented to a reasonable level.
	For detailed information about the X12 999 Functional Acknowledgement transaction go to www.wpc-edi.com

This is a 'plain vanilla' implementation of the standard.

The 999 informs the submitter that the functional group arrived at the destination. It may include 
information about the syntactical quality of the functional group and the implementation guide 
compliance. 
The Implementation Acknowledgment (999) transaction is not required as a response to receipt of 
a batch transaction compliant with this implementation guide. 
The Implementation Acknowledgment (999) transaction is not required as a response to receipt of 
a real-time transaction compliant with this implementation guide.

For debugging, uncomment out the echo commands.

*/

/********** PARAMETERS **********/
// Set filename to the full path and filename of the file to be input.
$filename='C:\\EDI__Test\\X12_.txt.999';

/********************************/

$error_msg="";
$msg="";
$summary_msg="";

$handle = @fopen($filename, 'rb');
$contents = fread($handle, filesize($filename));
@fclose($handle);

$actual_contents = array();
$actual_contents = explode("~",$contents);

//Getting rid of all single quotes in all the elements of array $actual_contents
$new_actual_contents = str_replace("'","",$actual_contents);

$count = count($new_actual_contents);
$rcdcount = 1;
$final_array = array();

for($i = 2; $i<$count; $i++)
{	$final_array[$i-2] = $new_actual_contents[$i];	}

$finalcount = count($final_array);
$segment_count=0;
$ST_count=0;

/* ERROR MESSAGE ARRAYS */
$AK304_error_array[1]="Unrecognized segment ID";
$AK304_error_array[2]="Unexpected segment";
$AK304_error_array[3]="Mandatory segment missing";
$AK304_error_array[4]="Loop occurs over maximum times";
$AK304_error_array[5]="Segment exceeds maximum use";
$AK304_error_array[6]="Segment not in defined transaction set";
$AK304_error_array[7]="Segment not in proper sequence";
$AK304_error_array[8]="Segment has data element errors";
$AK304_error_array[511]="Trailing separators encountered (custom code)";

$AK403_error_array[1]="Mandatory data element missing";
$AK403_error_array[2]="Conditional required data element missing";
$AK403_error_array[3]="Too many data elements ";
$AK403_error_array[4]="Data element is too short";
$AK403_error_array[5]="Data element is too long";
$AK403_error_array[6]="Invalid character in data element";
$AK403_error_array[7]="Invalid code value";
$AK403_error_array[8]="Invalid date";
$AK403_error_array[9]="Invalid time";
$AK403_error_array[10]="Exclusion condition violated";

$AK501_status[ord("A")]="Accepted";
$AK501_status[ord("E")]="Accepted but errors were noted";
$AK501_status[ord("M")]="Rejected, message authentication code (MAC) failed";
$AK501_status[ord("P")]="Partially accepted, at least one transaction set was rejected";
$AK501_status[ord("R")]="Rejected";
$AK501_status[ord("W")]="Rejected, assurance failed validity tests";
$AK501_status[ord("X")]="Rejected, content after decryption could not be analyzed";

$AK502_error_array[1]="Transaction set not supported";
$AK502_error_array[2]="Transaction set trailer missing";
$AK502_error_array[3]="Transaction set control number in header and trailer do not match";
$AK502_error_array[4]="Number of included segments does not match actual count";
$AK502_error_array[5]="One or more segments in error";
$AK502_error_array[6]="Missing or invalid transaction set identifier ";
$AK502_error_array[7]="Missing or invalid transaction set control number (a duplicate transaction number may have occurred)";

$AK901_status[ord("A")]="Accepted";
$AK901_status[ord("E")]="Accepted but errors were noted";
$AK901_status[ord("M")]="Rejected, message authentication code (MAC) failed";
$AK901_status[ord("P")]="Partially accepted, at least one transaction set was rejected";
$AK901_status[ord("R")]="Rejected";
$AK901_status[ord("W")]="Rejected, assurance failed validity tests";
$AK901_status[ord("X")]="Rejected, content after decryption could not be analyzed";			

$AK905_error_array[1]="Functional group not supported";
$AK905_error_array[2]="Functional group version not supported";
$AK905_error_array[3]="Functional group trailer missing";
$AK905_error_array[4]="Group control number in the functional group header and trailer do not agree";
$AK905_error_array[5]="Number of included transaction sets does not match actual count";
$AK905_error_array[6]="Group control number violates syntax (a duplicate group control number may have occurred)";

$IK304_error_array[ord("1")]="Unrecognized segment ID";
$IK304_error_array[ord("2")]="Unexpected segment";
$IK304_error_array[ord("3")]="Mandatory segment missing";
$IK304_error_array[ord("4")]="Loop occurs over maximum times";
$IK304_error_array[ord("5")]="Segment exceeds maximum use";
$IK304_error_array[ord("6")]="Segment not in defined transaction set";
$IK304_error_array[ord("7")]="Segment not in proper sequence";
$IK304_error_array[ord("8")]="Segment has data element errors";
$IK304_error_array[ord("I4")]="Implementation 'Not Used' Segment Present";
$IK304_error_array[ord("I6")]="Implementation Dependent Segment Missing";
$IK304_error_array[ord("I7")]="Implementation Loop Occurs Under Minimum Times";
$IK304_error_array[ord("I8")]="Implementation Segment Below Minimum Use";
$IK304_error_array[ord("I9")]="Implementation Dependent 'Not Used' Segment Present";

$IK403_error_array[ord("1")]="Mandatory data element missing";
$IK403_error_array[ord("2")]="Conditionally required data element missing";
$IK403_error_array[ord("3")]="Too many data elements";
$IK403_error_array[ord("4")]="Data element is too short";
$IK403_error_array[ord("5")]="Data element too long";
$IK403_error_array[ord("6")]="Invalid character in data element";
$IK403_error_array[ord("7")]="Invalid code value";
$IK403_error_array[ord("8")]="Invalid Date";
$IK403_error_array[ord("9")]="Invalid Time";
$IK403_error_array[ord("10")]="Exclusion Condition Violated";
$IK403_error_array[ord("12")]="Too Many Repetitions";
$IK403_error_array[ord("13")]="Too Many Components";
$IK403_error_array[ord("I6")]="Code Value Not Used In Implementation";
$IK403_error_array[ord("I9")]="Implementation ";
$IK403_error_array[ord("I10")]="Implementation 'Not Used' Data Element Present";
$IK403_error_array[ord("I11")]="Implementation Too Few Repetitions";
$IK403_error_array[ord("I12")]="Implementation Pattern Match Failure";
$IK403_error_array[ord("I13")]="Implementation Dependent 'Not Used' Data Element Present";

for($i = 0; $i<$finalcount; $i++)
{	
	// explode the segment into an array of data_items
	$data_item = array();
	$data_item=explode("*",$final_array[$i]);
	$segment_count++;

	switch(substr($final_array[$i],0,3))
	{
		case "ISA":		// INTERCHANGE CONTROL HEADER
			$ISA01_Authorization_Information_Qualifier=$data_item[1];
			$ISA02_Authorization_Information=$data_item[2];
			$ISA03_Security_Information_Qualifier=$data_item[3];
			$ISA04_Security_Information=$data_item[4];
			$ISA05_Interchange_ID_Qualifier=$data_item[5];
			$ISA06_Interchange_Sender_ID=$data_item[6];
			$ISA07_Interchange_ID_Qualifier=$data_item[7];
			$ISA08_Interchange_Receiver_ID=$data_item[8];
			$ISA09_Interchange_Date=$data_item[9];
			$ISA10_Interchange_Time=$data_item[10];
			$ISA11_Interchange_Control_Standards_Identifier=$data_item[11];
			$ISA12_Interchange_Control_Version_Number=$data_item[12];
			$ISA13_Interchange_Control_Number=$data_item[13];
			$ISA14_Usage_Indicator=$data_item[14];
			$ISA15_Component_Element_Separator=$data_item[15];
		break;
		
		case "GS*":		// FUNCTIONAL GROUP HEADER
			$GS01_Functional_Identifier_Code=$data_item[1];
			$GS02_Application_Senders_Code=$data_item[2];
			$GS03_Application_Receiver_Code=$data_item[3];
			$GS04_Date=$data_item[4];
			$GS05_Time=$data_item[5];
			$GS06_Group_Control_Number=$data_item[6];
			$GS07_Responsible_Agency_Code=$data_item[7];
			$GS08_Version=$data_item[8];
		break;
		
		case "ST*":		// Transaction Set Header
			$ST01_Transaction_Set_Identifier_Code=$data_item[1];
			$ST02_Transaction_Set_Control_Number=$data_item[2];
			$ST_count++;
		break;
		
		case "AK1":		// Functional Group Response Header
			$AK101_Functional_Identifier_Code=$data_item[1];
			$AK102_Functional_Group_Control_Number=$data_item[2];
			$AK103_Functional_Version_Release=$data_item[3];
			$summary_msg.="Group ID: ".$AK101_Functional_Identifier_Code."&nbsp; Group Control Number: ".$AK102_FN_Group_Control_Num."&nbsp; Version Release".$AK103_Functional_Version_Release."<br />";
		break;
		
		case "AK2":		// Transaction Set Response Header
			$AK201_XN_Set_ID=$data_item[1];
			$AK202_XN_Set_Control_Num=$data_item[2];
			$AK203_Implementation_Convention_Ref=$data_item[2];
			$error_msg.="Transaction Set ID: ".$AK201_XN_Set_ID."&nbsp; Transaction Set Control Number: ".$AK202_XN_Set_Control_Num."&nbsp;Implementation Convention Ref:".$AK203_Implementation_Convention_Ref;
		break;
		
		case "AK3":		// Data Segment Note
			$AK301_SegmentInError=$data_item[1];
			$AK302_ErrorPosition=$data_item[2];
			$AK303_BoundedLoop=$data_item[3];
			if(sizeof($data_item)>4)
			{	
				$AK304_Segment_ErrorCode=$data_item[4];	
				$error_msg.="Segment in error: ".$AK301_SegmentInError."&nbsp;[".$AK302_ErrorPosition."]&nbsp;Loop: ".$AK303_BoundedLoop."&nbsp;".$AK304_error_array[$AK304_Segment_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK304_Segment_ErrorCode="";	}
		break;
		
		case "AK4":		// Data Element Note
			$AK401_ErrorPosition=$data_item[1];
			$AK402_ErrorPosition=$data_item[2];
			$AK403_DataElement_ErrorCode=$data_item[3];
			if(sizeof($data_item)>4)
			{	
				$AK404_ElementInError=$data_item[4];	
				$error_msg.="Data element in error: ".$AK401_ErrorPosition."&nbsp;[".$AK402_ErrorPosition."]&nbsp;".$AK403_error_array[$AK403_DataElement_ErrorCode].":&nbsp;".$AK404_ElementInError."<br />";
				$errorCount++;
			}
			else
			{	$AK404_ElementInError="";	}
		break;
		
		case "AK5":	// Transaction Set Response Trailer
			$AK501_TransactionSetStatus=$data_item[1];
			
			$msg.="Transaction Set ".$AK501_status[ord($AK501_TransactionSetStatus)]."<br />";

			if(sizeof($data_item)>2)
			{	
				$AK502_ErrorCode=$data_item[2];
				$error_msg.="&nbsp;".$AK502_error_array[$AK502_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK502_ErrorCode="";	}
			if(sizeof($data_item)>3)
			{	
				$AK503_ErrorCode=$data_item[3];
				$error_msg.="&nbsp;".$AK502_error_array[$AK504_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK503_ErrorCode="";	}
			if(sizeof($data_item)>4)
			{	
				$AK504_ErrorCode=$data_item[4];
				$error_msg.="&nbsp;".$AK502_error_array[$AK504_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK504_ErrorCode="";	}
			if(sizeof($data_item)>5)
			{	
				$AK505_ErrorCode=$data_item[5];
				$error_msg.="&nbsp;".$AK502_error_array[$AK505_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK505_ErrorCode="";	}
			if(sizeof($data_item)>6)
			{	
				$AK506_ErrorCode=$data_item[6];
				$error_msg.="&nbsp;".$AK502_error_array[$AK506_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK506_ErrorCode="";	}
		break;
		
		case "AK9":		// Functional Group Response Trailer
			$AK901_FunctionalGroupStatus=$data_item[1];
			$AK902_TransactionSetCount=$data_item[2];
			$AK903_TransactionSetRecvdCount=$data_item[3];
			$AK904_TransactionSetAcceptedCount=$data_item[4];
			$summary_msg.="Functional Group ".$AK901_status[ord($AK901_FunctionalGroupStatus)]."<br />";
			$summary_msg.="Transaction Set Count ".$AK902_TransactionSetCount."<br />";
			$summary_msg.="Transaction Set Received Count ".$AK903_TransactionSetRecvdCount."<br />";
			$summary_msg.="Transaction Set Accepted Count ".$AK904_TransactionSetAcceptedCount."<br />";
			if(sizeof($data_item)>5)
			{	
				$AK905_ErrorCode=$data_item[5];
				$error_msg.="&nbsp;".$AK905_error_array[$AK905_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK905_ErrorCode="";	}
			if(sizeof($data_item)>6)
			{	
				$AK906_ErrorCode=$data_item[6];
				$error_msg.="&nbsp;".$AK905_error_array[$AK906_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK906_ErrorCode="";	}
			if(sizeof($data_item)>7)
			{	
				$AK907_ErrorCode=$data_item[7];
				$error_msg.="&nbsp;".$AK905_error_array[$AK907_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK907_ErrorCode="";	}
			if(sizeof($data_item)>8)
			{	
				$AK908_ErrorCode=$data_item[8];
				$error_msg.="&nbsp;".$AK905_error_array[$AK908_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK908_ErrorCode="";	}
			if(sizeof($data_item)>9)
			{	
				$AK909_ErrorCode=$data_item[9];
				$error_msg.="&nbsp;".$AK905_error_array[$AK909_ErrorCode]."<br />";
				$errorCount++;
			}
			else
			{	$AK909_ErrorCode="";	}
		break;
		
		case "CTX":		// Segment Context
		{	
			if(sizeof($data_item)>1)
			{
				$error_msg.="&nbsp;<b>Error with check:".$data_item[1]."</b><br />";
				$errorCount++;
			}
		}
		break;

		case "IK3":		// Error Identification
		{		
			$IK301_SegmentInError=$data_item[1];
			$IK302_ErrorPosition=$data_item[2];
			$IK303_BoundedLoop=$data_item[3];
			if(sizeof($data_item)>4)
			{	
				$IK304_Segment_ErrorCode=$data_item[4];	
				$error_msg.="Segment in error: ".$IK301_SegmentInError."&nbsp;[".$IK302_ErrorPosition."]&nbsp;Loop: ".$IK303_BoundedLoop."&nbsp;".$AK304_error_array[ord($IK304_Segment_ErrorCode)]."<br />";
				$errorCount++;
			}
			else
			{	$AK304_Segment_ErrorCode="";	}
		}
		break;
		
		case "IK4":		// Implementation Data Element Note	
		{		
			$IK401=explode(":",$data_item[1]);
			$IK401_1_Element_Position_In_Segment=$IK401[0];
			$IK401_2_Component_Data_Element_Position_In_Composite=$IK401[1];
			$IK401_3_Repeating_Data_Element_Position=$IK401[2];
			$IK403_Implementation_Data_Element_Syntax_Error_Code=$data_item[2]
			if(sizeof($data_item)>3)
			{
				$error_msg.="&nbsp;<b>".$data_item[0]." error. Position:".$data_item[2].". Loop:".$data_item[3].". Bad Data:".$data_item[4]."</b><br />";
				$errorCount++;
			}
		}
		break;
		
		case "IK5":		
		{		
			if(sizeof($data_item)==2)
			{	$error_msg.="&nbsp; Status:".$data_item[1]."<br />";}
			else if(sizeof($data_item)>2)
			{
				$error_msg.="&nbsp;<b>".$data_item[0]." error. Status:".$data_item[1].". [".$data_item[2]."]</b><br />";
				$errorCount++;
			}
		}
		break;
		
		case "SE*":		// TRANSACTION SET TRAILER
			$ST01_Number_of_Included_Segments=$data_item[1];
			$ST02_Transaction_Set_Control_Number=$data_item[2];
		break;
		
		case "GE*":		// FUNCTIONAL GROUP TRAILER
			$GE01_Number_of_Transaction_Sets_Includede=$data_item[1];
			$GE02_Group_Control_Number=$data_item[2];
		break;
		
		case "IEA":		//INTERCHANGE CONTROL TRAILER
			$IEA01_Number_of_Included_Functional_Groups=$data_item[1];
			$IEA02_Interchange_Control_Number=$data_item[2];
		break;
	}
}

// Send error message
else if($errorCount!=0)
{
	$error_msg.="File: ".$filename."<br />".$error_msg;
	$error_msg.="<br /><small>../X12_999_Import_Generic.php</small>";

	$to="X12__999@mycompanyname.com";

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: IT Admin<mysysadmin@mycompanyname.com>' . "\r\n";

	$subject="[IMPORTANT] ".$errorCount." error messages in 999 file ".$filename;

	mail($to, $subject, $error_msg, $headers);
}
else
{
	$msg="No errors in file<br /><br />";
	$msg.="File processed:".$filename."<br />";
	$msg.=$summary_msg;
	$msg.="<br /><small>../X12_999_Import_Generic.php</small>";

	$to="X12__999@mycompanyname.com";

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From: IT Admin<mysysadmin@mycompanyname.com>' . "\r\n";

	$subject="999 response file summary (NO ERRORS) [file:".$filename."]";

	mail($to, $subject, $msg, $headers);
}

?>
