SET QUOTED_IDENTIFIER OFF

Declare @path As VarChar(128), @filename As VarChar(128), @today As DateTime
Set @path='Your filepath'
Set @filename='Your X12 filename'
Set @today=GetDate()

-- Create and execute the Bulk Insert Command
Truncate Table dbo.tbl_837_IN
Declare @BulkCmd As nvarChar(4000)
Set @BulkCmd = "BULK INSERT tbl_837_IN FROM "
+"'"+@path+@filename+"' "
+"WITH (DATAFILETYPE='char',FIELDTERMINATOR='~',ROWTERMINATOR='~')"
Exec	(@BulkCmd)

-- Parse out each field, one at a time. The maximum number of fields in a segment 
-- for the particular file that this process was built for was 9. So there are 9 steps.
-- If your file contains segments with more than 9 fields then you can add more steps

-- STEP 01
If Not OBJECT_ID('tempdb..##TEMP01') Is Null
	Drop Table ##TEMP01

Select	  SubString(Field01,1,CHARINDEX('*', Field01, 0)-1) As SegmentID
		, SubString(Field01,CHARINDEX('*', Field01, 0)+1,512) As Field01
Into	##TEMP01
From	dbo.tbl_837_IN

-- STEP 02
If Not OBJECT_ID('tempdb..##TEMP02') Is Null
	Drop Table ##TEMP02

Select	  SegmentID
		, Case When CHARINDEX('*', Field01, 0)=0 Then Field01
			   Else	SubString(Field01,1,CHARINDEX('*', Field01, 0)-1) 
		  End As Field01
		, Case When CHARINDEX('*', Field01, 0)=0 Then ''
			   Else	SubString(Field01,CHARINDEX('*', Field01, 0)+1,512) 
		  End As Field02
Into	##TEMP02
From	##TEMP01

-- STEP 03
If Not OBJECT_ID('tempdb..##TEMP03') Is Null
	Drop Table ##TEMP03

Select	  SegmentID, Field01
		, Case When CHARINDEX('*', Field02, 0)=0 Then Field02
			   Else	SubString(Field02,1,CHARINDEX('*', Field02, 0)-1) 
		  End As Field02
		, Case When CHARINDEX('*', Field02, 0)=0 Then ''
			   Else	SubString(Field02,CHARINDEX('*', Field02, 0)+1,512) 
		  End As Field03
Into	##TEMP03
From	##TEMP02

-- STEP 04
If Not OBJECT_ID('tempdb..##TEMP04') Is Null
	Drop Table ##TEMP04

Select	  SegmentID, Field01, Field02
		, Case When CHARINDEX('*', Field03, 0)=0 Then Field03
			   Else	SubString(Field03,1,CHARINDEX('*', Field03, 0)-1) 
		  End As Field03
		, Case When CHARINDEX('*', Field03, 0)=0 Then ''
			   Else	SubString(Field03,CHARINDEX('*', Field03, 0)+1,512) 
		  End As Field04
Into	##TEMP04
From	##TEMP03

-- STEP 05
If Not OBJECT_ID('tempdb..##TEMP05') Is Null
	Drop Table ##TEMP05

Select	  SegmentID, Field01, Field02, Field03
		, Case When CHARINDEX('*', Field04, 0)=0 Then Field04
			   Else	SubString(Field04,1,CHARINDEX('*', Field04, 0)-1) 
		  End As Field04
		, Case When CHARINDEX('*', Field04, 0)=0 Then ''
			   Else	SubString(Field04,CHARINDEX('*', Field04, 0)+1,512) 
		  End As Field05
Into	##TEMP05
From	##TEMP04

-- STEP 06
If Not OBJECT_ID('tempdb..##TEMP06') Is Null
	Drop Table ##TEMP06

Select	  SegmentID, Field01, Field02, Field03, Field04
		, Case When CHARINDEX('*', Field05, 0)=0 Then Field05
			   Else	SubString(Field05,1,CHARINDEX('*', Field05, 0)-1) 
		  End As Field05
		, Case When CHARINDEX('*', Field05, 0)=0 Then ''
			   Else	SubString(Field05,CHARINDEX('*', Field05, 0)+1,512) 
		  End As Field06
Into	##TEMP06
From	##TEMP05

-- STEP 07
If Not OBJECT_ID('tempdb..##TEMP07') Is Null
	Drop Table ##TEMP07

Select	  SegmentID, Field01, Field02, Field03, Field04, Field05
		, Case When CHARINDEX('*', Field06, 0)=0 Then Field06
			   Else	SubString(Field06,1,CHARINDEX('*', Field06, 0)-1) 
		  End As Field06
		, Case When CHARINDEX('*', Field06, 0)=0 Then ''
			   Else	SubString(Field06,CHARINDEX('*', Field06, 0)+1,512) 
		  End As Field07
Into	##TEMP07
From	##TEMP06

-- STEP 08
If Not OBJECT_ID('tempdb..##TEMP08') Is Null
	Drop Table ##TEMP08

Select	  SegmentID, Field01, Field02, Field03, Field04, Field05, Field06
		, Case When CHARINDEX('*', Field07, 0)=0 Then Field07
			   Else	SubString(Field07,1,CHARINDEX('*', Field07, 0)-1) 
		  End As Field07
		, Case When CHARINDEX('*', Field07, 0)=0 Then ''
			   Else	SubString(Field07,CHARINDEX('*', Field07, 0)+1,512) 
		  End As Field08
Into	##TEMP08
From	##TEMP07

-- STEP 08
Insert	Into dbo.tbl_837
Select	  0 As HierarchicalLevel, SegmentID, Field01, Field02, Field03, Field04, Field05, Field06, Field07
		, Case When CHARINDEX('*', Field08, 0)=0 Then Field08
			   Else	SubString(Field08,1,CHARINDEX('*', Field08, 0)-1) 
		  End As Field08
		, Case When CHARINDEX('*', Field08, 0)=0 Then ''
			   Else	SubString(Field08,CHARINDEX('*', Field08, 0)+1,512) 
		  End As Field09
		, @filename As ImportFileName
		, @today As ImportDate
From	##TEMP08

-- Assign the HierarchicalLevel to each record so that we can group the segments per claim
If Not OBJECT_ID('tempdb..##TEMPHL') Is Null
	Drop Table ##TEMPHL

Select	HL1.Field01 As HierarchicalLevel, HL1.ID As IDStart, Case When HL2.ID Is Null Then 9999999 ELse HL2.ID End As IDEnd
Into	##TEMPHL
From	dbo.tbl_837 As HL1
Left	Outer Join dbo.tbl_837 As HL2 On HL2.SegmentID='HL' And HL2.Field01=(HL1.Field01+1)
Where	HL1.SegmentID='HL'

Update	D
Set		HierarchicalLevel=H.HierarchicalLevel
From	dbo.tbl_837 As D
Join	##TEMPHL As H On D.ID Between H.IDStart And H.IDEnd

-- Set the End transactions back to 0
Update	D
Set		HierarchicalLevel=0
From	dbo.tbl_837 As D
Where	D.SegmentID In('SE','GE','IEA')

-- Housekeeping
If Not OBJECT_ID('tempdb..##TEMP01') Is Null
	Drop Table ##TEMP01
If Not OBJECT_ID('tempdb..##TEMP02') Is Null
	Drop Table ##TEMP02
If Not OBJECT_ID('tempdb..##TEMP03') Is Null
	Drop Table ##TEMP03
If Not OBJECT_ID('tempdb..##TEMP04') Is Null
	Drop Table ##TEMP04
If Not OBJECT_ID('tempdb..##TEMP05') Is Null
	Drop Table ##TEMP05
If Not OBJECT_ID('tempdb..##TEMP06') Is Null
	Drop Table ##TEMP06
If Not OBJECT_ID('tempdb..##TEMP07') Is Null
	Drop Table ##TEMP07
If Not OBJECT_ID('tempdb..##TEMP08') Is Null
	Drop Table ##TEMP08
If Not OBJECT_ID('tempdb..##TEMPHL') Is Null
	Drop Table ##TEMPHL
