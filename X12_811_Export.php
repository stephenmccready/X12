<?php
/*
This script is intended to be used for outputting an 
	ANSI ASC X12.811 (Financial Series [FIN]) Consolidated Service Invoice/Statement (version 004010)

- A .txt file is output as 1 continuous string of characters
- You can change the linefeed switch to make the output file more readable (see PARAMETERS section below)

- If there are no errors, and the invoice count is greater than zero, a summary email is output
- If there are no errors, and the invoice count is zero, a warning email is output
- If there are errors an error email is output

For debugging, uncomment out the echo commands.

*/

/* ************************************************************************************************ */
// Iteration levels:
//	1. Store
//		2. Invoice 
//			3. Item IT1
//				4. Sub-Line Item SLN

/********** PARAMETERS **********/
// Set filename to the full path and filename of the file to be output.
$filename='C:\\EDI__Test\\X12__811.txt';
// Set linefeed to "\n" to make the output file somewhat 'human readable' by inserting a linefeed between each segment
// or set to "" for production
//$linefeed="";
$linefeed="\n";	
/********************************/

/* Message strings for logs/email notifications */
$infomsg="";
$errormsg="";

/* Counts for logs/email summaries */
$check__count=0;
$claim__count=0;
$proc__count=0;
$charges=0;
$net__amount=0;
$total__NumberofIncludedSegments=0;

$X12__811__File = fopen($filename,'w');

/* ************************************************************************************************ */
// ISA - INTERCHANGE CONTROL HEADER
//
// NOTE: All elements of the ISA are Mandatory
//
$ISA01__AuthorInfoQual="00";			
	// 00 = No Authorization Information Present (No Meaningful Information in I02)
	// 03 = Additional Data Identification
$ISA02__AuthorInformation="          ";
$ISA03__SecurityInfoQual="00";
	// 00 = No Security Information Present (No Meaningful Information in I04)
	// 03 = Password
$ISA04__SecurityInformation="          ";
$ISA05__InterchangeIDQual="ZZ";
	// 01 Duns (Dun & Bradstreet)
	// 14 Duns Plus Suffix
	// 20 Health Industry Number (HIN) CODE SOURCE 121: Health Industry Number
	// 27 Carrier Identification Number as assigned by Health Care Financing Administration (HCFA)
	// 28 Fiscal Intermediary Identification Number as assigned by Health Care Financing Administration (HCFA)
	// 29 Medicare Provider and Supplier Identification Number as assigned by Health Care Financing Administration (HCFA)
	// 30 U.S. Federal Tax Identification Number
	// 33 National Association of Insurance Commissioners Company Code (NAIC)
	// ZZ Mutually Defined
$ISA06__InterchangeSenderID="XXXXXXXXXXXXXXX";
	// ??? Your ADVANTIS Userid
$ISA07__InterchangeIDQual="ZZ";
	// 01 Duns (Dun & Bradstreet)
	// 14 Duns Plus Suffix
	// 20 Health Industry Number (HIN) CODE SOURCE 121: Health Industry Number
	// 27 Carrier Identification Number as assigned by Health Care Financing Administration (HCFA)
	// 28 Fiscal Intermediary Identification Number as assigned by Health Care Financing Administration (HCFA)
	// 29 Medicare Provider and Supplier Identification Number as assigned by Health Care Financing Administration (HCFA)
	// 30 U.S. Federal Tax Identification Number
	// 33 National Association of Insurance Commissioners Company Code (NAIC)
	// ZZ Mutually Defined
$ISA08__InterchangeReceiverID="SAFL   SAFL107";
	// ID for SAFELITE
$ISA09__InterchangeDate=date("ymd");
$ISA10__InterchangeTime=date("hm");
$ISA11__InterchangeStandardID="U";
$ISA12__InterchangeControlVersionID="00401";
$ISA13__InterchangeControlNumber="0".date("mdHi");
$ISA14__InterchangeAckRequested="0";
	// 0 No Interchange Acknowledgement Requested
	// 1 Interchange Acknowledgement Requested (TA1)
$ISA15__TestIndicator="T";				
	// P=Production, T=test
$ISA16__SubElementSeperator=">";

// echo "__ ISA\n";

fwrite($X12__811__File, "ISA*"
		.$ISA01__AuthorInfoQual."*"
		.$ISA02__AuthorInformation."*"
		.$ISA03__SecurityInfoQual."*"
		.$ISA04__SecurityInformation."*"
		.$ISA05__InterchangeIDQual."*"
		.$ISA06__InterchangeSenderID."*"
		.$ISA07__InterchangeIDQual."*"
		.$ISA08__InterchangeReceiverID."*"
		.$ISA09__InterchangeDate."*"
		.$ISA10__InterchangeTime."*"
		.$ISA11__InterchangeStandardID."*"
		.$ISA12__InterchangeControlVersionID."*"
		.$ISA13__InterchangeControlNumber."*"
		.$ISA14__InterchangeAckRequested."*"
		.$ISA15__TestIndicator."*"
		.$ISA16__SubElementSeperator
		."~".$linefeed);


/* ************************************************************************************************ */
// GS - FUNCTIONAL GROUP HEADER
//
// NOTE: All elements of the GS are Mandatory
//

$GS01__FunctionalIdentifierCode="CI";
	// CI = Consolidated Service Invoice/Statement (811)
$GS02__ApplicationSendersCode="SGC3PINV";
$GS03__ApplicationReceiversCode="SAFL SAFL107";
$GS04__GroupDate=date("Ymd");
$GS05__GroupTime=date("hm");
$GS06__GroupControlNumber="123456789";
	// ???
$GS07__ResponsibleAgencyCode="X";
	// X = Accredited Standards Committee X12
$GS08__VersionRelease="004010";
	// 005010X221A1 Standards Approved for Publication by ASC X12 Procedures Review Board through October 2003

// echo "__ GS\n";

fwrite($X12__811__File, "GS*".
		$GS01__FunctionalIdentifierCode."*".
		$GS02__ApplicationSendersCode."*".
		$GS03__ApplicationReceiversCode."*".
		$GS04__GroupDate."*".
		$GS05__GroupTime."*".
		$GS06__GroupControlNumber."*".
		$GS07__ResponsibleAgencyCode."*".
		$GS08__VersionRelease.
		"~".$linefeed);

		
/* ************************************************************************************************ */
// ST - TRANSACTION SET HEADER
$ST01__TransactionSetId="811";
$ST02__TransactionSetControlNumber="0001000001";

// echo "__ ST\n";

fwrite($X12__811__File, "ST*".
		$ST01__TransactionSetId."*".
		$ST02__TransactionSetControlNumber.
		"~".$linefeed);

$TransactionSegmentCount=1;


/* ************************************************************************************************ */
// BIG Beginning Segment for Invoice
$BIG01__InvoiceDate="20".$dom->getElement('InvoiceDate');
$BIG02__BatchNumber=$dom->getElement('InvoiceNumber');

// echo "__ BIG\n";

fwrite($X12__811__File, "BIG*".
		$BIG01__InvoiceDate."*".
		$BIG02__BatchNumber.
		"~".$linefeed);

$TransactionSegmentCount=1;


/* ************************************************************************************************ */
// N1 - SUBMITTTER
$N101__EntityIdentifierCode="41";
	// Mandatory
	// 41 Submitter
$N102__Name=$dom->getElement('OriginationCompanyID');
	// ???
	// Mandatory

// echo "N1 - 41\n";

fwrite($X12__811__File, "N1*".
			$N101__EntityIdentifierCode."*".
			$N102__Name.
			"~".$linefeed);
$TransactionSegmentCount++;


/* ************************************************************************************************ */
// N1 - RECEIVER
$N101__EntityIdentifierCode="40";
	// Mandatory
	// 40 Receiver
$N102__Name=="SAFELITE";
	// Mandatory

// echo "N1 - 40\n";

fwrite($X12__811__File, "N1*".
			$N101__EntityIdentifierCode."*".
			$N102__Name.
			"~".$linefeed);
$TransactionSegmentCount++;


/* ************************************************************************************************ */
// HL - 1 Service Provider
$HL01__HeirarchicalNumber="1";
$HL02__HeirarchicalParentIDNumber="";
$HL03__HeirarchicalLevelCode="1";
	// Service Provider

// echo "HL - 1\n";

fwrite($X12__811__File, "HL*".
			$HL01__HeirarchicalNumber."*".
			$HL02__HeirarchicalParentIDNumber."*".
			$HL03__HeirarchicalLevelCode.
			"~".$linefeed);
$TransactionSegmentCount++;


/* ************************************************************************************************ */
// NM1 - Service Provider
$NM101__EntityIdentifierCode="SJ";
	// Mandatory
	// SJ - Service Provider
$NM102__EntityTypeQualifier="2";
	// Mandatory
	// 1 Person
	// 2 Non-Person Entity
$NM103__NameLast=$dom->getElement('OriginationPID');
	// ???
	// Optional

// echo "NM1*SJ\n";

fwrite($X12__811__File, "NM1*".
			$NM101__EntityIdentifierCode."*".
			$NM102__EntityTypeQualifier."*".
			$NM103__NameLast.
			"~".$linefeed);
$TransactionSegmentCount++;


/* ************************************************************************************************ */
// REF - BAI - Business Identification Number 
$REF01__ReferenceNumberQualifier="BAI";
$REF02__ReferenceNumber=$dom->getElement('VendorIDCode');
	// '09nnnn' Parent Number as assigned by Safelite
	// ???
	
// echo "REF*BAI\n";

fwrite($X12__811__File, "REF*".
		$REF01__ReferenceNumberQualifier."*".
		$REF02__ReferenceNumber.
		"~".$linefeed);
$TransactionSegmentCount++;


/* ************************************************************************************************ */
// HL - 2 Billing Arrangement
$HL01__HeirarchicalNumber="2";
$HL02__HeirarchicalParentIDNumber="1";
$HL03__HeirarchicalLevelCode="2";
	// Billing Arrangement

// echo "HL - 2\n";

fwrite($X12__811__File, "HL*".
			$HL01__HeirarchicalNumber."*".
			$HL02__HeirarchicalParentIDNumber."*".
			$HL03__HeirarchicalLevelCode.
			"~".$linefeed);
$TransactionSegmentCount++;


/* ************************************************************************************************ */
// NM1 - Billing Arrangement
$NM101__EntityIdentifierCode="RI";
	// Mandatory
	// RI - Remit To
$NM102__EntityTypeQualifier="2";
	// Mandatory
	// 1 Person
	// 2 Non-Person Entity
$NM103__NameLast=$dom->getElement('???');
	// ???
	// Optional
$NM104__NameFirst="";
	// Optional
$NM105__NameMiddle="";
	// Optional
$NM106__NamePrefix="";
	// Not used by Safelite
$NM107__NameSuffix="";
	// Optional
$NM108__IDCodeQualifier="FI";
	// Mandatory
	// FI Federal Taxpayer’s Identification Number
$NM109__IDCode=$dom->getElement('???');
	// ???
	// Mandatory
	
// echo "NM1*RI\n";

fwrite($X12__811__File, "NM1*".
			$NM101__EntityIdentifierCode."*".
			$NM102__EntityTypeQualifier."*".
			$NM103__NameLast."*".
			$NM104__NameFirst."*".
			$NM105__NameMiddle."*".
			$NM106__NamePrefix."*".
			$NM107__NameSuffix."*".
			$NM108__IDCodeQualifier."*".
			$NM109__IDCode.
			"~".$linefeed);
$TransactionSegmentCount++;


$GE01__NumberofTransactionSetsIncluded=0;
$TransactionSetControlNumber=0;

/* ************************************************************************************************ */
// Iterate thru the entire dataset
while($dataset!="EOF")	//???
{

	/* ************************************************************************************************ */
	// HL - 3 Provider Information
	$HL01__HeirarchicalNumber="3";
	$HL02__HeirarchicalParentIDNumber="2";
	$HL03__HeirarchicalLevelCode="19";
		// Provider Information

	// echo "HL - 2\n";

	fwrite($X12__811__File, "HL*".
				$HL01__HeirarchicalNumber."*".
				$HL02__HeirarchicalParentIDNumber."*".
				$HL03__HeirarchicalLevelCode.
				"~".$linefeed);
	$TransactionSegmentCount++;

	
	/* ************************************************************************************************ */
	// NM1 - Store Name
	$NM101__EntityIdentifierCode="SN";
		// Mandatory
		// SN - Store Name
	$NM102__EntityTypeQualifier="2";
		// Mandatory
		// 1 Person
		// 2 Non-Person Entity
	$NM103__NameLast=$dom->getElement('RetailerName');
		// ???
		// Optional
	$NM104__NameFirst="";
		// Optional
	$NM105__NameMiddle="";
		// Optional
	$NM106__NamePrefix="";
		// Not used by Safelite
	$NM107__NameSuffix="";
		// Optional
	$NM108__IDCodeQualifier="FA";
		// Mandatory
		// FA Facility Identification
	$NM109__IDCode=$dom->getElement('ShopTaxNumber');
		// ???
		// Mandatory
		
	// echo "NM1*RI\n";

	fwrite($X12__811__File, "NM1*".
				$NM101__EntityIdentifierCode."*".
				$NM102__EntityTypeQualifier."*".
				$NM103__NameLast."*".
				$NM104__NameFirst."*".
				$NM105__NameMiddle."*".
				$NM106__NamePrefix."*".
				$NM107__NameSuffix."*".
				$NM108__IDCodeQualifier."*".
				$NM109__IDCode.
				"~".$linefeed);
	$TransactionSegmentCount++;
	
	
	/* ************************************************************************************************ */
	// N2 - Store Number
	// ???
	if($dom->getElement('StoreNumber') != "")
	{
		$N201__Name=$dom->getElement('StoreNumber');
		// echo "N2\n";
		fwrite($X12__811__File, "N2*".
				$N201__Name.
				"~".$linefeed);
		$TransactionSegmentCount++;
	}
	
	
	/* ************************************************************************************************ */
	// N3 - Address
	$N302__AddressLine1=$dom->getElement('RetailerAddress');
	$N302__AddressLine2="";

	// echo "N3\n";
	
	if($N302__AddressLine2=="")
	{	fwrite($X12__811__File, "N3*".$N301__AddressLine1."~".$linefeed);	}
	else
	{	fwrite($X12__811__File, "N3*".$N301__AddressLine1."*".$N302__AddressLine2."~".$linefeed);	}
	$TransactionSegmentCount++;

	
	/* ************************************************************************************************ */
	// N4 - Geographic Location
	$N401__city=$dom->getElement('RetailerCity');
		// Mandatory
	$N402__state=$dom->getElement('RetailerState');
		// Mandatory
	$N403__zip=$dom->getElement('RetailerZip');
		// Mandatory

	// echo "N4\n";

	fwrite($X12__811__File, "N4*".
				$N401__city."*".
				$N402__state."*".
				$N403__zip.
				"~".$linefeed);
	$TransactionSegmentCount++;	
	
	
	/* ************************************************************************************************ */
	// PER - Contact Name
	$PER01__ContactFunctionCode="CN";
		// Mandatory
		// CN Contact Name
	$PER02__Name=$dom->getElement('RetailerContact');
		// Optional
	$PER03__CommunicationNumberQualifier="TE";
		// Optional
		// EM Electronic Mail
		// FX Facsimile
		// TE Telephone
	$PER04__CommunicationNumber=$dom->getElement('RetailerTelephone');
		// Optional

	// echo "PER*CN\n";

	fwrite($X12__811__File, "PER*".
				$PER01__ContactFunctionCode."*".
				$PER02__Name."*".
				$PER03__CommunicationNumberQualifier."*".
				$PER04__CommunicationNumber.
				"~".$linefeed);
	$TransactionSegmentCount++;
	
	
	
	/* ************************************************************************************************ */
	// Iterate Invoices
	while($dataset!="EOF" && $invoice != "EOInvoice")	//???
	{
	
		/* ************************************************************************************************ */
		// HL - 4
		$HL01__HeirarchicalNumber="4";
		$HL02__HeirarchicalParentIDNumber="3";
		$HL03__HeirarchicalLevelCode="3";
			// Insured and Vehicle Information 

		// echo "HL - 4\n";

		fwrite($X12__811__File, "HL*".
					$HL01__HeirarchicalNumber."*".
					$HL02__HeirarchicalParentIDNumber."*".
					$HL03__HeirarchicalLevelCode.
					"~".$linefeed);
		$TransactionSegmentCount++;	
	
	
		/* ************************************************************************************************ */
		// LX - Assigned Number
		$LX01__AssignedNumber=1;
			// Mandatory

		// echo "LX\n";

		fwrite($X12__811__File, "LX*".
					$LX01__AssignedNumber.
					"~".$linefeed);
		$TransactionSegmentCount++;
	
	
		/* ************************************************************************************************ */
		// VEH - Vehicle Information
		$VEH01__AssignedNumber="";
			// Optional
		$VEH02__VIN=$dom->getElement('VehicleIDNumber');
			// ???
			// Optional
		$VEH03__Year=$dom->getElement('VehicleYear');
			// ???
			// Optional
		$VEH04__AgencyIDCode="NG";
			// NG = NAGS database
			// Mandatory
		$VEH05__Make=$dom->getElement('VehicleMake');
			// ???
			// Mandatory - Maximum 12 Characters
		$VEH06__Model=$dom->getElement('VehicleModel');
			// ???
			// Mandatory - Maximum 12 Characters
		$VEH07__Style=$dom->getElement('VehicleStyle');
			// ???
			// Mandatory - Maximum 12 Characters

		// echo "VEH\n";

		fwrite($X12__811__File, "VEH*".
					$VEH01__AssignedNumber."*".
					$VEH02__VIN."*".
					$VEH03__Year."*".
					$VEH04__AgencyIDCode."*".
					$VEH05__Make."*".
					$VEH06__Model."*".
					$VEH07__Style.
					"~".$linefeed);
		$TransactionSegmentCount++;
	
	
		/* ************************************************************************************************ */
		// REF - IV - Invoice Number 
		$REF01__ReferenceNumberQualifier="IV";
		$REF02__ReferenceNumber=$dom->getElement('InvoiceNumber');
			// ???
			
		// echo "REF*IV\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;	
	
	
		/* ************************************************************************************************ */
		// REF - QY - Mobile Flag
		$REF01__ReferenceNumberQualifier="QY";
		if($dom->getElement('MobileIndicator')=="S")	// S = Store ???
		{	$REF02__ReferenceNumber="N";	}
		else
		{	$REF02__ReferenceNumber="Y";	}
			// ???
			
		// echo "REF*QY\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;
	
	
		/* ************************************************************************************************ */
		// REF - IG - Policy Number 
		$REF01__ReferenceNumberQualifier="IV";
		$REF02__ReferenceNumber=$dom->getElement('PolicyNumber');
			// ???
			
		// echo "REF*IG\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;		
	
	
		/* ************************************************************************************************ */
		// REF - D9 - Claim Number 
		$REF01__ReferenceNumberQualifier="D9";
		$REF02__ReferenceNumber=$dom->getElement('ClaimNumber');
			// ???
			
		// echo "REF*D9\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;		
	
	
		/* ************************************************************************************************ */
		// REF - PO - Safelite Referral Number 
		$REF01__ReferenceNumberQualifier="PO";
		$REF02__ReferenceNumber=$dom->getElement('ReferralNumber');
			// ???
			
		// echo "REF*PO\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;		
	
	
		/* ************************************************************************************************ */
		// REF - ZZ - Signed Copy of Invoice on File
		$REF01__ReferenceNumberQualifier="ZZ";
		if($dom->getElement('SignedInvoiceOnFile')=="S")
		{	$REF02__ReferenceNumber="Y";	}
		else
		{	$REF02__ReferenceNumber="N";	}
			// ???
			
		// echo "REF*ZZ\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;	
		
	
		/* ************************************************************************************************ */
		// REF - 6O - GlassMate Car ID Number 
		$REF01__ReferenceNumberQualifier="6O";
		$REF02__ReferenceNumber=$dom->getElement('CarIDNumber');
			// ???
			
		// echo "REF*6O\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;	
	
	
		/* ************************************************************************************************ */
		// AMT - Invoice Amount
		$AMT01__AmountQualCode="BD";		
		$AMT02__MonetaryAmount=number_format($dom->getElement('InvoiceNetAmount'),2,'.','');

		// echo "AMT*BD\n";

		fwrite($X12__811__File, "AMT*".
				$AMT01__AmountQualCode."*".
				$AMT02__MonetaryAmount.
				"~".$linefeed);
		$TransactionSegmentCount++;

		
		/* ************************************************************************************************ */
		// DTM - Invoice DATE
		$DTM01__DateTimeQualifier="003";
			// Mandatory
			// 003 Invoice Date
		$DTM02__Date="20".$dom->getElement('InvoiceDate');
			// Mandatory

		// echo "DTM*003\n";

		fwrite($X12__811__File, "DTM*".
					$DTM01__DateTimeQualifier."*".
					$DTM02__Date.
					"~".$linefeed);
		$TransactionSegmentCount++;	

		
		/* ************************************************************************************************ */
		// DTM - Install/Completion DATE
		$DTM01__DateTimeQualifier="198";
			// Mandatory
			// 198 Install/Completion Date
		$DTM02__Date="20".$dom->getElement('InstallationDate');
			// Mandatory

		// echo "DTM*198\n";

		fwrite($X12__811__File, "DTM*".
					$DTM01__DateTimeQualifier."*".
					$DTM02__Date.
					"~".$linefeed);
		$TransactionSegmentCount++;			

		
		/* ************************************************************************************************ */
		// DTM - Referral/Assigned DATE
		$DTM01__DateTimeQualifier="141";
			// Mandatory
			// 141 Referral/Assigned Date
		$DTM02__Date="20".$dom->getElement('ReferralDate');
			// ???
			// Mandatory

		// echo "DTM*141\n";

		fwrite($X12__811__File, "DTM*".
					$DTM01__DateTimeQualifier."*".
					$DTM02__Date.
					"~".$linefeed);
		$TransactionSegmentCount++;			

		
		/* ************************************************************************************************ */
		// DTM - Referral/Assigned DATE
		$DTM01__DateTimeQualifier="142";
			// Mandatory
			// 142 Date Of Loss
		$DTM02__Date="20".$dom->getElement('DateOfLoss');
			// Mandatory

		// echo "DTM*142\n";

		fwrite($X12__811__File, "DTM*".
					$DTM01__DateTimeQualifier."*".
					$DTM02__Date.
					"~".$linefeed);
		$TransactionSegmentCount++;			
		
		
		/* ************************************************************************************************ */
		// TXI - Tax Information
		$TXI01__TaxTypeCode="TX";
			// Mandatory
			// TX - All Taxes
		$TXI02__Amount=number_format($dom->getElement('TaxAmount'),2,'.','');
			// Mandatory

		// echo "TXI\n";

		fwrite($X12__811__File, "TXI*".
					$TXI01__TaxTypeCode."*".
					$TXI02__Amount.
					"~".$linefeed);
		$TransactionSegmentCount++;		

		
		/* ************************************************************************************************ */
		// NM1 - Insured's Name
		$NM101__EntityIdentifierCode="IL";
			// Mandatory
			// IL - Insured's name
		$NM102__EntityTypeQualifier="1";
			// Mandatory
			// 1 Person
			// 2 Non-Person Entity
		$NM103__NameLast=$dom->getElement('InsuredOrClaimantLastName');
			// ???
			// Mandatory
		$NM104__NameFirst=$dom->getElement('InsuredOrClaimantFirstName');
			// ???
			// Optional
			
		// echo "NM1*IL\n";

		fwrite($X12__811__File, "NM1*".
					$NM101__EntityIdentifierCode."*".
					$NM102__EntityTypeQualifier."*".
					$NM103__NameLast."*".
					$NM104__NameFirst.
					"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// N3 - Address
		$N302__AddressLine1=$dom->getElement('InsuredAddress1');
		$N302__AddressLine2=$dom->getElement('InsuredAddress2');

		// echo "N3\n";
		
		if($N302__AddressLine2=="")
		{	fwrite($X12__811__File, "N3*".$N301__AddressLine1."~".$linefeed);	}
		else
		{	fwrite($X12__811__File, "N3*".$N301__AddressLine1."*".$N302__AddressLine2."~".$linefeed);	}
		$TransactionSegmentCount++;

		
		/* ************************************************************************************************ */
		// N4 - Geographic Location
		$N401__city=$dom->getElement('InsuredCity');
			// Mandatory
		$N402__state=$dom->getElement('InsuredState');
			// Mandatory
		$N403__zip=$dom->getElement('InsuredZip');
			// Mandatory

		// echo "N4\n";

		fwrite($X12__811__File, "N4*".
					$N401__city."*".
					$N402__state."*".
					$N403__zip.
					"~".$linefeed);
		$TransactionSegmentCount++;	
		
		
		/* ************************************************************************************************ */
		// PER - Contact Name
		$PER01__ContactFunctionCode="CN";
			// Mandatory
			// CN Contact Name
		$PER02__Name="";
			// Optional
		$PER03__CommunicationNumberQualifier="HP";
			// Mandatory
			// HP Home Phone
		$PER04__CommunicationNumber=$dom->getElement('InsuredOrClaimantTelephone');
			// Mandatory

		// echo "PER*CN\n";

		fwrite($X12__811__File, "PER*".
					$PER01__ContactFunctionCode."*".
					$PER02__Name."*".
					$PER03__CommunicationNumberQualifier."*".
					$PER04__CommunicationNumber.
					"~".$linefeed);
		$TransactionSegmentCount++;

		
		/* ************************************************************************************************ */
		// ITA - Allowance, Charge or Service
		$ITA01__AllowanceOrCharge="A";
			// Mandatory
			// A = Allowance
			// C = Charge
			// S = Service
		$ITA02__AgencyQualCode="";
			// Not used by Safelite
		$ITA03__SpecialServiceCode="";
			// Not used by Safelite
		$ITA04__MethodOfHandling="06";
			// Mandatory
			// 06 - Paid by customer or insured 
			// (amount to be collected by glass store)
		$ITA05__AllowanceOrCharge="";
			// Not used by Safelite
		$ITA06__AllowanceOrCharge="";
			// Not used by Safelite
		$ITA07__AllowanceOrChargeTotal=number_format($dom->getElement('DeductibleAmount'),2,'','');
			// Optional
		$ITA08__AllowanceOrChargePercentQual="";
			// Not used by Safelite
		$ITA09__AllowanceOrChargePercent="";
			// Not used by Safelite
		$ITA10__AllowanceOrChargeQuantity="";
			// Not used by Safelite
		$ITA11__UnitBasisMeasure="";
			// Not used by Safelite
		$ITA12__Quantity="";
			// Not used by Safelite
		$ITA13__Description="";
			// Not used by Safelite
		$ITA14__SpecialChargeCode="DED";
			// Mandatory
			// DED = Deductible

		// echo "ITA*A\n";

		fwrite($X12__811__File, "PER*".
					$ITA01__AllowanceOrCharge."*".
					$ITA02__AgencyQualCode."*".
					$ITA03__SpecialServiceCode."*".
					$ITA04__MethodOfHandling."*".
					$ITA05__AllowanceOrCharge."*".
					$ITA06__AllowanceOrCharge."*".
					$ITA07__AllowanceOrChargeTotal."*".
					$ITA08__AllowanceOrChargePercentQual."*".
					$ITA09__AllowanceOrChargePercent."*".
					$ITA10__AllowanceOrChargeQuantity."*".
					$ITA11__UnitBasisMeasure."*".
					$ITA12__Quantity."*".
					$ITA13__Description."*".
					$ITA14__SpecialChargeCode.					
					"~".$linefeed);
		$TransactionSegmentCount++;

	
		/* ************************************************************************************************ */
		// HL - 5
		$HL01__HeirarchicalNumber="5";
		$HL02__HeirarchicalParentIDNumber="4";
		$HL03__HeirarchicalLevelCode="V";
			// Address Level

		// echo "HL - 5\n";

		fwrite($X12__811__File, "HL*".
					$HL01__HeirarchicalNumber."*".
					$HL02__HeirarchicalParentIDNumber."*".
					$HL03__HeirarchicalLevelCode.
					"~".$linefeed);
		$TransactionSegmentCount++;	


		/* ************************************************************************************************ */
		// NM1 - Insurer Name
		$NM101__EntityIdentifierCode="IN";
			// Mandatory
			// IN - Insurer's name
		$NM102__EntityTypeQualifier="2";
			// Mandatory
			// 1 Person
			// 2 Non-Person Entity
		$NM103__NameLast=$dom->getElement('InsurerName');
			// ???
			// Mandatory
			
		// echo "NM1*IN\n";

		fwrite($X12__811__File, "NM1*".
					$NM101__EntityIdentifierCode."*".
					$NM102__EntityTypeQualifier."*".
					$NM103__NameLast.
					"~".$linefeed);
		$TransactionSegmentCount++;


		/* ************************************************************************************************ */
		// REF - PID - Program ID Number
		//             This element contains the Program ID Number assigned to this insurance company for 
		//			   this invoice; will be the value that is sent on the 272 or Fax.
		$REF01__ReferenceNumberQualifier="PID";
		$REF02__ReferenceNumber=$dom->getElement('ProgramIDNumber');
			// ???
			
		// echo "REF*PID\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;		
	
	
		/* ************************************************************************************************ */
		// REF - 5E - Consumer Identification
		//            This element will contain ‘Y’ when a premium part is used and proof of purchase 
		//            documents are available at the installing glass shop.
	
		$REF01__ReferenceNumberQualifier="5E";
		$REF02__ReferenceNumber=$dom->getElement('PremiumPartIndicator');
			// ???
			
		// echo "REF*5E\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;	
		
	
		/* ************************************************************************************************ */
		// REF - MR - Merchandise Type Code
		//  		  This element will contain ‘Y’ when a OEM part is used and proof of purchase documents 
		//			  are available at the installing glass shop.
		$REF01__ReferenceNumberQualifier="MR";
		$REF02__ReferenceNumber=$dom->getElement('OEMPartUsedIndicator');
			// ???
			
		// echo "REF*MR\n";

		fwrite($X12__811__File, "REF*".
				$REF01__ReferenceNumberQualifier."*".
				$REF02__ReferenceNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;

		$HL01__HeirarchicalNumber=5;
		$HL02__HeirarchicalParentIDNumber=4;
		$IT101__AssignedID=0;
	
		/* ************************************************************************************************ */
		// Iterate Invoices Items
		while($dataset!="EOF" && $invoice != "EOInvoice")	//???
		{

			/* ************************************************************************************************ */
			// HL
			$HL01__HeirarchicalNumber++;
			$HL02__HeirarchicalParentIDNumber++;
			$HL03__HeirarchicalLevelCode="4";
				// Baseline items charged per vehicle 

			// echo "HL - ".$HL01__HeirarchicalNumber."\n";

			fwrite($X12__811__File, "HL*".
						$HL01__HeirarchicalNumber."*".
						$HL02__HeirarchicalParentIDNumber."*".
						$HL03__HeirarchicalLevelCode.
						"~".$linefeed);
			$TransactionSegmentCount++;	
		
                
			/* ************************************************************************************************ */
			// IT1 - Baseline Item Data
			$IT101__AssignedID++;
				// Optional
			$IT102__QuantityInvoiced=$dom->getElement('Quantity');
				// Quantity being invoiced for the baseline part
				// Do not use a value > 1 except on Kits as per Appendix A
			$IT103__UnitBasisMeasureCode=$dom->getElement('UnitOfMeasure');
				// 
			$IT104__UnitPrice=$dom->getElement('ListPrice');
				// Selling price
			$IT105__BasisUnitPriceCode="";
				// Not Used
			$IT106__ProdServIDQual="TP";
				// TP = Product Type Code
			$IT107__ProdServiceID=$dom->getElement('RepairProductCode');
				// Repair Product Code
				/*
						AD -		Adhesive Kit
						GA -		Gasket
						GL -		Glass
						HW -		Hardware (Clips, screws, etc.)
						IM -		Installation Materials
						LO -		Labor Only
						MO -		Moldings
						OT -		Other
						RP -		Repair
						TI -		Tinting
						WE -		Weatherstrip
				*/
			$IT108__ProdServIDQual="PN";
				// PN = Part Number
			$IT109__ProdServiceID=$dom->getElement('PartNumber');
				// This element contains the Part Number for the Baseline Part.  
				// See Appendix A for Adhesive Kit part number values.  
				// See Appendix B for Part Number requirements.		
			$IT110__ProdServIDQual="PD";
				// PD = Part Description
			$IT111__ProdServiceID=$dom->getElement('PartDescription');
				// Part Description Text
			$IT112__ProdServIDQual="";
				// 
			$IT113__ProdServiceID="";
				// 
			$IT114__ProdServIDQual="CL";
				// CL = Color
			$IT115__ProdServiceID=$dom->getElement('GlassColor');
				// Color of glass
			$IT116__ProdServIDQual="";
				// 
			$IT117__ProdServiceID="";
				// 
			$IT118__ProdServIDQual="";
				// 
			$IT119__ProdServiceID="";
				// 
			$IT120__ProdServIDQual="OE";
				// OE = If an OEM baseline part was used
			$IT121__ProdServiceID=$dom->getElement('OEMPartNumber');
				// Manufacturers OEM number if an OEM baseline part was used

			// echo "IT1*A\n";

			fwrite($X12__811__File, "PER*".
						$IT101__AssignedID."*".
						$IT102__QuantityInvoiced."*".
						$IT103__UnitBasisMeasureCode."*".
						$IT104__UnitPrice."*".
						$IT105__BasisUnitPriceCode."*".
						$IT106__ProdServIDQual."*".
						$IT107__ProdServiceID."*".
						$IT108__ProdServIDQual."*".
						$IT109__ProdServiceID."*".
						$IT110__ProdServIDQual."*".
						$IT111__ProdServiceID."*".
						$IT112__ProdServIDQual."*".
						$IT113__ProdServiceID."*".
						$IT114__ProdServIDQual."*".
						$IT115__ProdServiceID."*".
						$IT116__ProdServIDQual."*".
						$IT117__ProdServiceID."*".
						$IT118__ProdServIDQual."*".
						$IT119__ProdServiceID."*".
						$IT120__ProdServIDQual."*".
						$IT121__ProdServiceID.
						"~".$linefeed);
			$TransactionSegmentCount++;	
	
	
			/* ************************************************************************************************ */
			// AMT - List Price
			$AMT01__AmountQualCode="LP";
				// LP = List Price
			$AMT02__MonetaryAmount=number_format($dom->getElement('ListPrice'),2,'.','');

			// echo "AMT*LP\n";

			fwrite($X12__811__File, "AMT*".
					$AMT01__AmountQualCode."*".
					$AMT02__MonetaryAmount.
					"~".$linefeed);
			$TransactionSegmentCount++;	

                        
                        $SLN01__AssignedID=0;

			/* ************************************************************************************************ */
			// Iterate Sub-Line Items
			while($dataset!="EOF" && $invoice != "EOInvoice" && $invoiceItem != "EOInvoiceItem")	//???
			{

				/* ************************************************************************************************ */
				// HL
				$HL01__HeirarchicalNumber++;
				$HL02__HeirarchicalParentIDNumber++;
				$HL03__HeirarchicalLevelCode="8";
					// Baseline items charged per vehicle 

				// echo "HL - ".$HL01__HeirarchicalNumber."\n";

				fwrite($X12__811__File, "HL*".
							$HL01__HeirarchicalNumber."*".
							$HL02__HeirarchicalParentIDNumber."*".
							$HL03__HeirarchicalLevelCode.
							"~".$linefeed);
				$TransactionSegmentCount++;		
	
        
                                /* ************************************************************************************************ */
                                // SLN - Sub-Line Item Data
                                $SLN01__AssignedID++;
                                        // Optional
                                $SLN02__AssignedID="1";
                                        // Optional
                                $SLN03__PriceRebateCode="A";
                                        // A=Add
                                $SLN04__QuantityInvoiced="1";
                                        // This element contains the quantity being invoiced for the sub-line part.
                                        // Do not use value greater than 1 unless part is a kit with the AD product code.
                                $SLN05__CompositeUnitMeasureCode="1";
                                        // Unit of Measure
                                $SLN06__UnitPrice=$dom->getElement('RepairProductCode');
                                        // This element contains the NET selling price for the sub-line item being invoiced;
                                        // qty * single unit price.
                                        
                                        
                                        
                                        Stopped here...
                                        
                                        
                                        
                                $SLN07__ProdServiceID=$dom->getElement('RepairProductCode');
                                        // Repair Product Code
                                        /*
                                                        AD -		Adhesive Kit
                                                        GA -		Gasket
                                                        GL -		Glass
                                                        HW -		Hardware (Clips, screws, etc.)
                                                        IM -		Installation Materials
                                                        LO -		Labor Only
                                                        MO -		Moldings
                                                        OT -		Other
                                                        RP -		Repair
                                                        TI -		Tinting
                                                        WE -		Weatherstrip
                                        */
                                $SLN08__ProdServIDQual="PN";
                                        // PN = Part Number
                                $SLN09__ProdServiceID=$dom->getElement('PartNumber');
                                        // This element contains the Part Number for the Baseline Part.  
                                        // See Appendix A for Adhesive Kit part number values.  
                                        // See Appendix B for Part Number requirements.		
                                $SLN10__ProdServIDQual="PD";
                                        // PD = Part Description
                                $SLN11__ProdServiceID=$dom->getElement('PartDescription');
                                        // Part Description Text
                                $SLN12__ProdServIDQual="";
                                        // 
                                $SLN13__ProdServiceID="";
                                        // 
                                $SLN14__ProdServIDQual="CL";
                                        // CL = Color
                                $SLN15__ProdServiceID=$dom->getElement('GlassColor');
                                        // Color of glass
                                $SLN16__ProdServIDQual="";
                                        // 
                                $SLN17__ProdServiceID="";
                                        // 
                                $SLN18__ProdServIDQual="";
                                        // 
                                $SLN19__ProdServiceID="";
                                        // 
                                $SLN20__ProdServIDQual="OE";
                                        // OE = If an OEM baseline part was used
                                $SLN21__ProdServiceID=$dom->getElement('OEMPartNumber');
                                        // Manufacturers OEM number if an OEM baseline part was used
        
                                // echo "SLN*A\n";
        
                                fwrite($X12__811__File, "PER*".
                                                        $SLN01__AssignedID."*".
                                                        $SLN02__QuantityInvoiced."*".
                                                        $SLN03__UnitBasisMeasureCode."*".
                                                        $SLN04__UnitPrice."*".
                                                        $SLN05__BasisUnitPriceCode."*".
                                                        $SLN06__ProdServIDQual."*".
                                                        $SLN07__ProdServiceID."*".
                                                        $SLN08__ProdServIDQual."*".
                                                        $SLN09__ProdServiceID."*".
                                                        $SLN10__ProdServIDQual."*".
                                                        $SLN11__ProdServiceID."*".
                                                        $SLN12__ProdServIDQual."*".
                                                        $SLN13__ProdServiceID."*".
                                                        $SLN14__ProdServIDQual."*".
                                                        $SLN15__ProdServiceID."*".
                                                        $SLN16__ProdServIDQual."*".
                                                        $SLN17__ProdServiceID."*".
                                                        $SLN18__ProdServIDQual."*".
                                                        $SLN19__ProdServiceID."*".
                                                        $SLN20__ProdServIDQual."*".
                                                        $SLN21__ProdServiceID.
                                                        "~".$linefeed);
                                $TransactionSegmentCount++;
	
	
	
	
                        // End of Sub-Line Invoice Item
                        $invoice__sub_item_count++;
                        }
                    
                    
                    
		// End of Invoice Item
		$invoice__item_count++;
		}
		
		
		
		
		
		
		
		
		
	// End of Invoice
	$invoice__count++;
	$invoice__item_count=0;	
	}
	
// End of DataSet
}


/* ************************************************************************************************ */
// SE - TRANSACTION SET TRAILER
$TransactionSegmentCount++;
$SE01__NumberofIncludedSegments=$TransactionSegmentCount;
	// Mandatory
$SE02__TransactionSetControlNumber=$ST02__TransactionSetControlNumber;
	// Mandatory
$GE01__NumberofTransactionSetsIncluded++;
$total__NumberofIncludedSegments+=$TransactionSegmentCount;
// echo "__ SE\n";

fwrite($X12__811__File, "SE*".
					$SE01__NumberofIncludedSegments."*".
					$SE02__TransactionSetControlNumber.
					"~".$linefeed);

					
/* ************************************************************************************************ */
// GE - TRANSACTION SET TRAILER
//
// NOTE: All elements of the GE are Mandatory
//

// Note: $GE01__NumberofTransactionSetsIncluded is maintained throughout the script
$GE02__GroupControlNumber=$GS06__GroupControlNumber;

// echo "__ GE\n";

fwrite($X12__811__File, "GE*".
						$GE01__NumberofTransactionSetsIncluded."*".
						$GE02__GroupControlNumber.
						"~".$linefeed);

						
/* ************************************************************************************************ */
// IEA - INTERCHANGE CONTROL TRAILER
//
// NOTE: All elements of the IEA are Mandatory
//

$IEA01__NumberofIncludedFunctionalGroups=1;
$IEA02__InterchangeControlNumber=$ISA13__InterchangeControlNumber;

// echo "__ IEA\n";

fwrite($X12__811__File, "IEA*".
						$IEA01__NumberofIncludedFunctionalGroups."*".
						$IEA02__InterchangeControlNumber.
						"~".$linefeed);

/* ************************************************************************************************ */

odbc_close($connection_string);

fclose($X12__811__File);




if($errormsg!="")
{
	$to="X12__811__ERRORS@mycompanyname.com";

	$subject="[Important] - X12 811 Export Errors";

	$errormsg.="You can append to the body of your error message here";

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	$from="mysysadmin@mycompanyname.com";

	mail($to, $subject, $errormsg, $headers, $from);
}
else
{
	if($invoice__count>0)
	{
		$msg="Summary <small>(File: ".$filename.")</small><br /><br />";
		$msg.="<table><tr><td>Invoices&nbsp;</td><td style=\"text-align:right;\">".number_format($invoice__count)."</td></tr>";
		$msg.="<tr><td>Claims</td><td style=\"text-align:right;\">".number_format($claim__count)."</td></tr>";
		$msg.="<tr><td>Procedures</td><td style=\"text-align:right;\">".number_format($proc__count)."</td></tr>";
		$msg.="<tr><td>Charges</td><td style=\"text-align:right;\">$".number_format($charges,2)."</td></tr>";
		$msg.="<tr><td>Paid</td><td style=\"text-align:right;\">$".number_format($net__amount,2)."</td></tr></table>";
		$msg.="<br />";
		$msg.="<br /><table><tr><th>Check#</th><th></th><th>Charges</th><th>Paid</th><th>Claims</th><th>Lines</th></tr>".$infomsg."</table>";
		$msg.="<br />";
		$msg.="<br />Segments: ".number_format($total__NumberofIncludedSegments);
		$msg.="<br />XN Sets: ".number_format($GE01__NumberofTransactionSetsIncluded);
		$subject =  "X12 811 Export Summary";
	}
	else
	{	
		$msg.="<br /><b>NO INVOICES EXPORTED</b>";	
		$subject="[IMPORTANT] - 0 invoices exported (X12 811 export summary)";
	}

	$to="X12__811@mycompanyname.com";

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	$from="mysysadmin@mycompanyname.com";

	mail($to, $subject, $msg, $headers, $from);

	/*
		Include your FTP script here, if you are submitting your file automatically
	*/

//	if($invoice__count>0)
//	{	include('Transfer__X12__811__File.php');	}

}

?>
