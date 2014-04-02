SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER OFF
GO

Create procedure [dbo].[Import_X12_TA1] @path As varChar(128), @filename As varChar(64), @emailRecipient As varChar(128)
As

/* Initial table set up
CREATE TABLE [dbo].[tbl_X12_TA1_Import](
	[col001] [varchar](max) NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
*/

/*
TA1 Segments:
01	Interchange control number
	Uniquely identifies the interchange
	Sender assigns the interchange control number 
	Together with the sender ID uniquely identifies the interchange data to the recipient
02	Interchange Date
	YYMMDD format
03	Interchange Time
	24-hour clock format
04	Interchange Acknowledgement Code
	A=Accepted
	R=Rejected
	E=Accepted,but the file contains errors and must be resubmitted
05	Interchange Note Code
	A three-digit number that corresponds to one of the note codes in tbl_TA1_InterChangeNoteCode
*/

Begin

Truncate Table dbo.tbl_X12_TA1_Import

Declare @BulkCmd As nvarChar(4000)
Set		@BulkCmd = "BULK INSERT tbl_X12_TA1_Import FROM '"+@path+@filename+"' WITH (ROWTERMINATOR='"+CHAR(10)+"')"

Exec	(@BulkCmd)

Declare @query As varchar(max), @body As varChar(max), @kount As int

Set @kount = (Select count(*) From dbo.tbl_X12_TA1_Import)

if @kount > 0
Begin
	Set @query="Set NOCOUNT On; Select Top 100 T.col001
		+Case When SubString(T.col001,1,3)='TA1' And SubString(T.col001,Len(T.col001)-4,1)='A' Then '<b> <== Accepted</b>'
			  When SubString(T.col001,1,3)='TA1' And SubString(T.col001,Len(T.col001)-4,1)='R' Then '<b> <== Rejected</b>'
			  When SubString(T.col001,1,3)='TA1' And SubString(T.col001,Len(T.col001)-4,1)='E' Then '<b> <== Accepted, but contains errors and must be resubmitted</b>'
			  Else '' End
		+Case When SubString(T.col001,1,3)='TA1' And Not I.InterChangeNoteCode Is Null Then '<br /><i>'+I.InterChangeNoteCode+' '+I.InterChangeNote+'</i>' Else '' End
		+'<br /><br />' 
		From dbo.tbl_X12_TA1_Import As T
		Left Outer Join dbo.tbl_TA1_InterChangeNoteCode As I On SubString(T.col001,1,3)='TA1' And SubString(T.col001,Len(T.col001)-2,3)=I.InterChangeNoteCode"
	
	Set	@body='<small>SQL: Import_X12_TA1</small><br /><br /><font face="Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace">'
	Set @body=@body+'<b>'+@filename+'</b><br /><br />'

	-- Email the first 100 lines of the file
	EXEC msdb..sp_send_dbmail 
	@profile_name='YourEmailProfile',
	@recipients=@emailRecipient,
	@subject='[Important]: X12 834 TA1 file validation',
	@body=@body,
	@body_format='HTML',
	@query=@query,
	@query_result_header=0
End	

End
