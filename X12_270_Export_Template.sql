Create Procedure AC_Export_X12_270 
-- *************************************************************************************************************
-- You will need to determine the value passed in each one of these parameters as these are health plan specific
-- *************************************************************************************************************
	@X12Version As VarChar(16), @SenderID As Char(15), @ReceiverID As Char(15), @testProdFlag As Char(1), @ICN As Char(9), @FedTaxID As Char(9), @NPI Char(12), @PlanMMISID Char(8)
As

-- FOR TESTING
-- Exec AC_Export_X12_270 '005010X279A1', 'AAAAA', 'BBBBB','T','000000001', '123456789','1234567890','12345678'

Begin

Declare @YYMMDD As Char(6), @YYYYMMDD As Char(8), @HHMM As Char(4),@TransacationCount As Int
Set @YYYYMMDD=Cast(DatePart(year, GetDate()) As Char(4))
			+ Right('0'+Cast(DatePart(month, GetDate()) As VarChar(2)),2)
			+ Right('0'+Cast(DatePart(day, GetDate()) As VarChar(2)),2)
Set @YYMMDD=SubString(Cast(DatePart(year, GetDate()) As VarChar(4)),3,2)
			+ Right('0'+Cast(DatePart(month, GetDate()) As VarChar(2)),2)
			+ Right('0'+Cast(DatePart(day, GetDate()) As VarChar(2)),2)
Set @HHMM=Right('0'+Cast(DatePart(hour, GetDate()) As VarChar(2)),2)
			+ Right('0'+Cast(DatePart(minute, GetDate()) As VarChar(2)),2)
Set @TransacationCount=0

If Not OBJECT_ID('dbo.tbl_TEMP_X12270') Is Null
	Drop Table dbo.tbl_TEMP_X12270

--  ************************************
--  * ISA                              *
--  ************************************
Select	  0 As MemberID
		, 1 As rowID
		, 'ISA*'					
		 +'00*'						--01	Authorization information qualifier
		 +'          *'				--02	Authorization information
		 +'00*'						--03	Security information qualifier
		 +'          *'				--04	Security information
		 +'ZZ*'						--05	Interchange Sender qualifier
		 +@SenderID+'*'				--06	Interchange Sender identifier
		 +'ZZ*'						--07	Interchange Receiver qualifier
		 +@ReceiverID+'*'			--08	Interchange Receiver identifier
		 +@YYMMDD+'*'				--09	Interchange Date
		 +@HHMM+'*'					--10	Interchange Time
		 +'^*'						--11	Interchange Control Standards ID (Repetition separator)
		 +'00501*'					--12	Interchange Control Version Number
		 +@ICN+'*'					--13	Interchange Control Number
		 +'0*'						--14	Technical acknowledgment required
		 +@testProdFlag+'*'			--15	Usage indicator
		 +':'						--16	Sub-Element Separator
		 +'~' As X12_record
Into	tbl_TEMP_X12270

--  ************************************
--  * GS                               *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  0 As MemberID
		, 2 As rowID
		, 'GS*'					
		 +'00*'						--01	Functional ID code
		 +@SenderID+'*'				--02	Application sender's code
		 +@ReceiverID+'*'			--03	Application receiver's code
		 +@YYMMDD+'*'				--04	Date
		 +@HHMM+'*'					--05	Time
		 +'1*'						--06	Group control number
		 +'X*'						--07	Responsible agency code
		 +@X12Version				--08	Version/release indicator ID code
		 +'~' As X12_record

--  ************************************
--  * ST                               *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  0 As MemberID
		, 3 As rowID
		, 'ST*'					
		 +'270*'					--01	Transaction set ID code
		 +'0001*'					--02	Transaction set control number
		 +@X12Version				--03	Version/release
		 +'~' As X12_record

--  ************************************
--  * BHT                              *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  0 As MemberID
		, 4 As rowID
		, 'BHT*'					
		 +'0022*'					--01	Hierarchical Structure Code	
		 +'13*'						--02	Transaction Set Purpose Code	- 13= Request
		 +'1*'						--03	Reference Identification
		 +@YYYYMMDD+'*'				--04	Date
		 +@HHMM						--05	Time
		 +'~' As X12_record

--  ************************************
--  * Create TEMP file of Members      *
--  ************************************
If Not OBJECT_ID('tempDB..##TEMPMEMBERS') Is Null
	Drop Table ##TEMPMEMBERS


-- **************************************************************
-- **************************************************************
-- FOR TESTING - CREATE ##TEMPMEMBERS TABLE FROM HARD-CODING
-- **************************************************************
-- **************************************************************
Select	  100 As memberID
		, Cast('Dan' As VarChar(15)) As FirstName
		, Cast('Snow' As VarChar(15)) As LastName
		, '' As MiddleInitial
		, 'AA11111A' As MedicaidNumber
		, @PlanMMISID As PlanMMISID
		, @FedTaxID As FedTaxID
		, @NPI As NPI
Into	##TEMPMEMBERS

Insert	Into ##TEMPMEMBERS
Select	  200 As memberID
		, 'Shak' As FirstName
		, 'Targaryen' As LastName
		, '' As MiddleInitial
		, 'BB22222B' As MedicaidNumber
		, @PlanMMISID As PlanMMISID
		, @FedTaxID As FedTaxID
		, @NPI As NPI


Insert	Into ##TEMPMEMBERS
Select	300 As memberID
		, 'Ameya' As FirstName
		, 'Lannister' As LastName
		, '' As MiddleInitial
		, 'CC33333C' As MedicaidNumber
		, @PlanMMISID As PlanMMISID
		, @FedTaxID As FedTaxID
		, @NPI As NPI

-- **************************************************************
-- **************************************************************
-- **************************************************************

--  ************************************
--  * HL 1                             *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  T.MemberID
		, 50 As rowID
		, 'HL*'					
		 +'1*'					--01	Hierarchical ID Number
		 +'*'					--02	Hierarchical Parent ID Number
		 +'20*'					--03	Hierarchical Level Code
		 +'1'					--04	Hierarchical Child Code
		 +'~' As X12_record
From	##TEMPMEMBERS As T

--  ************************************
--  * NM1 PR (Payer)                   *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  T.MemberID
		, 51 As rowID
		, 'NM1*'					
		 +'PR*'					--01	Entity Identifier Code	- PR=Payer
		 +'2*'					--02	Entity Type Qualifier	- 2=Non Person Entity
		 +'*'					--03	Last Name or the Organization Name
		 +'*'					--04	First Name
		 +'*'					--05	Middle Name
		 +'*'					--06	Name Prefix
		 +'*'					--07	Name Suffix
		 +'FI*'					--08	Identification Code Qualifier	- FI= Federal Tax ID
		 +T.FedTaxID			--09	Identification Code				- Tax ID
		 +'~' As X12_record
From	##TEMPMEMBERS As T

--  ************************************
--  * HL 2                             *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  T.MemberID
		, 60 As rowID
		, 'HL*'					
		 +'2*'					--01	Hierarchical ID Number
		 +'1*'					--02	Hierarchical Parent ID Number
		 +'21*'					--03	Hierarchical Level Code
		 +'1'					--04	Hierarchical Child Code
		 +'~' As X12_record
From	##TEMPMEMBERS As T

--  ************************************
--  * NM1 1P (Provider)                *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  T.MemberID
		, 61 As rowID
		, 'NM1*'					
		 +'1P*'					--01	Entity Identifier Code	- 1P=Provider
		 +'2*'					--02	Entity Type Qualifier	- 2=Non Person Entity
		 +'*'					--03	Last Name or the Organization Name
		 +'*'					--04	First Name
		 +'*'					--05	Middle Name
		 +'*'					--06	Name Prefix
		 +'*'					--07	Name Suffix
		 +'XX*'					--08	Identification Code Qualifier	- XX=NPI
		 +T.NPI					--09	Identification Code				- NPI
		 +'~' As X12_record
From	##TEMPMEMBERS As T

--  ************************************
--  * HL 3                             *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  T.MemberID
		, 70 As rowID
		, 'HL*'					
		 +'3*'					--01	Hierarchical ID Number
		 +'2*'					--02	Hierarchical Parent ID Number
		 +'22*'					--03	Hierarchical Level Code
		 +'1'					--04	Hierarchical Child Code
		 +'~' As X12_record
From	##TEMPMEMBERS As T

--  ************************************
--  * NM1 IL  (Subscriber)             *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  T.MemberID
		, 71 As rowID
		, 'NM1*'					
		 +'IL*'					--01	Entity Identifier Code	- IL=Insured or Subscriber
		 +'1*'					--02	Entity Type Qualifier	- 1=Person
		 +T.LastName+'*'		--03	Subscriber Last Name
		 +T.FirstName+'*'		--04	Subscriber First Name
		 +T.MiddleInitial+'*'	--05	Subscriber Middle Initial
		 +'*'					--06	Subscriber Name Prefix
		 +'*'					--07	Subscriber Name Suffix
		 +'MI*'					--08	Identification Code Qualifier	- MI=Member Identification Number
		 +T.MedicaidNumber		--09	Identification Code				- Member ID
		 +'~' As X12_record
From	##TEMPMEMBERS As T

--  ************************************
--  * EQ Benefit Inquiry               *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  T.MemberID
		, 72 As rowID
		, 'EQ*'					
		 +'30*'					--01	Service Type Co	- 30=Health Benefit Plan Coverage
		 +'*'					--02	Composite Medical Procedure Identifier
		 +'FAM'					--03	Benefit Coverage Level Code
		 +'~' As X12_record
From	##TEMPMEMBERS As T

--  ************************************
--  * SE                               *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  999999999 As MemberID
		, 97 As rowID
		, 'SE*'					
		 +Right('0000'+Cast(@TransacationCount As VarChar(4)),4)+'*'
								--01	Number of included segments
		 +'0001'				--02	Transaction set control number
		 +'~' As X12_record

--  ************************************
--  * GE                               *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  999999999 As MemberID
		, 98 As rowID
		, 'GE*'					
		 +'1*'					--01	Number of Transaction Sets Included in this Function Group
		 +'1'					--02	Group Control Number
		 +'~' As X12_record

--  ************************************
--  * IEA                              *
--  ************************************
Insert	Into tbl_TEMP_X12270
Select	  999999999 As MemberID
		, 99 As rowID
		, 'IEA*'					
		 +'1*'					--01	Number of Included Functional Groups
		 +@ICN					--02	Interchange Control Number
		 +'~' As X12_record

--  ************************************
--  * Output the file                  *
--  ************************************
Set QUOTED_IDENTIFIER Off

Exec xp_cmdshell 'bcp "Select X12_record From dbo.tbl_TEMP_X12270 Order By MemberID, rowID" queryout "C:\X12_270_test.txt" -T -c' 

If Not OBJECT_ID('dbo.tbl_TEMP_X12270') Is Null
	Drop Table dbo.tbl_TEMP_X12270
	
End
