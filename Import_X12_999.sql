SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER OFF
GO

Create procedure [dbo].[Import_X12_999] @path As varChar(128), @filename As varChar(64), @emailRecipient As varChar(128)
As

Begin

Truncate Table tbl_X12_999_Import

Declare @BulkCmd As nvarChar(4000)
Set		@BulkCmd = "BULK INSERT tbl_X12_999_Import FROM '"+@path+@filename+"' WITH (ROWTERMINATOR='\n')"

Exec	(@BulkCmd)

Declare @query As varchar(max), @body As varChar(max), @kount As int

Set @kount = (Select count(*) From tbl_X12_999_Import)

if @kount > 0
Begin

	If OBJECT_ID('tempdb..##TEMP_X12_999_1') Is Not Null
		Drop Table ##TEMP_X12_999_1
	If OBJECT_ID('tempdb..##TEMP_X12_999_2') Is Not Null
		Drop Table ##TEMP_X12_999_2
	If OBJECT_ID('tempdb..##TEMP_X12_999_3') Is Not Null
		Drop Table ##TEMP_X12_999_3
	If OBJECT_ID('tempdb..##TEMP_X12_999_4') Is Not Null
		Drop Table ##TEMP_X12_999_4
	If OBJECT_ID('tempdb..##TEMP_X12_999_5') Is Not Null
		Drop Table ##TEMP_X12_999_5

	-- Parse the tbl_X12_999_Import table into separate fields 
	Select	X.col001 As WholeRecord
			, SUBSTRING(X.col001,1,CHARINDEX('*',X.col001)-1) As Segment
			, SUBSTRING(X.col001,CHARINDEX('*',X.col001)+1,LEN(X.col001)-CHARINDEX('*',X.col001)) As col001 
	Into	##TEMP_X12_999_1
	From	tbl_X12_999_Import As X

	Select	WholeRecord
			, X.Segment
			, Case When CHARINDEX('*',X.col001)=0 Then X.col001
			  Else SUBSTRING(X.col001,1,CHARINDEX('*',X.col001)-1) End As F1
			, Case When CHARINDEX('*',X.col001)=0 Then ''
			  Else SUBSTRING(X.col001,CHARINDEX('*',X.col001)+1,LEN(X.col001)-CHARINDEX('*',X.col001)) End As col001 
	Into	##TEMP_X12_999_2
	From	##TEMP_X12_999_1 As X

	Select	WholeRecord
			, X.Segment
			, X.F1
			, Case When CHARINDEX('*',X.col001)=0 Then X.col001
				   Else SUBSTRING(X.col001,1,CHARINDEX('*',X.col001)-1) End As F2
			, Case When CHARINDEX('*',X.col001)=0 Then ''
				   Else SUBSTRING(X.col001,CHARINDEX('*',X.col001)+1,LEN(X.col001)) End As col001 
	Into	##TEMP_X12_999_3
	From	##TEMP_X12_999_2 As X

	Select	WholeRecord
			, X.Segment
			, X.F1
			, X.F2
			, Case When CHARINDEX('*',X.col001)=0 Then X.col001
				   Else SUBSTRING(X.col001,1,CHARINDEX('*',X.col001)-1) End As F3
			, Case When CHARINDEX('*',X.col001)=0 Then ''
				   Else SUBSTRING(X.col001,CHARINDEX('*',X.col001)+1,LEN(X.col001)) End As col001 
	Into	##TEMP_X12_999_4
	From	##TEMP_X12_999_3 As X

	Select	WholeRecord
			, X.Segment
			, X.F1
			, X.F2
			, X.F3
			, Case When CHARINDEX('*',X.col001)=0 Then X.col001
				   Else SUBSTRING(X.col001,1,CHARINDEX('*',X.col001)-1) End As F4
			, Case When CHARINDEX('*',X.col001)=0 Then ''
				   Else SUBSTRING(X.col001,CHARINDEX('*',X.col001)+1,LEN(X.col001)) End As col001 
	Into	##TEMP_X12_999_5
	From	##TEMP_X12_999_4 As X

	Set @query="Set NOCOUNT On; Select Top 100 WholeRecord+Case When E1.Error_Msg Is Null Then '<br />' Else ' &nbsp;<== <b><i>'+E1.Error_Msg+'</i></b><br />' End
 From ##TEMP_X12_999_5 As X
 Left Outer Join X12_999_ErrorMessage As E1 On E1.Error_ID=X.Segment And (E1.Error_Seq='' Or E1.Error_Seq=X.F1)"
	
	Set	@body='<font face="Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace">'
	Set @body=@body+@path+@filename+'<br /><br />'

	Declare @subject As char(128)
	Set @subject='[Important]: '+@filename+' X12 834 file validation'

	-- Email the first 100 lines of the file
	EXEC msdb..sp_send_dbmail 
	@profile_name='YourMailProfile',
	@recipients=@emailRecipient,
	@subject=@subject,
	@body=@body,
	@body_format='HTML',
	@query=@query,
	@query_result_header=0

	If OBJECT_ID('tempdb..##TEMP_X12_999_1') Is Not Null
		Drop Table ##TEMP_X12_999_1
	If OBJECT_ID('tempdb..##TEMP_X12_999_2') Is Not Null
		Drop Table ##TEMP_X12_999_2
	If OBJECT_ID('tempdb..##TEMP_X12_999_3') Is Not Null
		Drop Table ##TEMP_X12_999_3
	If OBJECT_ID('tempdb..##TEMP_X12_999_4') Is Not Null
		Drop Table ##TEMP_X12_999_4
	If OBJECT_ID('tempdb..##TEMP_X12_999_5') Is Not Null
		Drop Table ##TEMP_X12_999_5
End	

End
