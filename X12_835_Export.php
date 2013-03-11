<?php
/*

This script is intended to be used as a template for outputting an 
	ANSI ASC X12.835 Health Care Claim Payment/Advice transaction set ( version: 005010X221 )

	Each loop, segment and data element has been documented to a reasonable level.
	For detailed information about the X12 835 transaction set set go to www.wpc-edi.com

This is a 'plain vanilla' implementation of the standard.
- Where the data is sourced from an MS SQL database via ODBC 
  (you will need to change your dataset and field names to match your data source)
  (or you can change your data source field names to match this)
- It is based on getting an input dataset containing the following fields:

	check__number
	check__date
	vendor__name
	vendor__NPI
	vendor__address1
	vendor__address2
	vendor__city
	vendor__state
	vendor__zip
	claim__id
	clm__TotalClaimChargeAmount
	clm__ClaimPaymentAmount
	clm__copay__amount
	clm__coinsurance__amount
	clm__deductible

  You will need to add more fields to your source dataset if you switch on some of the situational segments that 
  are currently switched off

- A .txt file is output as 1 continuous string of characters
- You can also change the linefeed switch to make the output file more readable (see PARAMETERS section below)

- Some optional segments have been commented out, some included. You will need to determine which segments are required
  to meet your particular business rules.
- Default values have been used throughout.
- Dummy values have been included for many other elements. You will need to replace these with the 
  specific values for your implementation.
- If there are no errors, and the claim count is greater than zero, a summary email is output
- If there are no errors, and the claim count is zero, a warning email is output
- If there are errors an error email is output


For debugging, uncomment out the echo commands.

*/

/*
NOTE:  

Include your database configuration file here if your datasource is a database.

e.g. using an ODBC connection to an MS SQL database

include('config__ODBC.php');
Global $DB;
$DB = str__replace("\"", "", $DB);
$connection_string = odbc_connect($DB,"","");

$sqlquery = "Exec usp__mysqlstoredprocedure";
$process = odbc_exec($connection_string, $sqlquery);

*/

/********** PARAMETERS **********/
// Set filename to the full path and filename of the file to be output.
$filename='C:\\EDI__Test\\X12__835.txt';
// Set linefeed to "\n" to make the output file somewhat 'human readable' by inserting a linefeed between each segment
// or set to "" for production
$linefeed="";
//$linefeed="\n";	
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

$X12__835__File = fopen($filename,'w');

/* ************************************************************************************************ */
// ISA - INTERCHANGE CONTROL HEADER
//
// NOTE: All elements of the ISA are REQUIRED
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
$ISA08__InterchangeReceiverID="XXXXXXXXXXXXXXX";
$ISA09__InterchangeDate=date("ymd");
$ISA10__InterchangeTime=date("hm");
$ISA11__RepetitionSeparator="^";
$ISA12__InterchangeControlVersionNum="00501";
$ISA13__InterchangeControlNumber="0".date("mdHi");
$ISA14__AckRequested="0";
	// 0 No Interchange Acknowledgment Requested
	// 1 Interchange Acknowledgment Requested (TA1)
$ISA15__UsageIndicator="T";				
	// P=Production, T=test
$ISA16__ComponentElementSeperator=":";

// echo "__ ISA\n";

fwrite($X12__835__File, "ISA*"
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
		.$ISA11__RepetitionSeparator."*"
		.$ISA12__InterchangeControlVersionNum."*"
		.$ISA13__InterchangeControlNumber."*"
		.$ISA14__AckRequested."*"
		.$ISA15__UsageIndicator."*"
		.$ISA16__ComponentElementSeperator
		."~".$linefeed);

/* ************************************************************************************************ */
// GS - FUNCTIONAL GROUP HEADER
//
// NOTE: All elements of the GS are REQUIRED
//

$GS01__FunctionalIdentifierCode="HP";
	// HP = Health Care Claim Payment/Advice (835)
$GS02__ApplicationSendersCode="123456789012345";
$GS03__ApplicationReceiversCode="123456789012345";
$GS04__Date=date("Ymd");
$GS05__Time=date("hms");
$GS06__GroupControlNumber="123456789";
$GS07__ResponsibleAgencyCode="X";
	// X = Accredited Standards Committee X12
$GS08__VersionRelease="005010X221A1";
	// 005010X221A1 Standards Approved for Publication by ASC X12 Procedures Review Board through October 2003

// echo "__ GS\n";

fwrite($X12__835__File, "GS*".
		$GS01__FunctionalIdentifierCode."*".
		$GS02__ApplicationSendersCode."*".
		$GS03__ApplicationReceiversCode."*".
		$GS04__Date."*".
		$GS05__Time."*".
		$GS06__GroupControlNumber."*".
		$GS07__ResponsibleAgencyCode."*".
		$GS08__VersionRelease."~".$linefeed);

/* ************************************************************************************************ */
// There are 3 iteration levels:
//	1. Check number
//		2. Claim number
//			3. Procedure code

$holdCheckNumber="";
$holdClaim__id="";
$holdClaim__procedure__id="";
$claimSeq=1;

$GE01__NumberofTransactionSetsIncluded=0;
$TransactionSetControlNumber=0;

/* ************************************************************************************************ */
// Iterate thru the entire dataset
while(odbc_fetch_row($process))
{
	// Control break for Check Number
	if($holdCheckNumber=="" || $holdCheckNumber!=odbc_result($process,"check__number"))
	{
		if($holdCheckNumber!="")
		{
			// Close out the previous transaction set

			/* ************************************************************************************************ */
			// PLB - PROVIDER ADJUSTMENT
//			$PLB01__ProviderIdentifier="";
				// Required
//			$PLB02__FiscalPeriodDate="";
				// Required
//			$PLB03__1__ADJUSTMENTIDENTIFIER="";
				// Required
				// 50 Late Charge
				// 51 Interest Penalty Charge
				// 72 Authorized Return
				// 90 Early Payment Allowance
				// AH Origination Fee
				// AM Applied to Borrower’s Account
				// AP Acceleration of Benefits
				// B2 Rebate
				// B3 Recovery Allowance
				// BD Bad Debt Adjustment
				// BN Bonus
				// C5 Temporary Allowance
				// CR Capitation Interest
				// CS Adjustment
				// CT Capitation Payment
				// CV Capital Passthru
				// CW Certified Registered Nurse Anesthetist Passthru
				// DM Direct Medical Education Passthru
				// E3 Withholding
				// FB Forwarding Balance 
				// FC Fund Allocation
				// GO Graduate Medical Education Passthru
				// HM Hemophilia Clotting Factor Supplement
				// IP Incentive Premium Payment
				// IR Internal Revenue Service Withholding
				// IS Interim Settlement
				// J1 Nonreimbursable
				// L3 Penalty
				// L6 Interest Owed
				// LE Levy
				// LS Lump Sum
				// OA Organ Acquisition Passthru
				// OB Offset for Affiliated Providers
				// PI Periodic Interim Payment
				// PL Payment Final
				// RA Retro-activity Adjustment
				// RE Return on Equity
				// SL Student Loan Repayment
				// TL Third Party Liability
				// WO Overpayment Recovery
				// WU Unspecified Recovery
//			$PLB03__2__ProviderAdjustmentIdentifier="";
				// Required
//			$PLB04__ProviderAdjustmentAmount="";
				// Situational
//			$PLB05__1__ADJUSTMENTIDENTIFIER="";
				// Situational
//			$PLB05__2__ProviderAdjustmentIdentifier="";
				// Situational
//			$PLB06__ProviderAdjustmentAmount="";
				// Situational
//			$PLB07__1__ADJUSTMENTIDENTIFIER="";
				// Situational
//			$PLB07__2__ProviderAdjustmentIdentifier="";
				// Situational
//			$PLB08__ProviderAdjustmentAmount="";
				// Situational
//			$PLB09__1__ADJUSTMENTIDENTIFIER="";
				// Situational
//			$PLB09__2__ProviderAdjustmentIdentifier="";
				// Situational
//			$PLB10__ProviderAdjustmentAmount="";
				// Situational
//			$PLB11__1__ADJUSTMENTIDENTIFIER="";
				// Situational
//			$PLB11__2__ProviderAdjustmentIdentifier="";
				// Situational
//			$PLB12__ProviderAdjustmentAmount="";
				// Situational
//			$PLB13__1__ADJUSTMENTIDENTIFIER="";
				// Situational
//			$PLB13__2__ProviderAdjustmentIdentifier="";
				// Situational
//			$PLB14__ProviderAdjustmentAmount="";
				// Situational

			// echo "__ PLB\n";

//			fwrite($X12__835__File, "PLB*".
//									$PLB01__ProviderIdentifier."*".
//									$PLB02__FiscalPeriodDate."*".
//									$PLB03__1__ADJUSTMENTIDENTIFIER.":".$PLB03__2__ProviderAdjustmentIdentifier."*".
//									$PLB04__ProviderAdjustmentAmount."*".
//									$PLB05__1__ADJUSTMENTIDENTIFIER.":".$PLB05__2__ProviderAdjustmentIdentifier."*".
//									$PLB06__ProviderAdjustmentAmount."*".
//									$PLB07__1__ADJUSTMENTIDENTIFIER.":".$PLB07__2__ProviderAdjustmentIdentifier."*".
//									$PLB08__ProviderAdjustmentAmount."*".
//									$PLB09__1__ADJUSTMENTIDENTIFIER.":".$PLB09__2__ProviderAdjustmentIdentifier."*".
//									$PLB10__ProviderAdjustmentAmount."*".
//									$PLB11__1__ADJUSTMENTIDENTIFIER.":".$PLB11__2__ProviderAdjustmentIdentifier."*".
//									$PLB12__ProviderAdjustmentAmount."*".
//									$PLB13__1__ADJUSTMENTIDENTIFIER.":".$PLB13__2__ProviderAdjustmentIdentifier."*".
//									$PLB14__ProviderAdjustmentAmount
//									."~".$linefeed);
//			$TransactionSegmentCount++;

			/* ************************************************************************************************ */
			// SE - TRANSACTION SET TRAILER
			$TransactionSegmentCount++;
			$SE01__NumberofIncludedSegments=$TransactionSegmentCount;
			$SE02__TransactionSetControlNumber=$ST02__TransactionSetControlNumber;
			$GE01__NumberofTransactionSetsIncluded++;
			$total__NumberofIncludedSegments+=$TransactionSegmentCount;

			// echo "__ SE\n";

			fwrite($X12__835__File, "SE*".
									$SE01__NumberofIncludedSegments."*".
									$SE02__TransactionSetControlNumber.
									"~".$linefeed);
		}

		/* ************************************************************************************************ */
		// ST - TRANSACTION SET HEADER
		$TransactionSetControlNumber++;
		$TransactionSegmentCount=0;
		if($TransactionSetControlNumber<1000)
		{
			if($TransactionSetControlNumber<100)
			{
				if($TransactionSetControlNumber<10)
				{	$ST02__TransactionSetControlNumber="000".$TransactionSetControlNumber;	}
				else
				{	$ST02__TransactionSetControlNumber="00".$TransactionSetControlNumber;	}
			}
			else
			{	$ST02__TransactionSetControlNumber="0".$TransactionSetControlNumber;	}
		}
		else
		{	$ST02__TransactionSetControlNumber=$TransactionSetControlNumber;	}

		// echo "__ ST\n";

		$check__count++;

		fwrite($X12__835__File, "ST*835*".
				$ST02__TransactionSetControlNumber.
				"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// BPR - FINANCIAL INFORMATION
		if(odbc_result($process,"vendor__net__amount")<=0)
		{	
			$errormsg.="BPR02 Provider net amount <= $0: "
						.odbc_result($process,"vendor__name")
						." [".odbc_result($process,"vendor__NPI")."]<br />";	
		}
		if(odbc_result($process,"check__date")=="" || odbc_result($process,"check__date")==null)
		{	
			$errormsg.="BPR16 Provider check date is missing: "
						.odbc_result($process,"vendor__name")
						." [".odbc_result($process,"vendor__NPI")."]<br />";	
		}

		/* Outputs a line per vendor check */
		$infomsg.="<tr>";
			$infomsg.="<td>".odbc_result($process,"check__number")."</td>";
			$infomsg.="<td>".odbc_result($process,"vendor__name")."</td>";
			$infomsg.="<td style=\"text-align:right;\">$".number_format(odbc_result($process,"vendor__charges"),2)."</td>";
			$infomsg.="<td style=\"text-align:right;\">$".number_format(odbc_result($process,"vendor__net__amount"),2)."</td>";
			$infomsg.="<td style=\"text-align:right;\">".number_format(odbc_result($process,"vendor__claim__count"))."</td>";
			$infomsg.="<td style=\"text-align:right;\">".number_format(odbc_result($process,"vendor__proc__count"))."</td>";
		$infomsg.="</tr>";

		$BPR01__TransactionHandlingCode="C";
			// Required
			// C Payment Accompanies Remittance Advice
			// D Make Payment Only
			// H Notification Only
			// I Remittance Information Only
			// P Prenotification of Future Transfers
			// U Split Payment and Remittance
			// X Handling Party’s Option to Split Payment and Remittance
		$BPR02__TotalActualProviderPaymentAmount=number_format(odbc_result($process,"vendor__net__amount"),2,'.','');
		$BPR03__CreditDebitFlag="C";
			// Required
			// C Credit
			// D Debit
		$BPR04__PaymentMethodCode="BOP";
			// Required
			// ACH Automated Clearing House (ACH)
			// BOP Financial Institution Option
			// CHK Check
			// FWT Federal Reserve Funds/Wire Transfer - Nonrepetitive
			// Non-Payment Data
		$BPR05__PaymentFormatCode="";
			// Situational
			// CCP Cash Concentration/Disbursement plus Addenda (CCD+) (ACH)
			// CTX Corporate Trade Exchange (CTX) (ACH)
		$BPR06__DepositoryFinancialInstitutionQualifier="01";
			// Situational
			// 01 ABA Transit Routing Number Including Check Digits	(9 digits)
			// 04 Canadian Bank Branch and Institution Number
		$BPR07__DFI__ID="99999999";
			// Situational
		$BPR08__AccountNoQualifier="DA";
			// Situational
			// DA Demand Deposit
		$BPR09__SenderBankAccountNo="483021811769";
			// Situational
		$BPR10__OriginatingCompanyIdent="1"."205038398";		//USI Tax ID
			// Situational
		$BPR11__OriginatingCompanySupplementalCode="";
			// Situational
		$BPR12__IDNumberQualifier="01";
			// Situational
			// 01 ABA Transit Routing Number Including Check Digits	(9 digits)
			// 04 Canadian Bank Branch and Institution Number
		$BPR13__IdentificationNumber="0012345678";			//??
			// Situational
		$BPR14__AccountNumberQualifier="DA";					//??
			// Situational
			// DA Demand Deposit
			// SG Savings
		$BPR15__AccountNumber="0012345678";					//??
			// Situational
		$BPR16__checkIssueDate=substr(odbc_result($process,"check__date"),0,4)
							 .substr(odbc_result($process,"check__date"),5,2)
							 .substr(odbc_result($process,"check__date"),8,2);
			// Situational

		fwrite($X12__835__File, "BPR*".
			$BPR01__TransactionHandlingCode."*".
			$BPR02__TotalActualProviderPaymentAmount."*".
			$BPR03__CreditDebitFlag."*".
			$BPR04__PaymentMethodCode."*".
			$BPR05__PaymentFormatCode."*".
			$BPR06__DepositoryFinancialInstitutionQualifier."*".
			$BPR07__DFI__ID."*".
			$BPR08__AccountNoQualifier."*".
			$BPR09__SenderBankAccountNo."*".
			$BPR10__OriginatingCompanyIdent."*".
			$BPR11__OriginatingCompanySupplementalCode."*".
			$BPR12__IDNumberQualifier."*".
			$BPR13__IdentificationNumber."*".
			$BPR14__AccountNumberQualifier."*".
			$BPR15__AccountNumber."*".
			$BPR16__checkIssueDate."~".$linefeed);
		$TransactionSegmentCount++;
		// echo "__ BPR\n";

		/* ************************************************************************************************ */
		// TRN - REASSOCIATION TRACE NUMBER
		if(odbc_result($process,"check__number")=="" || odbc_result($process,"check__number")==null)
		{	
			$errormsg.="TRN Provider check number is missing: "
						.odbc_result($process,"vendor__name")
						." [".odbc_result($process,"vendor__NPI")."]<br />";	
		}
		$TRN01__TraceTypeCode="1";
			// Required
			// 1 Current Transaction Trace Numbers
		$TRN02__ReferenceIdent=odbc_result($process,"check__number");
			// Required
		$TRN03__OriginatingCompanyIdent="1"."987654321";
			// Required
		$TRN04__Reference__Id="";
			// Situational

		// echo "__ TRN\n";

		fwrite($X12__835__File, "TRN*".
			$TRN01__TraceTypeCode."*".
			$TRN02__ReferenceIdent."*".
			$TRN03__OriginatingCompanyIdent."~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// CUR - FOREIGN CURRENCY INFORMATION
//		$CUR01__EntityIdentifierCode="PR";
			// Required
			// PR Payer
//		$CUR02__CurrencyCode="USD";
			// Required
			// Standard ISO currency code
			// e.g. USD US Dollars

		// echo "__ CUR*PR\n";

//		fwrite($X12__835__File, "CUR*".
//								$CUR01__EntityIdentifierCode."*".
//								$CUR02__CurrencyCode.
//								."~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// REF - RECEIVER IDENTIFICATION
//		$REF01__ReferenceIdentificationQualifier="EV";
		// Required
		// EV Receiver Identification Number
//		$REF02__ReferenceIdentification="";
		// Required

		// echo "__ REF*EV\n";

//		fwrite($X12__835__File, "REF*".
//								$REF01__ReferenceIdentificationQualifier."*".
//								$REF02__ReferenceIdentification.
//								"~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// REF - VERSION IDENTIFICATION
//		$REF01__ReferenceIdentificationQualifier="F2";
		// Required
		// F2 Version Code - Local
//		$REF02__ReferenceIdentification="";
		// Required

		// echo "__ REF*F2\n";

//		fwrite($X12__835__File, "REF*".
//								$REF01__ReferenceIdentificationQualifier."*".
//								$REF02__ReferenceIdentification.
//								"~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// DTM - PRODUCTION DATE
		$DTM01__DateTimeQualifier="405";
			// Required
			// 405 Production
		$DTM02__ProductionDate=date("Ymd");
			// Required

		// echo "__ DTM\n";

		fwrite($X12__835__File, "DTM*".
					$DTM01__DateTimeQualifier."*".
					$DTM02__ProductionDate.
					"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// N1 - Loop 1000A - PAYER IDENTIFICATION
		$N101__EntityIdentifierCode="PR";
			// Required
			// PR Payer
		$N102__PayerName="Paying Company Name";
			// Required

		// echo "1000A N1\n";

		fwrite($X12__835__File, "N1*".
					$N101__EntityIdentifierCode."*".
					$N102__PayerName.
					"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//N3 - Loop 1000A - PAYER ADDRESS
		$N301__AddressLine1="777 Main Street";
			// Required
		$N302__AddressLine2="13th Floor";
			// Situational

		// echo "1000A N3\n";

		if($N302__AddressLine2=="")
		{	fwrite($X12__835__File, "N3*".$N301__AddressLine1."~".$linefeed);	}
		else
		{	fwrite($X12__835__File, "N3*".$N301__AddressLine1."*".$N302__AddressLine2."~".$linefeed);	}
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//N4 - Loop 1000A - PAYER CITY, STATE, ZIP CODE
		$N401__city="New York";
			// Required
		$N402__state="NY";
			// Required
		$N403__zip="10001";
			// Required
//		$N404__country__code="";
			// Situational
			// Use the alpha-2 country codes from Part 1 of ISO 3166
//		$N405__location__qualifier="";
			// Not used
//		$N406__location__identifier="";
			// Not used
//		$N407__country__subdivision__code="";
			// Situational
			// Use the country subdivision codes from Part 2 of ISO 3166.

		// echo "1000A N4\n";

		fwrite($X12__835__File, "N4*".
								$N401__city."*".
								$N402__state."*".
								$N403__zip.
								"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//REF - Loop 1000A - ADDITIONAL PAYER IDENTIFICATION
//		$REF01__ReferenceIdentificationQualifier="HI";
			// Required
			// 2U Payer Identification Number
			// EO Submitter Identification Number
			// HI Health Industry Number (HIN)
			// NF National Association of Insurance Commissioners (NAIC) Code
//		$REF02__ReferenceIdentification="12345678";
			// Required

		// echo "1000A REF*HI\n";

//		fwrite($X12__835__File, "REF*".$REF01__ReferenceIdentificationQualifier."*".$REF02__ReferenceIdentification."~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//PER - Loop 1000A - PAYER Business Contact Information
//		$PER01__ContactFunctionCode="CX";
			// Required
			// CX Payers Claim Office
//		$PER02__Name="John Doe";
			// Situational
//		$PER03__CommunicationNumberQualifier="TE";
			// Situational
			// EM Electronic Mail
			// FX Facsimile
			// TE Telephone
//		$PER04__CommunicationNumber="2125551234";
			// Situational
//		$PER05__CommunicationNumberQualifier="FX";
			// Situational
			// EM Electronic Mail
			// EX Telephone Extension
			// FX Facsimile
			// TE Telephone
//		$PER06__CommunicationNumber="2125551235";
			// Situational
//		$PER07__CommunicationNumberQualifier="FX";
			// Situational
			// EX Telephone Extension
//		$PER08__CommunicationNumber="555";
			// Situational

		// echo "1000A PER*CX\n";

//		fwrite($X12__835__File, "PER*".
//								$PER01__ContactFunctionCode."*".
//								$PER02__Name."*".
//								$PER03__CommunicationNumberQualifier."*".
//								$PER04__CommunicationNumber."*".
//								$PER05__CommunicationNumberQualifier."*".
//								$PER06__CommunicationNumber."*".
//								$PER07__CommunicationNumberQualifier."*".
//								$PER08__CommunicationNumber.
//								"~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//PER - Loop 1000A - PAYER TECHNICAL CONTACT INFORMATION
//		$PER01__ContactFunctionCode="BL";
			// Required
			// BL Technical Department
//		$PER02__Name="Jane Doe";
			// Situational
//		$PER03__CommunicationNumberQualifier="TE";
			// Situational
			// EM Electronic Mail
			// FX Facsimile
			// TE Telephone
//		$PER04__CommunicationNumber="2125551234";
			// Situational
//		$PER05__CommunicationNumberQualifier="FX";
			// Situational
			// EM Electronic Mail
			// EX Telephone Extension
			// FX Facsimile
			// TE Telephone
//		$PER06__CommunicationNumber="2125551235";
			// Situational
//		$PER07__CommunicationNumberQualifier="FX";
			// Situational
			// EX Telephone Extension
//		$PER08__CommunicationNumber="555";
			// Situational

		// echo "1000A PER*CX\n";

//		fwrite($X12__835__File, "PER*".
//								$PER01__ContactFunctionCode."*".
//								$PER02__Name."*".
//								$PER03__CommunicationNumberQualifier."*".
//								$PER04__CommunicationNumber."*".
//								$PER05__CommunicationNumberQualifier."*".
//								$PER06__CommunicationNumber."*".
//								$PER07__CommunicationNumberQualifier."*".
//								$PER08__CommunicationNumber.
//								"~".$linefeed);
//		$TransactionSegmentCount++;


		/* ************************************************************************************************ */
		//PER - Loop 1000A - PAYER WEB SITE
//		$PER01__ContactFunctionCode="IC";
			// Required
			// IC Information Contact
//		$PER02__Name="";
			// Not Used
//		$PER03__CommunicationNumberQualifier="UR";
			// Required
			// UR Uniform Resource Locator (URL)
//		$PER04__CommunicationNumber="mywebsite@address.com";
			// Situational

		// echo "1000A PER*CX\n";

//		fwrite($X12__835__File, "PER*".
//								$PER01__ContactFunctionCode."*".
//								$PER02__Name."*".
//								$PER03__CommunicationNumberQualifier."*".
//								$PER04__CommunicationNumber.
//								"~".$linefeed);
//		$TransactionSegmentCount++;
		
		/* ************************************************************************************************ */
		//N1 - Loop 1000B - PAYEE IDENTIFICATION
		if(odbc_result($process,"vendor__name")=="")
		{	
			$errormsg.="1000B N102 Provider name missing: "
						.odbc_result($process,"vendor__name")
						." [".odbc_result($process,"vendor__NPI")."]<br />";	
		}

		if(odbc_result($process,"vendor__NPI")=="" || !is__numeric(substr(odbc_result($process,"vendor__NPI"),0,10)))
		{	
			$errormsg.="1000B N104 Provider NPI missing or in incorrect format: "
						.odbc_result($process,"vendor__name")
						." [".odbc_result($process,"vendor__NPI")."]<br />";	
		}

		$N101__EntityIdentifierCode="PE";
			// Required
			// PE Payee
		$N102__PayeeName=substr(odbc_result($process,"vendor__name"),0,80);
			// Required
		$N103__IdentificationCodeQualifier="XX";
			// Required
			// FI Federal Taxpayer’s Identification Number
			// XV Centers for Medicare and Medicaid Services PlanID
			// XX Centers for Medicare and Medicaid Services National Provider Identifier
		$N104__PayeeID=substr(odbc_result($process,"vendor__NPI"),0,10);
			// Required

		// echo "1000B N1\n";

		fwrite($X12__835__File, "N1*".
								$N101__EntityIdentifierCode."*".
								$N102__PayeeName."*".
								$N103__IdentificationCodeQualifier."*".
								$N104__PayeeID.
								"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//N3 - Loop 1000B - PAYEE ADDRESS
		if(odbc_result($process,"vendor__address1")=="")
		{	
			$errormsg.="1000B N301 Provider address missing address line: "
					.odbc_result($process,"vendor__address1")." - "
						.odbc_result($process,"vendor__name")
						." [".odbc_result($process,"vendor__NPI")."]<br />";	
		}
		
		$N301__AddressLine1=odbc_result($process,"vendor__address1");
			// Required
		$N302__AddressLine2=odbc_result($process,"vendor__address2");
			// Situational

		// echo "1000B N3\n";

		if($N302__AddressLine2=="")
		{	fwrite($X12__835__File, "N3*".$N301__AddressLine1."~".$linefeed);	}
		else
		{	fwrite($X12__835__File, "N3*".$N301__AddressLine1."*".$N302__AddressLine2."~".$linefeed);	}
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//N4 - Loop 1000B - PAYEE CITY, STATE, ZIP CODE
		if(odbc_result($process,"vendor__city")=="" ||odbc_result($process,"vendor__state")=="" ||odbc_result($process,"vendor__zipcode")=="")
		{	
			$errormsg.="1000B N401 N402 N403 Provider address missing city, state or zip: "
					.odbc_result($process,"vendor__city").","
					.odbc_result($process,"vendor__state")."  "
					.odbc_result($process,"vendor__zipcode")." - "
						.odbc_result($process,"vendor__name")
						." [".odbc_result($process,"vendor__NPI")."]<br />";	
		}

		$N401__city=odbc_result($process,"vendor__city");
			// Required
		$N402__state=odbc_result($process,"vendor__state");
			// Required
		$N403__zip=odbc_result($process,"vendor__zipcode");
			// Required
//		$N404__country__code="";
			// Situational
			// Use the alpha-2 country codes from Part 1 of ISO 3166
//		$N405__location__qualifier="";
			// Not used
//		$N406__location__identifier="";
			// Not used
//		$N407__country__subdivision__code="";
			// Situational
			// Use the country subdivision codes from Part 2 of ISO 3166.

		// echo "1000B N4\n";

		fwrite($X12__835__File, "N4*".
								$N401__city."*".
								$N402__state."*".
								$N403__zip.
								"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */

		$holdCheckNumber=odbc_result($process,"check__number");
	}

	//*********************
	//	CLAIM HEADER
	//*********************
	if($holdClaim__id=="" || $holdClaim__id!=odbc_result($process,"claim__id"))
	{
		/* ************************************************************************************************ */
		// LX - LOOP 2000 - HEADER NUMBER
		$LX01__AssignedNumber=$claimSeq;
			// Required

		// echo "2000 LX\n";

		fwrite($X12__835__File, "LX*".
								$LX01__AssignedNumber.
								"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// TS3 - PROVIDER SUMMARY INFORMATION
//		$TS301__ProviderIdentifier="";
			// Required
//		$TS302__FacilityTypeCode="";
			// Required
//		$TS303__FiscalPeriodDate="";
			// Required
//		$TS304__TotalClaimCount="";
			// Required
//		$TS305__TotalClaimChargeAmount="";
			// Required
//		$TS306__MonetaryAmount="";
			// Not used
//		$TS307__MonetaryAmount="";
			// Not used
//		$TS308__MonetaryAmount="";
			// Not used
//		$TS309__MonetaryAmount="";
			// Not used
//		$TS310__MonetaryAmount="";
			// Not used
//		$TS311__MonetaryAmount="";
			// Not used
//		$TS312__MonetaryAmount="";
			// Not used
//		$TS313__TotalMSPPayerAmount="";
			// Situational
//		$TS314__MonetaryAmount="";
			// Not used
//		$TS315__TotalNonLabChargeAmount="";
			// Situational
//		$TS316__MonetaryAmount="";
			// Not used
//		$TS317__TotalHCPCSReportedChargeAmount="";
			// Situational
//		$TS318__TotalHCPCSPayableAmount="";
			// Situational
//		$TS319__MonetaryAmount="";
			// Not used
//		$TS320__TotalProfessionalComponentAmount="";
			// Situational
//		$TS321__TotalMSPPatientLiabilityMetAmount="";
			// Situational
//		$TS322__TotalPatientReimbursementAmount="";
			// Situational
//		$TS323__TotalPIPClaimCount="";
			// Situational
//		$TS324__TotalPIPAdjustmentAmount="";
			// Situational

		// echo "2000 TS3\n";

//		fwrite($X12__835__File, "TS3*".
//								$TS301__ProviderIdentifier."*".
//								$TS302__FacilityTypeCode."*".
//								$TS303__FiscalPeriodDate."*".
//								$TS304__TotalClaimCount."*".
//								$TS306__MonetaryAmount."*".
//								$TS307__MonetaryAmount."*".
//								$TS308__MonetaryAmount."*".
//								$TS309__MonetaryAmount."*".
//								$TS310__MonetaryAmount."*".
//								$TS311__MonetaryAmount."*".
//								$TS312__MonetaryAmount."*".
//								$TS313__TotalMSPPayerAmount."*".
//								$TS314__MonetaryAmount."*".
//								$TS315__TotalNonLabChargeAmount."*".
//								$TS316__MonetaryAmount."*".
//								$TS317__TotalHCPCSReportedChargeAmount."*".
//								$TS318__TotalHCPCSPayableAmount."*".
//								$TS319__MonetaryAmount."*".
//								$TS320__TotalProfessionalComponentAmount."*".
//								$TS321__TotalMSPPatientLiabilityMetAmount."*".
//								$TS322__TotalPatientReimbursementAmount."*".
//								$TS323__TotalPIPClaimCount."*".
//								$TS324__TotalPIPAdjustmentAmount.
//								"~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// TS2 - PROVIDER SUPPLEMENTAL SUMMARY INFORMATION
//		$TS201__TotalDRGAmount="";
			// Situational
//		$TS202__TotalFederalSpecificAmount="";
			// Situational
//		$TS203__TotalHospitalSpecificAmount="";
			// Situational
//		$TS204__TotalDisproportionateShareAmount="";
			// Situational
//		$TS205__TotalCapitalAmount="";
			// Situational
//		$TS206__TotalIndirectMedicalEducationAmount="";
			// Situational
//		$TS207__TotalOutlierDayCount="";
			// Situational
//		$TS208__TotalDayOutlierAmount="";
			// Situational
//		$TS209__TotalCostOutlierAmount="";
			// Situational
//		$TS210__AverageDRGLengthofStay="";
			// Situational
//		$TS211__TotalDischargeCount="";
			// Situational
//		$TS212__TotalCostReportDayCount="";
			// Situational
//		$TS213__TotalCoveredDayCount="";
			// Situational
//		$TS214__TotalNoncoveredDayCount="";
			// Situational
//		$TS215__TotalMSPPassThroughAmount="";
			// Situational
//		$TS216__AverageDRGweight="";
			// Situational
//		$TS217__TotalPPSCapitalFSPDRGAmount="";
			// Situational
//		$TS218__TotalPPSCapitalHSPDRGAmount="";
			// Situational
//		$TS219__TotalPPSDSHDRGAmount="";
			// Situational

		// echo "2000 TS3\n";

//		fwrite($X12__835__File, "TS3*".
//								$TS201__TotalDRGAmount."*".
//								$TS202__TotalFederalSpecificAmount."*".
//								$TS203__TotalHospitalSpecificAmount."*".
//								$TS204__TotalDisproportionateShareAmount."*".
//								$TS205__TotalCapitalAmount."*".
//								$TS206__TotalIndirectMedicalEducationAmount."*".
//								$TS207__TotalOutlierDayCount."*".
//								$TS208__TotalDayOutlierAmount."*".
//								$TS209__TotalCostOutlierAmount."*".
//								$TS210__AverageDRGLengthofStay."*".
//								$TS211__TotalDischargeCount."*".
//								$TS212__TotalCostReportDayCount."*".
//								$TS213__TotalCoveredDayCount."*".
//								$TS214__TotalNoncoveredDayCount."*".
//								$TS215__TotalMSPPassThroughAmount."*".
//								$TS216__AverageDRGweight."*".
//								$TS217__TotalPPSCapitalFSPDRGAmount."*".
//								$TS218__TotalPPSCapitalHSPDRGAmount."*".
//								$TS219__TotalPPSDSHDRGAmount.
//								"~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// CLP - LOOP 2100 - CLAIM PAYMENT INFORMATION

		$clm__TotalClaimChargeAmount+=odbc_result($process,"clm__TotalClaimChargeAmount");
		$clm__ClaimPaymentAmount+=odbc_result($process,"clm__ClaimPaymentAmount");
		$claim__count++;

		if(odbc_result($process,"patient__number")=="")
		{	
			$errormsg.="2100 CLP01 Claim patient number missing: "
						." [".odbc_result($process,"claim__id")."]<br />";	
		}
		
		if(odbc_result($process,"clm__TotalClaimChargeAmount")<=0 || odbc_result($process,"clm__ClaimPaymentAmount")<=0)
		{	
			$errormsg.="2100 CLP03 CLP04 Claim charges or net__amount <= $0: "
						." [".odbc_result($process,"claim__id")."]<br />";	
		}
		
		$CLP01__ClaimSubmittersIdentifier=odbc_result($process,"claim__id");
			// Required
		$CPL02__ClaimStatusCode="1";	//1=Processed As Primary
			// Required
			// 1 Processed as Primary
			// 2 Processed as Secondary
			// 3 Processed as Tertiary
			// 4 Denied
			// 19 Processed as Primary, Forwarded to Additional Payer(s)
			// 20 Processed as Secondary, Forwarded to Additional Payer(s)
			// 21 Processed as Tertiary, Forwarded to Additional Payer(s)
			// 22 Reversal of Previous Payment
			// 23 Not Our Claim, Forwarded to Additional Payer(s)
			// 25 Predetermination Pricing Only - No Payment
		$CLP03__TotalClaimChargeAmount=number_format(odbc_result($process,"clm__TotalClaimChargeAmount"),2,'.','');
			// Required
		$CLP04__ClaimPaymentAmount=number_format(odbc_result($process,"clm__ClaimPaymentAmount"),2,'.','');
			// Required
		$CLP05__PatientResponsibilityAmount=number_format(odbc_result($process,"clm__copay__amount")
												+odbc_result($process,"clm__coinsurance__amount")
												+odbc_result($process,"clm__deductible"),2,'.','');
			// Situational
		if($CLP05__PatientResponsibilityAmount==0)
		{	$CLP05__PatientResponsibilityAmount="";	}

		$CLP06__ClaimFilingIndicatorCode="12";
			// Required
			// 12 Preferred Provider Organization (PPO) Use this code for Blue Cross/Blue Shield par arrangements.
			// 13 Point of Service (POS)
			// 14 Exclusive Provider Organization (EPO)
			// 15 Indemnity Insurance Use this code for Blue Cross/Blue Shield non-par arrangements.
			// 16 Health Maintenance Organization (HMO) Medicare Risk
			// AM Automobile Medical
			// CH Champus
			// DS Disability
			// HM Health Maintenance Organization
			// LM Liability Medical
			// MA Medicare Part A
			// MB Medicare Part B
			// MC Medicaid
			// OF Other Federal Program Use this code for the Black Lung Program.
			// TV Title V
			// VA Veteran Administration Plan
			// WC Workers’ Compensation Health Claim
		$CLP07__PayerClaimControlNumber=odbc_result($process,"claim__id");
			// Required
		$CLP08__FacilityCodeValue="11";
			// Situational
			// 11 Office
			// 12 Home
			// 21 Inpatient Hospital
			// 22 Outpatient Hospital
			// 23 Emergency Room - Hospital
			// 24 Ambulatory Surgical Center
			// 25 Birthing Center
			// 26 Military Treatment Facility
			// 31 Skilled Nursing Facility
			// 32 Nursing Facility
			// 33 Custodial Care Facility
			// 34 Hospice
			// 41 Ambulance - Land
			// 42 Ambulance - Air or Water
			// 51 Inpatient Psychiatric Facility
			// 52 Psychiatric Facility Partial Hospitalization
			// 53 Community Mental Health Center
			// 54 Intermediate Care Facility/Mentally Retarded
			// 55 Residential Substance Abuse Treatment Facility
			// 56 Psychiatric Residential Treatment Center
			// 50 Federally Qualified Health Center
			// 60 Mass Immunization Center
			// 61 Comprehensive Inpatient Rehabilitation Facility
			// 62 Comprehensive Outpatient Rehabilitation Facility
			// 65 End Stage Renal Disease Treatment Facility
			// 71 State or Local Public Health Clinic
			// 72 Rural Health Clinic
			// 81 Independent Laboratory
			// 99 Other Unlisted Facility
//		$CLP09__ClaimFrequencyCode="";
			// Situational
//		$CLP10__PatientStatusCode="";
			// Not Used
//		$CLP11__DiagnosisRelatedGroupDRGCode="";
			// Situational
//		$CLP12__DiagnosisRelatedGroupDRGWeight="";
			// Situational
//		$CLP13__DischargeFraction="";
			// Situational
//		$CLP14__ResponseCode="";
			//  Not Used

		// echo "2100 CLP\n";

		fwrite($X12__835__File, "CLP*".
								$CLP01__ClaimSubmittersIdentifier."*".
								$CPL02__ClaimStatusCode."*".
								$CLP03__TotalClaimChargeAmount."*".
								$CLP04__ClaimPaymentAmount."*".
								$CLP05__PatientResponsibilityAmount."*".
								$CLP06__ClaimFilingIndicatorCode."*".
								$CLP07__PayerClaimControlNumber."*".
								$CLP08__FacilityCodeValue.
								"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// CAS - CLAIM ADJUSTMENT
			
		$CAS01__ClaimAdjustmentGroupCode="CO";
			// Required
			// CO Contractual Obligations
			// CR Correction and Reversals
			// OA Other adjustments
			// PI Payor Initiated Reductions
			// PR Patient Responsibility
		$CAS02__ClaimAdjustmentReasonCode="45";	
			// Required
			// 45=Charge exceeds fee schedule/maximum allowable or contracted/legislated fee arrangement.
			//	http://www.wpc-edi.com/reference/codelists/healthcare/claim-adjustment-reason-codes/
		$CAS03__AdjustmentAmount=number_format($chargesminuscontratedamount,2,'.','');
			// Required
//		$CAS04__AdjustmentQuantity="";
			// Situational
//		$CAS05__AdjustmentReasonCode="";
			// Situational
//		$CAS06__AdjustmentAmount="";
			// Situational
//		$CAS07__AdjustmentQuantity="";
			// Situational
//		$CAS08__AdjustmentReasonCode="";
			// Situational
//		$CAS09__AdjustmentAmount="";
			// Situational
//		$CAS10__AdjustmentQuantity="";
			// Situational
//		$CAS11__AdjustmentReasonCode="";
			// Situational
//		$CAS12__AdjustmentAmount="";
			// Situational
//		$CAS13__AdjustmentQuantity="";
			// Situational
//		$CAS14__AdjustmentReasonCode="";
			// Situational
//		$CAS15__AdjustmentAmount="";
			// Situational
//		$CAS16__AdjustmentQuantity="";
			// Situational
//		$CAS17__AdjustmentReasonCode="";
			// Situational
//		$CAS18__AdjustmentAmount="";
			// Situational
//		$CAS19__AdjustmentQuantity="";
			// Situational

		// echo "2100 CAS*CO*131\n";

		fwrite($X12__835__File, "CAS*".
									$CAS01__ClaimAdjustmentGroupCode."*".
									$CAS02__ClaimAdjustmentReasonCode."*".
									$CAS03__AdjustmentAmount.
									"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// NM1 - LOOP 2100 - PATIENT NAME
		if(odbc_result($process,"patient__last__name")=="")
		{	
			$errormsg.="2100 NM103 Claim patient last name missing: "
						." [".odbc_result($process,"claim__id")."]<br />";	
		}
		if(odbc_result($process,"patient__first__name")=="")
		{	
			$errormsg.="2100 NM104 Claim patient first name missing: "
						." [".odbc_result($process,"claim__id")."]<br />";	
		}
		if(odbc_result($process,"patient__eligibility__ud")=="")
		{	
			$errormsg.="2100 NM109 Claim patient member id missing: "
						." [".odbc_result($process,"claim__id")."]<br />";	
		}
		$NM101__EntityIdentifierCode="QC";
			// Required
			// QC Patient
		$NM102__EntityTypeQualifier="1";
			// Required
			// 1 Person
			// 2 Non-Person Entity
		$NM103__NameLast=odbc_result($process,"patient__last__name");
			// Situational
		$NM104__NameFirst=odbc_result($process,"patient__first__name");
			// Situational
		$NM105__NameMiddle=odbc_result($process,"patient__middle__name");
			// Situational
		$NM106__NamePrefix="";
			// Not Used
		$NM107__NameSuffix=odbc_result($process,"patient__name__suffix");
			// Situational
		$NM108__IdentificationCodeQualifier="MI";	//member ID
			// Situational
			// 34 Social Security Number
			// HN Health Insurance Claim (HIC) Number
			// II Standard Unique Health Identifier for each Individual in the United States
			// MI Member Identification Number
			// MR Medicaid Recipient Identification Number
		$NM109__IdentificationCode=odbc_result($process,"patient__eligibility__ud");
			// Situational

		// echo "2100 NM1*QC\n";

		fwrite($X12__835__File, "NM1*".
								$NM101__EntityIdentifierCode."*".
								$NM102__EntityTypeQualifier."*".
								$NM103__NameLast."*".
								$NM104__NameFirst."*".
								$NM105__NameMiddle."*".
								$NM106__NamePrefix."*".
								$NM107__NameSuffix."*".
								$NM108__IdentificationCodeQualifier."*".
								$NM109__IdentificationCode.
								"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		//NM1 - LOOP 2100 - INSURED NAME
		if(odbc_result($process,"member__id")!=odbc_result($process,"subscriber__id")
			&& odbc_result($process,"subscriber__last__name")!="" && odbc_result($process,"subscriber__last__name")!=Null
			&& odbc_result($process,"subscriber__first__name")!="" && odbc_result($process,"subscriber__first__name")!=Null
			)
		{
			if(odbc_result($process,"subscriber__last__name")=="")
			{	
				$errormsg.="2100 NM103 Claim subscriber last name missing: "
							." [".odbc_result($process,"claim__id")."]<br />";	
			}
			if(odbc_result($process,"subscriber__first__name")=="")
			{	
				$errormsg.="2100 NM104 Claim subscriber first name missing: "
							." [".odbc_result($process,"claim__id")."]<br />";	
			}
			if(odbc_result($process,"subscriber__eligibility__ud")=="")
			{	
				$errormsg.="2100 NM109 Claim subscriber member id missing: "
							." [".odbc_result($process,"claim__id")."]<br />";	
			}
			
			$NM101__EntityIdentifierCode="IL";
				// Required
				// IL Insured or Subscriber
			$NM102__EntityTypeQualifier="1";
				// Required
				// 1 Person
				// 2 Non-Person Entity
			$NM103__NameLast=odbc_result($process,"subscriber__last__name");
				// Situational
			$NM104__NameFirst=odbc_result($process,"subscriber__first__name");
				// Situational
			$NM105__NameMiddle=odbc_result($process,"subscriber__middle__name");
				// Situational
			$NM106__NamePrefix="";
				// Not Used
			$NM107__NameSuffix=odbc_result($process,"subscriber__name__suffix");
				// Situational
			$NM108__IdentificationCodeQualifier="MI";	//member ID
				// Required
				// FI Federal Taxpayer’s Identification Number
				// II Standard Unique Health Identifier for each Individual in the United States
				// MI Member Identification Number
			if(odbc_result($process,"subscriber__eligibility__ud")==Null || odbc_result($process,"subscriber__eligibility__ud")=="")
			{	
				$NM109__IdentificationCode=odbc_result($process,"patient__eligibility__ud");	
					// Required
			}
			else
			{	
				$NM109__IdentificationCode=odbc_result($process,"subscriber__eligibility__ud");	
					// Required
			}

			// echo "2100 NM1*IL\n";

			fwrite($X12__835__File, "NM1*".
				$NM101__EntityIdentifierCode."*".
				$NM102__EntityTypeQualifier."*".
				$NM103__NameLast."*".
				$NM104__NameFirst."*".
				$NM105__NameMiddle."*".
				$NM106__NamePrefix."*".
				$NM107__NameSuffix."*".
				$NM108__IdentificationCodeQualifier."*".
				$NM109__IdentificationCode."~".$linefeed);
			$TransactionSegmentCount++;
		}

		/* ************************************************************************************************ */
		// NM1 - LOOP 2100 - CORRECTED PATIENT/INSURED NAME
//		if(odbc_result($process,"patient__last__name")=="")
//		{	
//			$errormsg.="2100 NM103 Claim patient last name missing: "
//						." [".odbc_result($process,"claim__id")."]<br />";	
//		}
//		if(odbc_result($process,"patient__first__name")=="")
//		{	
//			$errormsg.="2100 NM104 Claim patient first name missing: "
//						." [".odbc_result($process,"claim__id")."]<br />";	
//		}
//		if(odbc_result($process,"patient__eligibility__ud")=="")
//		{	
//			$errormsg.="2100 NM109 Claim patient member id missing: "
//						." [".odbc_result($process,"claim__id")."]<br />";	
//		}
//		$NM101__EntityIdentifierCode="74";
			// Required
			// 74 =Corrected Insured
//		$NM102__EntityTypeQualifier="1";
			// Required
			// 1 Person
			// 2 Non-Person Entity
//		$NM103__NameLast=odbc_result($process,"patient__last__name");
			// Situational
//		$NM104__NameFirst=odbc_result($process,"patient__first__name");
			// Situational
//		$NM105__NameMiddle=odbc_result($process,"patient__middle__name");
			// Situational
//		$NM106__NamePrefix="";
			// Not Used
//		$NM107__NameSuffix=odbc_result($process,"patient__name__suffix");
			// Situational
//		$NM108__IdentificationCodeQualifier="MI";	//member ID
			// Situational
			// 34 Social Security Number
			// HN Health Insurance Claim (HIC) Number
			// II Standard Unique Health Identifier for each Individual in the United States
			// MI Member Identification Number
			// MR Medicaid Recipient Identification Number
//		$NM109__IdentificationCode=odbc_result($process,"patient__eligibility__ud");
			// Situational

		// echo "2100 NM1*QC\n";

//		fwrite($X12__835__File, "NM1*".
//								$NM101__EntityIdentifierCode."*".
//								$NM102__EntityTypeQualifier."*".
//								$NM103__NameLast."*".
//								$NM104__NameFirst."*".
//								$NM105__NameMiddle."*".
//								$NM106__NamePrefix."*".
//								$NM107__NameSuffix."*".
//								$NM108__IdentificationCodeQualifier."*".
//								$NM109__IdentificationCode.
//								"~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// NM1 - Loop 2100 - SERVICE PROVIDER NAME
		if(odbc_result($process,"vendor__name")=="")
		{	
			$errormsg.="2100 NM103 Claim service provider name missing: "
						." [".odbc_result($process,"claim__id")."]<br />";
		}
		if(odbc_result($process,"vendor__NPI")=="" || !is__numeric(substr(odbc_result($process,"vendor__NPI"),0,10)))
		{	
			$errormsg.="2100 NM109 Claim service provider NPI in invalid format: "
						.odbc_result($process,"vendor__name")." - "
						.substr(odbc_result($process,"vendor__NPI"),0,10)
						." [".odbc_result($process,"claim__id")."]<br />";
		}

		$NM101__EntityIdentifierCode="82";
			// Required
			// 82 Rendering Provider
		$NM102__EntityTypeQualifier="2";
			// Required
			// 1 Person
			// 2 Non-Person Entity
		$NM103__NameLast=odbc_result($process,"vendor__name");
			// Situational
		$NM104__NameFirst="";
			// Situational
		$NM105__NameMiddle="";
			// Situational
		$NM106__NamePrefix="";
			// Not Used
		$NM107__NameSuffix="";
			// Situational
		$NM108__IdentificationCodeQualifier="XX";
			// Required
			// BD Blue Cross Provider Number
			// BS Blue Shield Provider Number
			// FI Federal Taxpayer’s Identification Number
			// MC Medicaid Provider Number
			// PC Provider Commercial Number
			// SL State License Number
			// UP Unique Physician Identification Number (UPIN)
			// XX Centers for Medicare and Medicaid National Provider Identifier
		$NM109__IdentificationCode=substr(odbc_result($process,"vendor__NPI"),0,10);
			// Required

		// echo "2100 NM1*82\n";

		fwrite($X12__835__File, "NM1*".
			$NM101__EntityIdentifierCode."*".
			$NM102__EntityTypeQualifier."*".
			$NM103__NameLast."*".
			$NM104__NameFirst."*".
			$NM105__NameMiddle."*".
			$NM106__NamePrefix."*".
			$NM107__NameSuffix."*".
			$NM108__IdentificationCodeQualifier."*".
			$NM109__IdentificationCode."~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// MOA - Loop 2100 - OUTPATIENT ADJUDICATION INFORMATION
		// Not used at this time

		/* ************************************************************************************************ */
		// REF - LOOP 2100 - OTHER CLAIM RELATED IDENTIFICATION - Class of Contract Code 
		if(odbc_result($process,"employergroup__ud")=="")
		{	
			$errormsg.="2100 REF02 Claim payor name missing: "
						." [".odbc_result($process,"claim__id")."]<br />";
		}
		$REF01__ReferenceIdentificationQualifier="CE";
		$REF02__ReferenceIdentification=substr(odbc_result($process,"employergroup__ud"),0,50);

		// echo "2100 REF*CE\n";

		fwrite($X12__835__File, "REF*".$REF01__ReferenceIdentificationQualifier."*".$REF02__ReferenceIdentification."~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// REF - LOOP 2100 - OTHER CLAIM RELATED IDENTIFICATION - Original Reference Number
		if(odbc_result($process,"document__locator__number")!="")
		{	
			$REF01__ReferenceIdentificationQualifier="F8";
			$REF02__ReferenceIdentification=odbc_result($process,"document__locator__number");

			// echo "2100 REF*F8\n";

			fwrite($X12__835__File, "REF*".$REF01__ReferenceIdentificationQualifier."*".$REF02__ReferenceIdentification."~".$linefeed);
			$TransactionSegmentCount++;
		}

		/* ************************************************************************************************ */
		// REF - LOOP 2100 - RENDERING PROVIDER IDENTIFICATION
//		$REF01__ReferenceIdentificationQualifier="";
//		$REF02__ReferenceIdentification="";

		// echo "2100 REF*\n";

//		fwrite($X12__835__File, "REF*".$REF01__ReferenceIdentificationQualifier."*".$REF02__ReferenceIdentification."~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// DTM - LOOP 2100 - CLAIM DATE - RECEIVED
		if(odbc_result($process,"date__received")=="")
		{	
			$errormsg.="2100 DTM02 Claim date received missing: "
						." [".odbc_result($process,"claim__id")."]<br />";
		}
		$DTM01__DateTimeQualifier="050";		//Received
		$DTM02__Date=substr(odbc_result($process,"date__received"),0,4)
					.substr(odbc_result($process,"date__received"),5,2)
					.substr(odbc_result($process,"date__received"),8,2);

		// echo "2100 DTM*050\n";

		fwrite($X12__835__File, "DTM*".$DTM01__DateTimeQualifier."*".$DTM02__Date."~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// DTM - LOOP 2100 - CLAIM DATE - STATEMENT DATE - FROM
//		$DTM01__DateTimeQualifier="232";		//Claim Statement Period Start
//		$DTM02__Date=substr(odbc_result($process,"first__from__service__date"),0,4).
//					substr(odbc_result($process,"first__from__service__date"),5,2).
//					substr(odbc_result($process,"first__from__service__date"),8,2);

		// echo "2100 DTM*232\n";

//		fwrite($X12__835__File, "DTM*".$DTM01__DateTimeQualifier."*".$DTM02__Date."~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// DTM - LOOP 2100 - CLAIM DATE - STATEMENT DATE - TO
//		$DTM01__DateTimeQualifier="233";		//Claim Statement Period End
//		$DTM02__Date=substr(odbc_result($process,"last__to__service__date"),0,4).
//					substr(odbc_result($process,"last__to__service__date"),5,2).
//					substr(odbc_result($process,"last__to__service__date"),8,2);

		// echo "2100 DTM*233\n";

//		fwrite($X12__835__File, "DTM*".$DTM01__DateTimeQualifier."*".$DTM02__Date."~".$linefeed);
//		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// PER - LOOP 2100 - CLAIM CONTACT INFORMATION
//		$PER01__ContactFunctionCode="CX";
//		$PER02__Name=substr(odbc_result($process,"employergroup__ud"),0,60);
//		$PER03__CommunicationNumberQualifier="TE";
//		$PER04__CommunicationNumber="8778746385";

		// echo "2100 PER*CX\n";

//		fwrite($X12__835__File, "PER*".$PER01__ContactFunctionCode."*"
//									.$PER02__Name."*"
//									.$PER03__CommunicationNumberQualifier."*"
//									.$PER04__CommunicationNumber."~".$linefeed);
//		$TransactionSegmentCount++;
		/* ************************************************************************************************ */

		/* ************************************************************************************************ */
		// AMT - LOOP 2100 - CLAIM SUPPLEMENTAL INFORMATION - Amount Allowed
		$AMT01__AmountQualCode="AU";		// DeductionAmount
		$AMT02__MonetaryAmount=number_format(odbc_result($process,"clm__contract__amount"),2,'.','');

		// echo "2100 AMT*AU\n";

		fwrite($X12__835__File, "AMT*".$AMT01__AmountQualCode."*".$AMT02__MonetaryAmount."~".$linefeed);
		$TransactionSegmentCount++;
		/* ************************************************************************************************ */

		$holdClaim__id=odbc_result($process,"claim__id");
		$claimSeq++;
	}

	//*********************
	// SERVICE LINE HEADER
	//*********************
	if($holdClaim__procedure__id=="" || $holdClaim__procedure__id!=odbc_result($process,"claim__procedure__id"))
	{
		/* ************************************************************************************************ */
		// SVC - LOOP 2110 - SERVICE PAYMENT INFORMATION
		if(odbc_result($process,"procedurecode__ud")=="")
		{	
			$errormsg.="2110 SVC01__2 Claim line procedure code missing: "
						." [".odbc_result($process,"claim__id")."]<br />";
		}
		if(odbc_result($process,"units")=="")
		{	
			$errormsg.="2110 SVC01__2 Claim line procedure code missing: "
						." [".odbc_result($process,"claim__id")."]<br />";
		}
		$SVC01__1__ProductServiceIDQualifier='HC';
		$SVC01__2__ProductServiceID=":".odbc_result($process,"procedurecode__ud");
		$SVC01__3__ProcedureModifier="";
		$SVC01__4__ProcedureModifier="";
		$SVC01__5__ProcedureModifier="";
		$SVC01__6__ProcedureModifier="";

		if(odbc_result($process,"modifier__1")!="")
		{	
			$SVC01__3__ProcedureModifier=":".substr(odbc_result($process,"modifier__1"),0,2);
			if(odbc_result($process,"modifier__2")!="")
			{	
				$SVC01__4__ProcedureModifier=":".substr(odbc_result($process,"modifier__2"),0,2);
				if(odbc_result($process,"modifier__3")!="")
				{	
					$SVC01__5__ProcedureModifier=":".substr(odbc_result($process,"modifier__3"),0,2);	
					if(odbc_result($process,"modifier__4")!="")
					{	
						$SVC01__6__ProcedureModifier=":".substr(odbc_result($process,"modifier__4"),0,2);	
					}
				}
			}
		}
			
		if(odbc_result($process,"procedurecode__ud")=="")
		{	
			$errormsg.="2110 SVC02 Claim line charges or net__amount <= $0: "
						." [".odbc_result($process,"claim__id")."]<br />";
		}
		
		$SVC02__MonetaryAmount=number_format(odbc_result($process,"charges"),2,'.','');
		$SVC03__MonetaryAmount=number_format(odbc_result($process,"net__amount"),2,'.','');
		$SVC04__ProductServiceID="";
		$SVC05__Quantity=number_format(odbc_result($process,"units"),0,'','');

		$proc__count++;
		
		// echo "2110 SVC ".odbc_result($process,"procedurecode__ud")." ".odbc_result($process,"modifier__1")."<br />";

		fwrite($X12__835__File, "SVC*".
			$SVC01__1__ProductServiceIDQualifier.
				$SVC01__2__ProductServiceID.
				$SVC01__3__ProcedureModifier.
				$SVC01__4__ProcedureModifier.
				$SVC01__5__ProcedureModifier.
				$SVC01__6__ProcedureModifier."*".
			$SVC02__MonetaryAmount."*".
			$SVC03__MonetaryAmount."*".
			$SVC04__ProductServiceID."*".
			$SVC05__Quantity.
			"~".$linefeed);
		$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// CAS - CLAIM ADJUSTMENT (for CONTRACTUAL OBLIGATIONS)
		$chargesminuscontratedamount=odbc_result($process,"charges")
									-odbc_result($process,"contract__amount");

		if($chargesminuscontratedamount>0)
		{
			$CAS01__ClaimAdjustmentGroupCode="CO";
				/*
					CO Contractual Obligations
					CR Correction and Reversals
					OA Other adjustments
					PI Payor Initiated Reductions
					PR Patient Responsibility
				*/
			$CAS02__ClaimAdjustmentReasonCode="45";	//45=Charge exceeds fee schedule/maximum allowable or contracted/legislated fee arrangement.
				//	http://www.wpc-edi.com/reference/codelists/healthcare/claim-adjustment-reason-codes/
			$CAS03__AdjustmentAmount=number_format($chargesminuscontratedamount,2,'.','');

			// echo "2100 CAS*CO*131\n";

			fwrite($X12__835__File, "CAS*".
					$CAS01__ClaimAdjustmentGroupCode."*".
					$CAS02__ClaimAdjustmentReasonCode."*".
					$CAS03__AdjustmentAmount."~".$linefeed);
			$TransactionSegmentCount++;
		}

		/* ************************************************************************************************ */
		// CAS - ADJUSTMENT (for PATIENT RESPONSIBILITY - DEDUCTIBLE)
		if(odbc_result($process,"deductible")>0)
		{
			$CAS01__ClaimAdjustmentGroupCode="PR";
				/*
					CO Contractual Obligations
					CR Correction and Reversals
					OA Other adjustments
					PI Payor Initiated Reductions
					PR Patient Responsibility
				*/
			$CAS02__ClaimAdjustmentReasonCode="1";		//1 = DEDUCTIBLE
				//	http://www.wpc-edi.com/reference/codelists/healthcare/claim-adjustment-reason-codes/
			$CAS03__AdjustmentAmount=number_format(odbc_result($process,"deductible"),2,'.','');

			// echo "2100 CAS*PR*1\n";

			fwrite($X12__835__File, "CAS*".
					$CAS01__ClaimAdjustmentGroupCode."*".
					$CAS02__ClaimAdjustmentReasonCode."*".
					$CAS03__AdjustmentAmount."~".$linefeed);
			$TransactionSegmentCount++;
		}

		/* ************************************************************************************************ */
		// CAS - ADJUSTMENT (for PATIENT RESPONSIBILITY - COINSURANCE)
		if(odbc_result($process,"coinsurance__amount")>0)
		{
			$CAS01__ClaimAdjustmentGroupCode="PR";
				/*
					CO Contractual Obligations
					CR Correction and Reversals
					OA Other adjustments
					PI Payor Initiated Reductions
					PR Patient Responsibility
				*/
			$CAS02__ClaimAdjustmentReasonCode="2";		//2 = COINSURANCE
				//	http://www.wpc-edi.com/reference/codelists/healthcare/claim-adjustment-reason-codes/
			$CAS03__AdjustmentAmount=number_format(odbc_result($process,"coinsurance__amount"),2,'.','');

			// echo "2100 CAS*PR*2\n";

			fwrite($X12__835__File, "CAS*".
					$CAS01__ClaimAdjustmentGroupCode."*".
					$CAS02__ClaimAdjustmentReasonCode."*".
					$CAS03__AdjustmentAmount."~".$linefeed);
			$TransactionSegmentCount++;
		}

		/* ************************************************************************************************ */
		// CAS - ADJUSTMENT (for PATIENT RESPONSIBILITY - COPAY)
		if(odbc_result($process,"copay__amount")>0)
		{
			$CAS01__ClaimAdjustmentGroupCode="PR";
				/*
					CO Contractual Obligations
					CR Correction and Reversals
					OA Other adjustments
					PI Payor Initiated Reductions
					PR Patient Responsibility
				*/
			$CAS02__ClaimAdjustmentReasonCode="3";		//3 = COPAY
				//	http://www.wpc-edi.com/reference/codelists/healthcare/claim-adjustment-reason-codes/
			$CAS03__AdjustmentAmount=number_format(odbc_result($process,"copay__amount"),2,'.','');

			// echo "2100 CAS*PR*3\n";

			fwrite($X12__835__File, "CAS*".
					$CAS01__ClaimAdjustmentGroupCode."*".
					$CAS02__ClaimAdjustmentReasonCode."*".
					$CAS03__AdjustmentAmount."~".$linefeed);
			$TransactionSegmentCount++;
		}

		/* ************************************************************************************************ */
		// CAS - ADJUSTMENT (for Ineligible amount)
		if(odbc_result($process,"ineligible__amount")>0)
		{
			$CAS01__ClaimAdjustmentGroupCode="CO";
				/*
					CO Contractual Obligations
					CR Correction and Reversals
					OA Other adjustments
					PI Payor Initiated Reductions
					PR Patient Responsibility
				*/
			$CAS02__ClaimAdjustmentReasonCode="59";		//59 = Processed based on multiple or concurrent procedure rules
				//	http://www.wpc-edi.com/reference/codelists/healthcare/claim-adjustment-reason-codes/
			$CAS03__AdjustmentAmount=number_format(odbc_result($process,"ineligible__amount"),2,'.','');

			// echo "2100 CAS*PR*3\n";

			fwrite($X12__835__File, "CAS*".
					$CAS01__ClaimAdjustmentGroupCode."*".
					$CAS02__ClaimAdjustmentReasonCode."*".
					$CAS03__AdjustmentAmount.
					"~".$linefeed);
			$TransactionSegmentCount++;
		}

	/* ************************************************************************************************ */
	//	DTM - LOOP 2110 - SERVICE DATE
	//	$DTM01__DateTimeQualifier="472";		//Service Period Start
	//	$DTM02__Date=substr(odbc_result($process,"from__service__date"),0,4).
	//				substr(odbc_result($process,"from__service__date"),5,2).
	//				substr(odbc_result($process,"from__service__date"),8,2);

	// echo "2110 DTM*472\n";

	//	fwrite($X12__835__File, "DTM*".$DTM01__DateTimeQualifier."*".$DTM02__Date."~".$linefeed);
	//	$TransactionSegmentCount++;

		/* ************************************************************************************************ */
		// AMT - LOOP 2110 - SERVICE PAYMENT INFORMATION - Deductible
	//	$AMT01__AmountQualCode="KH";		// DeductionAmount
	//	$AMT02__MonetaryAmount==number_format(odbc_result($process,"deductible"),2,'.','');;

		// echo "2100 AMT*KH\n";

	//	fwrite($X12__835__File, "AMT*".$AMT01__AmountQualCode."*".$AMT02__MonetaryAmount."~".$linefeed);
	//	$TransactionSegmentCount++;

		$holdClaim__procedure__id=odbc_result($process,"claim__procedure__id");
	}

	/* ************************************************************************************************ */
	// LQ - LOOP 2110 - HEALTH CARE REMARK CODES 
	// http://www.wpc-edi.com/reference/codelists/healthcare/remittance-advice-remark-codes/
//	if(odbc_result($process,"eob__ud")!="")
//	{
//		$LQ01__CodeListQualifier="HE";
//		$LQ02__ReferenceIdentification=odbc_result($process,"eob__ud");
//
		// echo "2100 LQ*\n";
//
//		fwrite($X12__835__File, "LQ*".$LQ01__CodeListQualifier."*".$LQ02__ReferenceIdentification."~".$linefeed);
//		$TransactionSegmentCount++;
//	}
}

/* ************************************************************************************************ */
// SE - TRANSACTION SET TRAILER
$TransactionSegmentCount++;
$SE01__NumberofIncludedSegments=$TransactionSegmentCount;
	// Required
$SE02__TransactionSetControlNumber=$ST02__TransactionSetControlNumber;
	// Required
$GE01__NumberofTransactionSetsIncluded++;
$total__NumberofIncludedSegments+=$TransactionSegmentCount;
// echo "__ SE\n";

fwrite($X12__835__File, "SE*".
					$SE01__NumberofIncludedSegments."*".
					$SE02__TransactionSetControlNumber.
					"~".$linefeed);

/* ************************************************************************************************ */
// GE - TRANSACTION SET TRAILER
//
// NOTE: All elements of the GE are required
//

// Note: $GE01__NumberofTransactionSetsIncluded is maintained throughout the script
$GE02__GroupControlNumber=$GS06__GroupControlNumber;

// echo "__ GE\n";

fwrite($X12__835__File, "GE*".
						$GE01__NumberofTransactionSetsIncluded."*".
						$GE02__GroupControlNumber.
						"~".$linefeed);

/* ************************************************************************************************ */
// IEA - INTERCHANGE CONTROL TRAILER
//
// NOTE: All elements of the IEA are required
//

$IEA01__NumberofIncludedFunctionalGroups=1;
$IEA02__InterchangeControlNumber=$ISA13__InterchangeControlNumber;

// echo "__ IEA\n";

fwrite($X12__835__File, "IEA*".
						$IEA01__NumberofIncludedFunctionalGroups."*".
						$IEA02__InterchangeControlNumber.
						"~".$linefeed);

/* ************************************************************************************************ */

odbc_close($connection_string);

fclose($X12__835__File);

if($errormsg!="")
{
	$to="X12__835__ERRORS@mycompanyname.com";

	$subject="[Important] - X12 835 Export Errors";

	$errormsg.="You can append to the body of your error message here";

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	$from="mysysadmin@mycompanyname.com";

	mail($to, $subject, $errormsg, $headers, $from);
}
else
{
	if($claim__count>0)
	{
		$msg="Summary for check run <small>(File: ".$filename.")</small><br /><br />";
		$msg.="<table><tr><td>Checks&nbsp;</td><td style=\"text-align:right;\">".number_format($check__count)."</td></tr>";
		$msg.="<tr><td>Claims</td><td style=\"text-align:right;\">".number_format($claim__count)."</td></tr>";
		$msg.="<tr><td>Procedures</td><td style=\"text-align:right;\">".number_format($proc__count)."</td></tr>";
		$msg.="<tr><td>Charges</td><td style=\"text-align:right;\">$".number_format($charges,2)."</td></tr>";
		$msg.="<tr><td>Paid</td><td style=\"text-align:right;\">$".number_format($net__amount,2)."</td></tr></table>";
		$msg.="<br />";
		$msg.="<br /><table><tr><th>Check#</th><th></th><th>Charges</th><th>Paid</th><th>Claims</th><th>Lines</th></tr>".$infomsg."</table>";
		$msg.="<br />";
		$msg.="<br />Segments: ".number_format($total__NumberofIncludedSegments);
		$msg.="<br />XN Sets: ".number_format($GE01__NumberofTransactionSetsIncluded);
		$subject =  "X12 835 Export Summary";
	}
	else
	{	
		$msg.="<br /><b>NO CLAIMS EXPORTED</b>";	
		$subject="[IMPORTANT] - 0 claims exported (X12 835 claim export summary)";
	}

	$to="X12__835@mycompanyname.com";

	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	$from="mysysadmin@mycompanyname.com";

	mail($to, $subject, $msg, $headers, $from);

	/*
		Include your FTP script here, if you are submitting your file automatically
	*/

//	if($claim__count>0)
//	{	include('Transfer__X12__835__File.php');	}

}

?>